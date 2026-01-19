<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ShipmentTransport;
use App\Models\ShipmentTransportApproval;
use App\Mail\ContainerInspectionPassed;
use App\Mail\ContainerApprovedForLoading;
use App\Mail\ContainerReadyForDepartmentApproval;
use App\Mail\ContainerRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ShipmentTransportApprovalController extends Controller
{
    // Define the sequential approval order for loading approvals
    private $loadingApprovalSequence = ['warehouse', 'quality', 'shipping', 'security'];

    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->input('status', 'pending'); // Default to pending
        $search = $request->input('search');

        $query = ShipmentTransportApproval::with(['shipmentTransport', 'approver']);

        // Apply site filter unless user is superadmin
        if (!$user->hasPermissionTo('superadmin')) {
            $query->whereHas('shipmentTransport', function ($q) use ($user) {
                $q->where('site_id', $user->site_id);
            });
        }

        // Always apply department filtering first (unless superadmin)
        if (!$user->hasPermissionTo('superadmin')) {
            $query->where(function ($q) use ($user) {
                // Always show user's own approvals (approved/rejected by them)
                $q->where('approved_by', $user->id);

                // Add department-specific approvals based on permissions
                if ($user->hasPermissionTo('container.management_approve')) {
                    $q->orWhere('department', 'management');
                }
                if ($user->hasPermissionTo('container.warehouse_approve')) {
                    $q->orWhere('department', 'warehouse');
                }
                if ($user->hasPermissionTo('container.shipping_approve')) {
                    $q->orWhere('department', 'shipping');
                }
                if ($user->hasPermissionTo('container.quality_approve')) {
                    $q->orWhere('department', 'quality');
                }
                if ($user->hasPermissionTo('container.security_approve')) {
                    $q->orWhere('department', 'security');
                }
            });
        }

        // Then apply status filter (within the department-filtered results)
        if ($status && $status !== 'all') {
            $query->where('approval_status', $status);
        }
        // If status is 'all' or not set, show all statuses within department permissions

        // Apply search filter
        if ($search) {
            $query->whereHas('shipmentTransport', function ($q) use ($search) {
                $q->where('transport_number', 'LIKE', "%{$search}%")
                  ->orWhere('sku_number', 'LIKE', "%{$search}%")
                  ->orWhere('forwarder', 'LIKE', "%{$search}%");
            });
        }

        $approvals = $query->latest()->paginate(20);

        // Add approved_by_name to each approval
        $approvals->getCollection()->transform(function ($approval) {
            $approval->approved_by_name = $approval->approver ? $approval->approver->name : null;
            return $approval;
        });

        return response()->json($approvals);
    }

    public function approve(Request $request, $approvalId)
    {
        $approval = ShipmentTransportApproval::findOrFail($approvalId);
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasPermissionTo('superadmin') && $approval->shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        // Check permissions based on department
        $requiredPermission = "container.{$approval->department}_approve";
        if (!$user->hasPermissionTo($requiredPermission) && !$user->hasPermissionTo('container.management_approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // For loading approvals, enforce sequential approval order
        if ($approval->approval_type === 'loading') {
            $container = $approval->shipmentTransport;
            $currentDeptIndex = array_search($approval->department, $this->loadingApprovalSequence);

            if ($currentDeptIndex === false) {
                return response()->json(['message' => 'Invalid department for approval'], 400);
            }

            // Check if all previous departments in sequence have approved
            for ($i = 0; $i < $currentDeptIndex; $i++) {
                $prevDept = $this->loadingApprovalSequence[$i];
                $prevApproval = $container->approvals->where('department', $prevDept)
                    ->where('approval_type', 'loading')
                    ->first();

                if (!$prevApproval || $prevApproval->approval_status !== 'approved') {
                    $prevDeptName = ucfirst($prevDept);
                    return response()->json([
                        'message' => "{$prevDeptName} department must approve first before {$approval->department} can approve"
                    ], 400);
                }
            }
        }

        $approval->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        // For loading approvals, create the next approval in sequence
        if ($approval->approval_type === 'loading') {
            $this->createNextSequentialApproval($approval->shipmentTransport, $approval->department);
        }

        // Broadcast real-time update for approval
        broadcast(new \App\Events\ContainerStageUpdated($approval->shipmentTransport))->toOthers();

        $this->checkAndUpdateContainerStatus($approval->shipmentTransport);

        return response()->json(['message' => 'Container approved successfully']);
    }

    public function reject(Request $request, $approvalId)
    {
        $approval = ShipmentTransportApproval::findOrFail($approvalId);
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasPermissionTo('superadmin') && $approval->shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        // Check permissions based on department
        $requiredPermission = "container.{$approval->department}_approve";
        if (!$user->hasPermissionTo($requiredPermission) && !$user->hasPermissionTo('container.management_approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $approval->update([
            'approval_status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        // Update container status based on rejection
        $container = $approval->shipmentTransport;
        if ($approval->department === 'management') {
            $container->update([
                'status' => 'failed',
                'failed_at' => 'container_checking_approval'
            ]);
        } else {
            $container->update([
                'status' => 'failed',
                'failed_at' => 'container_loading_report_approval'
            ]);
        }

        // Send rejection email
        try {
            Mail::to($container->createdBy->email)->send(new ContainerRejected($container, $approval));
        } catch (\Exception $e) {
            \Log::error("Failed to send rejection email: " . $e->getMessage());
        }

        return response()->json(['message' => 'Container rejected']);
    }

    private function checkAndUpdateContainerStatus(ShipmentTransport $container)
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
                Mail::to($container->createdBy->email)->send(new ContainerApprovedForLoading($container));
            } catch (\Exception $e) {
                \Log::error("Failed to send approval email to container creator: " . $e->getMessage());
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
                        Mail::to($securityUsers)->send(new \App\Mail\ContainerReadyForOnboarding($container));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send onboarding ready email to security team: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public function createDepartmentApprovals(ShipmentTransport $container)
    {
        // Create only the FIRST approval in sequence (warehouse)
        // Subsequent approvals will be created when previous ones are approved
        $firstDepartment = 'warehouse';

        // Check if warehouse approval already exists
        $existingApproval = ShipmentTransportApproval::where([
            'shipment_transport_id' => $container->id,
            'department' => $firstDepartment,
            'approval_type' => 'loading',
        ])->first();

        if (!$existingApproval) {
            // Create warehouse approval first
            $approval = ShipmentTransportApproval::create([
                'shipment_transport_id' => $container->id,
                'department' => $firstDepartment,
                'approval_type' => 'loading',
                'approval_status' => 'pending',
            ]);

            // Send notification only for warehouse
            $this->sendDepartmentApprovalEmails($container, [$firstDepartment]);
        }
    }

    private function createNextSequentialApproval(ShipmentTransport $container, string $approvedDepartment)
    {
        // Define the sequential order
        $sequence = ['warehouse', 'quality', 'shipping', 'security'];

        // Find the index of the approved department
        $currentIndex = array_search($approvedDepartment, $sequence);

        if ($currentIndex === false || $currentIndex >= count($sequence) - 1) {
            // Last in sequence or invalid department
            return;
        }

        // Get the next department
        $nextDepartment = $sequence[$currentIndex + 1];

        // Check if next approval already exists
        $existingApproval = ShipmentTransportApproval::where([
            'shipment_transport_id' => $container->id,
            'department' => $nextDepartment,
            'approval_type' => 'loading',
        ])->first();

        if (!$existingApproval) {
            // Create the next approval in sequence
            $approval = ShipmentTransportApproval::create([
                'shipment_transport_id' => $container->id,
                'department' => $nextDepartment,
                'approval_type' => 'loading',
                'approval_status' => 'pending',
            ]);

            // Send notification for the next department
            $this->sendDepartmentApprovalEmails($container, [$nextDepartment]);
        }
    }

    private function sendDepartmentApprovalEmails(ShipmentTransport $container, array $departments = null)
    {
        // Send notifications to department representatives that container is ready for approval
        $departmentsToNotify = $departments ? $departments : ['warehouse', 'shipping', 'quality', 'security'];

        foreach ($departmentsToNotify as $department) {
            // Find the approval record for this department
            $approval = ShipmentTransportApproval::where([
                'shipment_transport_id' => $container->id,
                'department' => $department,
                'approval_type' => 'loading',
            ])->first();

            if ($approval) {
                // Send Power Automate notifications
                $this->sendPowerAutomateNotification($container, $approval);

                // Send email as backup
                $users = $this->getDepartmentUsers($department);
                if ($users->isNotEmpty()) {
                    try {
                        Mail::to($users)->send(new ContainerReadyForDepartmentApproval($container, $department, 'loading'));
                    } catch (\Exception $e) {
                        // Log the error but don't fail the entire process
                        \Log::error("Failed to send approval email to {$department} department: " . $e->getMessage());
                    }
                }
            }
        }
    }

    private function sendPowerAutomateNotification(ShipmentTransport $container, ShipmentTransportApproval $approval)
    {
        try {
            // Get department users who can approve
            $departmentUsers = $this->getDepartmentUsers($approval->department);

            if ($departmentUsers->isEmpty()) {
                \Log::warning("No users found for department {$approval->department}, skipping Power Automate notifications");
                return;
            }

            $triggerUrl = config('services.power_automate.trigger_url');
            if (!$triggerUrl) {
                \Log::error("Power Automate trigger URL not configured");
                return;
            }

            // Collect all approver emails into an array for Power Automate to handle "first wins" logic
            $approverEmails = $departmentUsers->pluck('email')->toArray();

            $payload = [
                'approval_id' => $approval->id,
                'title' => $container->container_number ?: $container->transport_number,
                'description' => 'Container approval required for loading process',
                'approver_email' => $approverEmails,  // Array of all qualified emails
                //'department' => $approval->department   // Department info for Power Automate logic
            ];

            $response = \Illuminate\Support\Facades\Http::post($triggerUrl, $payload);

            if ($response->successful()) {
                \Log::info("Sent Power Automate notification to " . count($approverEmails) . " users for approval {$approval->id} in department {$approval->department}");
            } else {
                \Log::error("Failed to send Power Automate notification for approval {$approval->id}: " . $response->body());
            }

        } catch (\Exception $e) {
            \Log::error("Exception sending Power Automate notifications for approval {$approval->id}: " . $e->getMessage());
        }
    }

    public function approveFromEmail($approvalId)
    {
        $approval = ShipmentTransportApproval::findOrFail($approvalId);
        $user = auth()->user();

        // Check permissions
        if (!$user->hasPermissionTo('container.management_approve')) {
            return redirect('/container/dashboard')->with('error', 'Unauthorized to approve this container');
        }

        // Check if already approved
        if ($approval->approval_status === 'approved') {
            return redirect('/container/dashboard')->with('info', 'Container already approved');
        }

        $approval->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->checkAndUpdateContainerStatus($approval->shipmentTransport);

        return redirect('/container/dashboard')->with('success', 'Container approved successfully');
    }

    private function getDepartmentUsers($department)
    {
        // Return users with the specific permission
        return \App\Models\User::permission("container.{$department}_approve")->get();
    }

    public function receiveApprovalResult(Request $request)
    {
        $validated = $request->validate([
            'approval_id' => 'required|integer',
            'decision' => 'required|string|in:Approve,Reject',
            'by' => 'required|email',
            'name' => 'required|string',
            'time' => 'required|string'
        ]);

        $approval = ShipmentTransportApproval::findOrFail($validated['approval_id']);

        // Check if already processed
        if ($approval->approval_status !== 'pending') {
            return response()->json(['message' => 'Approval already processed'], 200);
        }

        // Find or create user who approved
        $user = \App\Models\User::firstOrCreate(
            ['email' => $validated['by']],
            ['name' => $validated['name']]
        );

        $approval->update([
            'approval_status' => $validated['decision'] === 'Approve' ? 'approved' : 'rejected',
            'approved_by' => $user->id,
            'approved_at' => $validated['time']
        ]);

        // For loading approvals, create the next approval in sequence if approved
        if ($approval->approval_type === 'loading' && $validated['decision'] === 'Approve') {
            $this->createNextSequentialApproval($approval->shipmentTransport, $approval->department);
        }

        // Broadcast real-time update
        broadcast(new \App\Events\ContainerStageUpdated($approval->shipmentTransport))->toOthers();

        // Check and update container status
        $this->checkAndUpdateContainerStatus($approval->shipmentTransport);

        return response()->json(['message' => 'Approval result processed successfully']);
    }

    public function getApprovalDetails($approvalId)
    {
        $user = auth()->user();
        $approval = ShipmentTransportApproval::with([
            'shipmentTransport',
            'shipmentTransport.inspection.answers.question',
            'shipmentTransport.photo'
        ])->findOrFail($approvalId);

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasPermissionTo('superadmin') && $approval->shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to approval details'], 403);
        }

        $details = [
            'approval' => $approval,
            'container' => $approval->shipmentTransport,
        ];

        // Add inspection details if it's an inspection approval
        if ($approval->approval_type === 'inspection' && $approval->shipmentTransport->inspection) {
            $inspection = $approval->shipmentTransport->inspection;
            $details['inspection'] = [
                'id' => $inspection->id,
                'status' => $inspection->status,
                'received_at' => $inspection->received_at,
                'inspected_at' => $inspection->inspected_at,
                'questions' => $inspection->answers->map(function ($answer) {
                    return [
                        'question' => $answer->question->question,
                        'passed' => $answer->passed,
                        'remarks' => $answer->remarks,
                        'photo_path' => $answer->photo_path,
                    ];
                }),
            ];
        }

        // Add loading photos if it's a loading approval
        if ($approval->approval_type === 'loading') {
            $photos = $approval->shipmentTransport->photo->filter(function ($photo) {
                return !in_array($photo->label, ['security_checking_photo']);
            })->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'label' => $photo->label,
                    'photo_path' => $photo->photo_path,
                    'taken_by' => $photo->taken_by,
                    'created_at' => $photo->created_at,
                ];
            });
            $details['loading_photos'] = $photos;
        }

        return response()->json($details);
    }
}
