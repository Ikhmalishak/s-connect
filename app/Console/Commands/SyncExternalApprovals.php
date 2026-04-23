<?php

namespace App\Console\Commands;
Use Carbon\Carbon;
use App\Models\ShipmentTransportApproval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncExternalApprovals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approvals:sync-external';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Power Automate for approval status updates (bulk)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $loadingApprovalsUrl = config('services.power_automate.loading_approvals_url');
        $inspectionApprovalsUrl = config('services.power_automate.inspection_approvals_url');
        $shippingApprovalsUrl = config('services.power_automate.shipping_approvals_url');
        $cleanupUrl = config('services.power_automate.cleanup_url');

        try {
            $this->info('Polling for approval updates from Vercel...');

            $allProcessedIds = [];
            $totalProcessedCount = 0;

            // Poll loading approvals
            $loadingProcessed = $this->pollApprovals($loadingApprovalsUrl, 'loading');
            $allProcessedIds = array_merge($allProcessedIds, $loadingProcessed['ids']);
            $totalProcessedCount += $loadingProcessed['count'];

            // Poll inspection approvals
            $inspectionProcessed = $this->pollApprovals($inspectionApprovalsUrl, 'inspection');
            $allProcessedIds = array_merge($allProcessedIds, $inspectionProcessed['ids']);
            $totalProcessedCount += $inspectionProcessed['count'];

            // Poll shipping requirement approvals
            $shippingProcessed = $this->pollApprovals($shippingApprovalsUrl, 'shipping');
            $allProcessedIds = array_merge($allProcessedIds, $shippingProcessed['ids']);
            $totalProcessedCount += $shippingProcessed['count'];

            $this->info("Successfully processed {$totalProcessedCount} approvals locally");

            // Cleanup processed approvals from Vercel
            if (!empty($allProcessedIds)) {
                $this->cleanupProcessedApprovals($allProcessedIds, $cleanupUrl);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Bulk polling failed: ' . $e->getMessage());
            Log::error('Power Automate bulk polling failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }

    private function pollApprovals(string $pollingUrl, string $approvalType): array
    {
        try {
            $this->info("Polling {$approvalType} approvals from {$pollingUrl}...");

            // Fetch approvals from Vercel API
            $response = Http::timeout(30)->get($pollingUrl);

            if (!$response->successful()) {
                $this->error("Failed to fetch {$approvalType} approvals from Vercel: " . $response->status());
                return ['ids' => [], 'count' => 0];
            }

            $vercelApprovals = $response->json();

            if (empty($vercelApprovals)) {
                $this->info("No {$approvalType} approvals found in Vercel");
                return ['ids' => [], 'count' => 0];
            }

            $this->info("Found " . count($vercelApprovals) . " {$approvalType} approvals in Vercel");

            $processedIds = [];
            $processedCount = 0;

            foreach ($vercelApprovals as $vercelApproval) {
                if ($this->processVercelApproval($vercelApproval)) {
                    $processedIds[] = $vercelApproval['approval_id'];
                    $processedCount++;
                }
            }

            return ['ids' => $processedIds, 'count' => $processedCount];

        } catch (\Exception $e) {
            $this->error("{$approvalType} polling failed: " . $e->getMessage());
            Log::error("Power Automate {$approvalType} polling failed", ['error' => $e->getMessage()]);
            return ['ids' => [], 'count' => 0];
        }
    }

    private function processVercelApproval(array $vercelApproval): bool
    {
        try {
            // Validate required fields
            if (!isset($vercelApproval['approval_id']) || !isset($vercelApproval['decision']) || !isset($vercelApproval['approver'])) {
                Log::warning("Invalid Vercel approval format", ['approval' => $vercelApproval]);
                return false;
            }

            // Convert approval_id to integer
            $approvalId = (int) $vercelApproval['approval_id'];

            // Initialize variables
            $approval = null;
            $changeRequest = null;

            // Find the local approval based on type
            $approvalType = $vercelApproval['type'] ?? null;
            if ($approvalType === 'shipping') {
                $changeRequest = \App\Models\ShippingRequirementChange::find($approvalId);
                Log::info("Looking for shipping change request ID {$approvalId}");
            } else {
                // Default to container approvals (loading/inspection)
                $approval = ShipmentTransportApproval::find($approvalId);
                Log::info("Looking for container approval ID {$approvalId}");
            }

            if (!$approval && !$changeRequest) {
                Log::warning("Local approval/change request not found", ['approval_id' => $approvalId, 'type' => $approvalType]);
                return false;
            }

            $entity = $approval ?? $changeRequest;
            Log::info("Found approval/change request ID {$approvalId} of type " . get_class($entity));

            // Check if already processed (different field names for different entities)
            $alreadyProcessed = false;
            if ($approval && $approval->approval_status !== 'pending') {
                $alreadyProcessed = true;
            } elseif ($changeRequest && $changeRequest->status !== 'pending') {
                $alreadyProcessed = true;
            }

            if ($alreadyProcessed) {
                Log::info("Approval/change request {$approvalId} already processed locally");
                return true; // Still count as processed for cleanup
            }

            // Update status
            $status = $vercelApproval['decision'] === 'Approve' ? 'approved' : 'rejected';

            // Find or create user
            $user = \App\Models\User::firstOrCreate(
                ['email' => $vercelApproval['approver']],
                ['name' => 'Unknown'] // Vercel doesn't provide name, so use default
            );

            // Update approval status for all approval types
            if ($approval) {
                $approval->update([
                    'approval_status' => $status,
                    'approved_by' => $user->id,
                    'approved_at' => isset($vercelApproval['timestamp'])
                        ? Carbon::parse($vercelApproval['timestamp'])->setTimezone('UTC')
                        : now()->setTimezone('UTC')
                ]);

                // Special handling for loading approvals
                if ($approval->approval_type === 'loading' && $status === 'approved') {
                    $this->createNextSequentialApproval($approval);
                }
            }

            // Handle shipping requirement changes
            if ($changeRequest) {
                $this->processShippingRequirementChangeApproval($changeRequest, $status, $user);
            }

            // Broadcast real-time update for container approvals
            if ($approval && $approval->shipmentTransport) {
                broadcast(new \App\Events\ContainerStageUpdated($approval->shipmentTransport))->toOthers();
            }

            // Check and update container status for container-related approvals
            if ($approval && $approval->shipmentTransport) {
                $this->checkAndUpdateContainerStatus($approval->shipmentTransport);
            }

            Log::info("Processed approval {$approvalId}: {$status} by {$vercelApproval['approver']}");
            return true;

        } catch (\Exception $e) {
            Log::error("Exception processing Vercel approval {$vercelApproval['approval_id']}: " . $e->getMessage());
            return false;
        }
    }

    private function createNextSequentialApproval(ShipmentTransportApproval $approval): void
    {
        // Define the sequential order
        $sequence = ['warehouse', 'quality', 'shipping', 'security'];

        // Find the index of the approved department
        $currentIndex = array_search($approval->department, $sequence);

        if ($currentIndex === false || $currentIndex >= count($sequence) - 1) {
            // Last in sequence or invalid department
            return;
        }

        // Get the next department
        $nextDepartment = $sequence[$currentIndex + 1];

        // Check if next approval already exists
        $existingApproval = ShipmentTransportApproval::where([
            'shipment_transport_id' => $approval->shipment_transport_id,
            'department' => $nextDepartment,
            'approval_type' => 'loading',
        ])->first();

        if (!$existingApproval) {
            // Create the next approval in sequence
            $newApproval = ShipmentTransportApproval::create([
                'shipment_transport_id' => $approval->shipment_transport_id,
                'department' => $nextDepartment,
                'approval_type' => 'loading',
                'approval_status' => 'pending',
            ]);

            Log::info("Created next sequential approval for {$nextDepartment} department after {$approval->department} approval");

            // Send notification for the next department
            $this->sendDepartmentApprovalEmails($newApproval);
        }
    }

    private function sendDepartmentApprovalEmails(ShipmentTransportApproval $approval): void
    {
        try {
            // Get department users who can approve (with proper site filtering)
            $departmentUsers = $this->getDepartmentUsers($approval->department, $approval->shipmentTransport->site_id, true);

            if ($departmentUsers->isEmpty()) {
                Log::warning("No users found for department {$approval->department}, skipping notifications");
                return;
            }

            $triggerUrl = config('services.power_automate.trigger_url');
            if (!$triggerUrl) {
                Log::error("Power Automate trigger URL not configured");
                return;
            }

            // Collect all approver emails into an array for Power Automate to handle "first wins" logic
            $approverEmails = $departmentUsers->pluck('email')->toArray();

            $payload = [
                'approval_id' => $approval->id,
                'title' => $approval->shipmentTransport->container_number ?: $approval->shipmentTransport->transport_number,
                'description' => 'Container approval required for loading process',
                'approver_email' => $approverEmails,
                //'department' => $approval->department
            ];

            $response = Http::timeout(30)->post($triggerUrl, $payload);

            if ($response->successful()) {
                Log::info("Sent Power Automate notification to " . count($approverEmails) . " users for approval {$approval->id} in department {$approval->department}");
            } else {
                Log::error("Failed to send Power Automate notification for approval {$approval->id}: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("Exception sending Power Automate notifications for approval {$approval->id}: " . $e->getMessage());
        }
    }

    private function checkAndUpdateContainerStatus(\App\Models\ShipmentTransport $container)
    {
        // Refresh the container to get latest approvals
        $container = $container->fresh();
        $container->load('approvals');
        $approvals = $container->approvals;

        // Check if all required approvals are approved
        $qualityInspectionApproval = $approvals->where('department', 'quality')->where('approval_type', 'inspection')->first();
        $departmentLoadingApprovals = $approvals->where('approval_type', 'loading')->whereIn('department', ['warehouse', 'quality', 'shipping', 'security']);

        if ($container->stage === 'container_checking_approval' && $qualityInspectionApproval && $qualityInspectionApproval->approval_status === 'approved') {
            // Quality approved inspection - can upload photos
            $container->update(['stage' => 'container_loading_report']);

            // Send email to container creator
            try {
                \Illuminate\Support\Facades\Mail::to($container->createdBy->email)->send(new \App\Mail\ContainerApprovedForLoading($container));
            } catch (\Exception $e) {
                Log::error("Failed to send approval email to container creator: " . $e->getMessage());
            }

        } elseif ($container->stage === 'container_loading_report_approval') {
            // Check sequential approval: warehouse → quality → shipping → security
            $sequence = ['warehouse', 'quality', 'shipping', 'security'];
            $allInSequenceApproved = true;

            foreach ($sequence as $dept) {
                $approval = $departmentLoadingApprovals->where('department', $dept)->first();
                if (!$approval || $approval->approval_status !== 'approved') {
                    $allInSequenceApproved = false;
                    break;
                }
            }

            $qualityApprovedForLoading = $qualityInspectionApproval && $qualityInspectionApproval->approval_status === 'approved';

            if ($allInSequenceApproved && $qualityApprovedForLoading) {
                $container->update(['stage' => 'onboarding_ready']);

                // Broadcast event to refresh dashboard
                broadcast(new \App\Events\ContainerStageUpdated($container))->toOthers();
            }
        }
    }

    private function processShippingRequirementChangeApproval(\App\Models\ShippingRequirementChange $changeRequest, string $status, $user)
    {
        try {
            if ($changeRequest->status !== 'pending') {
                Log::info("Change request {$changeRequest->id} already processed");
                return;
            }

            // Process the change based on type
            $shippingRequirement = $changeRequest->shippingRequirement;

            if ($status === 'approved') {
                if ($changeRequest->change_type === 'create') {
                    // Create the new shipping requirement
                    $shippingRequirement = \App\Models\ShippingRequirement::create(array_merge($changeRequest->proposed_data, [
                        'last_updated_by' => $changeRequest->requested_by,
                        'attachment_path' => $changeRequest->attachment_path,
                        'change_requested_at' => $changeRequest->created_at,
                        'requires_approval' => false,
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                        'status' => 'normal',
                    ]));
                } elseif ($changeRequest->change_type === 'update') {
                    // Apply the update
                    $shippingRequirement->update(array_merge($changeRequest->proposed_data, [
                        'last_updated_by' => $changeRequest->requested_by,
                        'attachment_path' => $changeRequest->attachment_path,
                        'change_requested_at' => $changeRequest->created_at,
                        'requires_approval' => false,
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                        'status' => 'normal',
                    ]));
                } elseif ($changeRequest->change_type === 'delete') {
                    // Delete the requirement
                    $shippingRequirement->delete();
                }

                // Update the change request
                $changeRequest->update([
                    'status' => 'approved',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'review_comments' => 'Approved via Teams',
                ]);

                Log::info("Shipping requirement change request {$changeRequest->id} approved via Teams");
            } else {
                // Reject the change request
                $changeRequest->update([
                    'status' => 'rejected',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'review_comments' => 'Rejected via Teams',
                ]);

                // Set status back to normal for the shipping requirement
                if ($shippingRequirement) {
                    $shippingRequirement->update(['status' => 'normal']);
                }

                Log::info("Shipping requirement change request {$changeRequest->id} rejected via Teams");
            }

            // Fire event for real-time updates
            try {
                \App\Events\ShippingRequirementChangeProcessed::dispatch($changeRequest, $shippingRequirement, $status);
            } catch (\Exception $e) {
                Log::error("Failed to dispatch ShippingRequirementChangeProcessed event: " . $e->getMessage());
                // Don't fail the entire process for event dispatch issues
            }

        } catch (\Exception $e) {
            Log::error("Exception processing shipping requirement change approval {$changeRequest->id}: " . $e->getMessage());
        }
    }

    private function getDepartmentUsers($department, $containerSiteId = null, $forNotifications = false)
    {
        // For security department, handle both external and internal permissions
        if ($department === 'security') {
            if ($forNotifications) {
                // For Power Automate notifications, only use external permission (Teams licensed users)
                $query = \App\Models\User::permission('container.security.approve');
            } else {
                // For internal approvals, use both external and internal permissions
                $query = \App\Models\User::where(function ($q) {
                    $q->whereHas('permissions', function ($perm) {
                        $perm->where('name', 'container.security.approve');
                    })->orWhereHas('permissions', function ($perm) {
                        $perm->where('name', 'container.security.approve_internal');
                    });
                });
            }
        } else {
            // For other departments, use the standard permission
            $query = \App\Models\User::permission("container.{$department}.approve");
        }

        // FIX: Apply site filtering for ALL departments that need it
        if (in_array($department, ['warehouse', 'quality', 'shipping', 'security'])) {
            if ($department === 'shipping') {
                $query->where('site_id', 2);
            } elseif (in_array($department, ['warehouse', 'quality'])) {
                if ($containerSiteId && $containerSiteId > 0) {
                    $query->where('site_id', $containerSiteId);
                } else {
                    Log::error("No valid site_id for {$department}");
                    return collect();
                }
            }
        }

        return $query->get();
    }

    private function cleanupProcessedApprovals(array $processedIds, string $cleanupUrl): void
    {
        try {
            $this->info("Cleaning up " . count($processedIds) . " processed approvals from Vercel...");

            // Send bulk cleanup request
            $response = Http::timeout(30)->delete($cleanupUrl, [
                'approval_ids' => implode(',', $processedIds)
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $deletedCount = $result['deleted_count'] ?? count($processedIds);
                $this->info("Successfully cleaned up {$deletedCount} approvals from Vercel");
                Log::info("Bulk cleanup completed", ['processed_ids' => $processedIds, 'deleted_count' => $deletedCount]);
            } else {
                $this->error("Failed to cleanup approvals from Vercel: " . $response->status());
                Log::error("Vercel cleanup failed", ['processed_ids' => $processedIds, 'response' => $response->body()]);
            }

        } catch (\Exception $e) {
            $this->error("Exception during Vercel cleanup: " . $e->getMessage());
            Log::error("Vercel cleanup exception", ['processed_ids' => $processedIds, 'error' => $e->getMessage()]);
        }
    }
}
