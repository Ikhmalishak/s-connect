<?php

namespace App\Console\Commands;

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
        $pollingUrl = config('services.power_automate.polling_url');
        $cleanupUrl = config('services.power_automate.cleanup_url');

        if (!$pollingUrl) {
            $this->error('Power Automate polling URL not configured');
            return Command::FAILURE;
        }

        try {
            $this->info('Polling for approval updates from Vercel...');

            // Fetch all approvals from Vercel bulk API
            $response = Http::timeout(30)->get($pollingUrl);

            if (!$response->successful()) {
                $this->error("Failed to fetch approvals from Vercel: " . $response->status());
                return Command::FAILURE;
            }

            $vercelApprovals = $response->json();

            if (empty($vercelApprovals)) {
                $this->info('No approvals found in Vercel');
                return Command::SUCCESS;
            }

            $this->info("Found " . count($vercelApprovals) . " approvals in Vercel");

            $processedIds = [];
            $processedCount = 0;

            foreach ($vercelApprovals as $vercelApproval) {
                if ($this->processVercelApproval($vercelApproval)) {
                    $processedIds[] = $vercelApproval['approval_id'];
                    $processedCount++;
                }
            }

            $this->info("Successfully processed {$processedCount} approvals locally");

            // Cleanup processed approvals from Vercel
            if (!empty($processedIds)) {
                $this->cleanupProcessedApprovals($processedIds, $cleanupUrl);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Bulk polling failed: ' . $e->getMessage());
            Log::error('Power Automate bulk polling failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
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

            // Find the local approval
            $approval = ShipmentTransportApproval::find($approvalId);

            if (!$approval) {
                Log::warning("Local approval not found", ['approval_id' => $approvalId]);
                return false;
            }

            // Check if already processed
            if ($approval->approval_status !== 'pending') {
                Log::info("Approval {$approvalId} already processed locally");
                return true; // Still count as processed for cleanup
            }

            // Update approval status
            $status = $vercelApproval['decision'] === 'Approve' ? 'approved' : 'rejected';

            // Find or create user
            $user = \App\Models\User::firstOrCreate(
                ['email' => $vercelApproval['approver']],
                ['name' => 'Unknown'] // Vercel doesn't provide name, so use default
            );

            $approval->update([
                'approval_status' => $status,
                'approved_by' => $user->id,
                'approved_at' => $vercelApproval['timestamp'] ?? now()
            ]);

            // For loading approvals, create the next approval in sequence if approved
            if ($approval->approval_type === 'loading' && $status === 'approved') {
                $this->createNextSequentialApproval($approval);
            }

            // Broadcast real-time update
            broadcast(new \App\Events\ContainerStageUpdated($approval->shipmentTransport))->toOthers();

            // Check and update container status
            $this->checkAndUpdateContainerStatus($approval->shipmentTransport);

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
            // Get department users who can approve
            $departmentUsers = \App\Models\User::permission("container.{$approval->department}_approve")->get();

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

                // Send notification to security team that container is ready for onboarding
                $securityUsers = $this->getDepartmentUsers('security');
                if ($securityUsers->isNotEmpty()) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($securityUsers)->send(new \App\Mail\ContainerReadyForOnboarding($container));
                    } catch (\Exception $e) {
                        Log::error("Failed to send onboarding ready email to security team: " . $e->getMessage());
                    }
                }
            }
        }
    }

    private function getDepartmentUsers($department)
    {
        // Return users with the specific permission
        return \App\Models\User::permission("container.{$department}_approve")->get();
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
