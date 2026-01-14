<?php
namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ShipmentTransport;
use App\Models\ShippingRequirement;
use App\Mail\ContainerOnHold;
use App\Mail\ContainerReleased;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ShipmentTransportController extends Controller
{

    //get dashboard for shipment transport
    public function dashboard()
    {
        return Inertia::render('ManageContainer/Dashboard');
    }

    public function getStats()
    {
        $user = auth()->user();
        $query = ShipmentTransport::query();

        // Apply site filter unless user is superadmin
        if (!$user->hasPermissionTo('superadmin')) {
            $query->where('site_id', $user->site_id);
        }

        $totalContainers = $query->count();

        // Clone the base query for each stat calculation
        $containerTypes = (clone $query)
            ->selectRaw('transport_type, COUNT(*) as count')
            ->groupBy('transport_type')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->transport_type,
                    'total' => $item->count
                ];
            });

        // Status breakdown
        $statusStats = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->status),
                    'total' => $item->count
                ];
            });

        // Stage breakdown
        $stageStats = (clone $query)
            ->selectRaw('stage, COUNT(*) as count')
            ->groupBy('stage')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst(str_replace('_', ' ', $item->stage)),
                    'total' => $item->count
                ];
            });

        // Inspection status
        $inspectionStats = [
            [
                'name' => 'Pending Inspection',
                'total' => (clone $query)
                    ->where(function ($q) {
                        $q->whereDoesntHave('inspection')
                          ->orWhereHas('inspection', function ($subQ) {
                              $subQ->where('status', 'pending');
                          });
                    })->count()
            ],
            [
                'name' => 'Passed Inspection',
                'total' => (clone $query)
                    ->whereHas('inspection', function ($q) {
                        $q->where('status', 'passed');
                    })->count()
            ],
            [
                'name' => 'Failed Inspection',
                'total' => (clone $query)
                    ->whereHas('inspection', function ($q) {
                        $q->where('status', 'failed');
                    })->count()
            ]
        ];

        return response()->json([
            'totalContainers' => $totalContainers,
            'containerTypes' => $containerTypes,
            'statusStats' => $statusStats,
            'stageStats' => $stageStats,
            'inspectionStats' => $inspectionStats
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $limit = $request->input('limit', 50);
        $search = $request->input('search');
        $status = $request->input('status');

        $query = ShipmentTransport::with(['inspection', 'photo', 'holdBy']);

        // Apply site filter unless user is superadmin
        if (!$user->hasPermissionTo('superadmin')) {
            $query->where('site_id', $user->site_id);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transport_number', 'LIKE', "%{$search}%")
                    ->orWhere('transport_type', 'LIKE', "%{$search}%")
                    ->orWhere('sku_number', 'LIKE', "%{$search}%")
                    ->orWhere('model_project', 'LIKE', "%{$search}%")
                    ->orWhere('forwarder', 'LIKE', "%{$search}%")
                    ->orWhere('hauler', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Apply limit and get results
        $shipments = $query->latest()->take($limit)->get();

        return response()->json([
            'data' => $shipments,
        ]);
    }

    /**
     * Get shipments for visitor form selection.
     */
    public function getShipmentsForVisitor(Request $request)
    {
        $user = auth()->user();
        $siteId = $request->input('site_id');

        if (!$siteId) {
            return response()->json(['error' => 'Site ID is required'], 400);
        }

        // Check if user can access this site
        if (!$user->hasPermissionTo('superadmin') && $user->site_id != $siteId) {
            return response()->json(['error' => 'Unauthorized access to site'], 403);
        }

        $shipments = ShipmentTransport::where('site_id', $siteId)
            ->where('status', '!=', 'completed') // Don't show completed shipments
            ->select('id', 'transport_number', 'transport_type', 'sku_number', 'model_project')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $shipments
        ]);
    }

    /**
     * Validate container number for visitor registration.
     */
    public function validateContainerForVisitor(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'container_number' => 'required|string|regex:/^[A-Z]{4}\d{7}$/',
            'site_id' => 'required|integer|exists:sites,id',
        ]);

        // Check if user can access this site
        if (!$user->hasPermissionTo('superadmin') && $user->site_id != $validated['site_id']) {
            return response()->json([
                'valid' => false,
                'message' => 'Unauthorized access to site'
            ], 403);
        }

        // Find container by transport number
        $container = ShipmentTransport::where('transport_number', $validated['container_number'])
            ->where('site_id', $validated['site_id'])
            ->first();

        if (!$container) {
            return response()->json([
                'valid' => false,
                'message' => 'Container number not found'
            ]);
        }

        // Check if container stage is 'onboarding_ready'
        if ($container->stage !== 'onboarding_ready') {
            return response()->json([
                'valid' => false,
                'message' => 'Container is not ready for visitor registration'
            ]);
        }

        return response()->json([
            'valid' => true,
            'container' => [
                'id' => $container->id,
                'transport_number' => $container->transport_number,
                'stage' => $container->stage,
                'transport_type' => $container->transport_type,
                'sku_number' => $container->sku_number,
                'model_project' => $container->model_project,
            ],
            'message' => 'Container is valid and ready for visitor registration'
        ]);
    }

    /**
     * Get driver information for a shipment.
     */
    public function getDriverInfo(ShipmentTransport $shipmentTransport)
    {
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasPermissionTo('superadmin') && $shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        // Get the driver assigned to this shipment
        $driverAssignment = $shipmentTransport->shipmentTransportDrivers()->with('visitor')->first();

        if (!$driverAssignment || !$driverAssignment->visitor) {
            return response()->json([
                'driver' => null,
                'message' => 'No driver assigned to this shipment'
            ]);
        }

        $visitor = $driverAssignment->visitor;

        return response()->json([
            'driver' => [
                'visitor_name' => $visitor->visitor_name,
                'ic_number' => $visitor->ic_number,
                'passport' => $visitor->passport,
                'visitor_company' => $visitor->visitor_company,
                'vehicle_number' => $visitor->vehicle_number,
                'phone_number' => $visitor->phone_number,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $site_id = auth()->user()->site_id;

        // Get base validation rules
        $rules = [
            'transport_type' => 'required|string',
            'transport_number' => 'required|string',
            'sku_number' => 'required|string',
            'model_project' => 'required|string',
            'forwarder' => 'required|string',
            'country' => 'required|string',
            'work_order' => 'required|string',
            'hauler' => 'required|string',
            'driver_name' => 'nullable|string',
            'driver_id' => 'nullable|string',
            'high_security_seal' => 'nullable|string',
            'gps' => 'nullable|string',
            'fork_seal' => 'nullable|string',
            'temporary_seal' => 'nullable|string',
        ];

        // Check if transport type is Container - make high_security_seal required
        $transportType = $request->input('transport_type');
        if ($transportType === 'Container') {
            $rules['high_security_seal'] = 'required|string';
        }

        // Check if country requires seals
        $country = $request->input('country');
        if ($country) {
            $requirement = ShippingRequirement::where('destination', $country)->first();
            if ($requirement && $requirement->requires_seals) {
                $rules['gps'] = 'required|string';
                $rules['fork_seal'] = 'required|string';
            }
        }

        $validated = $request->validate($rules);

        $shipment = ShipmentTransport::create(array_merge($validated, [
            'site_id' => $site_id,
            'date' => now()->toDateString(),
            'status' => 'pending',
            'stage' => 'container_checking',
            'created_by' => auth()->id(),
        ]));

        return response()->json([
            'message' => 'Shipment Transport created successfully.',
            'data' => $shipment,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShipmentTransport $shipmentTransport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShipmentTransport $shipmentTransport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShipmentTransport $shipmentTransport)
    {
        //
    }

    /**
     * Get shipping requirements for a country.
     */
    public function getCountryRequirements(Request $request)
    {
        $country = $request->query('country');

        if (!$country) {
            return response()->json(['error' => 'Country parameter is required'], 400);
        }

        $requirement = ShippingRequirement::where('destination', $country)->first();

        if (!$requirement) {
            return response()->json(['error' => 'Requirements not found for this country'], 404);
        }

        return response()->json([
            'data' => $requirement
        ]);
    }

    /**
     * Get all shipping requirements for management page.
     */
    public function getShippingRequirements(Request $request)
    {
        $query = ShippingRequirement::with(['changes' => function($q) {
            $q->where('status', 'pending')->latest();
        }]);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('region', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('risk_level', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['region', 'destination', 'risk_level', 'strength_mm', 'requires_seals', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $requirements = $query->paginate($perPage);

        // Add approval status information to each requirement
        $requirements->getCollection()->transform(function ($requirement) {
            $pendingChange = $requirement->changes->first();

            return array_merge($requirement->toArray(), [
                'change_requested_at' => $pendingChange ? $pendingChange->created_at : null,
                'approved_at' => $requirement->approved_at,
            ]);
        });

        return response()->json($requirements);
    }



    /**
     * Hold a container with reason.
     */
    public function hold(Request $request, ShipmentTransport $shipmentTransport)
    {
        $user = auth()->user();

        // Check if user has quality permissions
        if (!$user->hasPermissionTo('container.quality_approve')) {
            return response()->json(['message' => 'Unauthorized. Quality department access required.'], 403);
        }

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasPermissionTo('superadmin') && $shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        // Check if container status allows holding
        if ($shipmentTransport->status !== 'in_progress') {
            return response()->json(['message' => 'Only containers that are in progress can be put on hold'], 400);
        }

        // Check if already on hold
        if ($shipmentTransport->is_on_hold) {
            return response()->json(['message' => 'Container is already on hold'], 400);
        }

        $validated = $request->validate([
            'hold_reason' => 'required|string|max:1000',
        ]);

        $shipmentTransport->update([
            'is_on_hold' => true,
            'hold_reason' => $validated['hold_reason'],
            'hold_by' => $user->id,
            'hold_at' => now(),
        ]);

        // Send email notifications
        $this->sendHoldNotifications($shipmentTransport, $user);

        return response()->json([
            'message' => 'Container has been put on hold successfully',
            'data' => $shipmentTransport->fresh()
        ]);
    }

    /**
     * Release a container from hold.
     */
    public function release(Request $request, ShipmentTransport $shipmentTransport)
    {
        $user = auth()->user();

        // Check if user has quality permissions
        if (!$user->hasPermissionTo('container.quality_approve')) {
            return response()->json(['message' => 'Unauthorized. Quality department access required.'], 403);
        }

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasPermissionTo('superadmin') && $shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        // Check if not on hold
        if (!$shipmentTransport->is_on_hold) {
            return response()->json(['message' => 'Container is not on hold'], 400);
        }

        $shipmentTransport->update([
            'is_on_hold' => false,
            'hold_reason' => null,
            'hold_by' => null,
            'hold_at' => null,
        ]);

        // Send email notifications
        $this->sendReleaseNotifications($shipmentTransport, $user);

        return response()->json([
            'message' => 'Container has been released from hold successfully',
            'data' => $shipmentTransport->fresh()
        ]);
    }

    /**
     * Send email notifications when container is put on hold.
     */
    private function sendHoldNotifications(ShipmentTransport $container, $user)
    {
        // Send email to container creator
        try {
            Mail::to($container->createdBy->email)->send(new ContainerOnHold($container, $user));
        } catch (\Exception $e) {
            \Log::error("Failed to send hold notification to container creator: " . $e->getMessage());
        }

        // Send email to department users who might be affected
        $departmentsToNotify = ['warehouse', 'shipping', 'security'];
        foreach ($departmentsToNotify as $department) {
            $users = $this->getDepartmentUsers($department);
            if ($users->isNotEmpty()) {
                try {
                    Mail::to($users)->send(new ContainerOnHold($container, $user));
                } catch (\Exception $e) {
                    \Log::error("Failed to send hold notification to {$department} department: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Handle change requests for create, update, and delete operations.
     */
    public function requestChange(Request $request)
    {
        $user = auth()->user();

        // Check if user has shipping access
        if (!$user->hasPermissionTo('container.shipping.access')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'change_type' => 'required|in:create,update,delete',
            'shipping_requirement_id' => 'required_if:change_type,update,delete|exists:shipping_requirements,id',
            'proposed_data.region' => 'required_if:change_type,create,update|string',
            'proposed_data.destination' => 'required_if:change_type,create,update|string',
            'proposed_data.risk_level' => 'required_if:change_type,create,update|string',
            'proposed_data.strength_mm' => 'required_if:change_type,create,update|string',
            'proposed_data.requires_seals' => 'required_if:change_type,create,update',
            'attachment' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        // Store attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('shipping-attachments', 'public');
        }

        $changeRequestData = [
            'requested_by' => $user->id,
            'change_type' => $validated['change_type'],
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ];

        if ($validated['change_type'] === 'create') {
            // For create operations, no shipping_requirement_id needed
            $changeRequestData['proposed_data'] = [
                'region' => $validated['proposed_data']['region'],
                'destination' => $validated['proposed_data']['destination'],
                'risk_level' => $validated['proposed_data']['risk_level'],
                'strength_mm' => $validated['proposed_data']['strength_mm'],
                'requires_seals' => filter_var($validated['proposed_data']['requires_seals'], FILTER_VALIDATE_BOOLEAN),
            ];
        } elseif ($validated['change_type'] === 'update') {
            // For update operations
            $shippingRequirement = ShippingRequirement::findOrFail($validated['shipping_requirement_id']);

            // Check if at least one field has changed
            $hasChanges = false;
            $currentData = $shippingRequirement->only(['region', 'destination', 'risk_level', 'strength_mm', 'requires_seals']);

            foreach (['region', 'destination', 'risk_level', 'strength_mm', 'requires_seals'] as $field) {
                $proposedValue = $field === 'requires_seals' ?
                    filter_var($validated['proposed_data'][$field], FILTER_VALIDATE_BOOLEAN) :
                    $validated['proposed_data'][$field];

                if (isset($proposedValue) && $proposedValue != $currentData[$field]) {
                    $hasChanges = true;
                    break;
                }
            }

            if (!$hasChanges) {
                return response()->json(['message' => 'No changes detected. Please modify at least one field.'], 422);
            }

            $changeRequestData['shipping_requirement_id'] = $shippingRequirement->id;
            $changeRequestData['original_data'] = $currentData;
            $changeRequestData['proposed_data'] = [
                'region' => $validated['proposed_data']['region'],
                'destination' => $validated['proposed_data']['destination'],
                'risk_level' => $validated['proposed_data']['risk_level'],
                'strength_mm' => $validated['proposed_data']['strength_mm'],
                'requires_seals' => filter_var($validated['proposed_data']['requires_seals'], FILTER_VALIDATE_BOOLEAN),
            ];

            // Set status to pending
            $shippingRequirement->update(['status' => 'pending']);
        } elseif ($validated['change_type'] === 'delete') {
            // For delete operations
            $shippingRequirement = ShippingRequirement::findOrFail($validated['shipping_requirement_id']);
            $changeRequestData['shipping_requirement_id'] = $shippingRequirement->id;
            $changeRequestData['original_data'] = $shippingRequirement->only(['region', 'destination', 'risk_level', 'strength_mm', 'requires_seals']);

            // Set status to pending
            $shippingRequirement->update(['status' => 'pending']);
        }

        // Create change request
        $changeRequest = \App\Models\ShippingRequirementChange::create($changeRequestData);

        // Fire event for real-time updates
        \Log::info('Firing ShippingRequirementChangeRequested event', ['change_request_id' => $changeRequest->id]);
        \App\Events\ShippingRequirementChangeRequested::dispatch($changeRequest);

        $actionMessage = match($validated['change_type']) {
            'create' => 'creation',
            'update' => 'change',
            'delete' => 'deletion',
        };

        return response()->json([
            'message' => "Shipping requirement {$actionMessage} request submitted for approval",
            'data' => $changeRequest->load('requester'),
        ]);
    }

    /**
     * Request update to shipping requirement (creates change request).
     */
    public function updateShippingRequirement(Request $request, ShippingRequirement $shippingRequirement)
    {
        $user = auth()->user();

        // Check if user has shipping access
        if (!$user->hasPermissionTo('container.shipping.access')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'region' => 'required|string',
            'destination' => 'required|string',
            'risk_level' => 'required|string',
            'strength_mm' => 'required|string',
            'requires_seals' => 'required',
            'attachment' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        // Convert requires_seals to boolean
        $validated['requires_seals'] = filter_var($validated['requires_seals'], FILTER_VALIDATE_BOOLEAN);

        // Check if at least one field has changed
        $hasChanges = false;
        $currentData = $shippingRequirement->only(['region', 'destination', 'risk_level', 'strength_mm', 'requires_seals']);

        foreach (['region', 'destination', 'risk_level', 'strength_mm', 'requires_seals'] as $field) {
            if (isset($validated[$field]) && $validated[$field] != $currentData[$field]) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            return response()->json(['message' => 'No changes detected. Please modify at least one field.'], 422);
        }

        // Store attachment if provided
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('shipping-attachments', 'public');
        }

        // Create change request
        $changeRequest = \App\Models\ShippingRequirementChange::create([
            'shipping_requirement_id' => $shippingRequirement->id,
            'requested_by' => $user->id,
            'change_type' => 'update',
            'original_data' => $shippingRequirement->only(['region', 'destination', 'risk_level', 'strength_mm', 'requires_seals']),
            'proposed_data' => [
                'region' => $validated['region'],
                'destination' => $validated['destination'],
                'risk_level' => $validated['risk_level'],
                'strength_mm' => $validated['strength_mm'],
                'requires_seals' => $validated['requires_seals'],
            ],
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        // Fire event for real-time updates
        \Log::info('Firing ShippingRequirementChangeRequested event', ['change_request_id' => $changeRequest->id]);
        \App\Events\ShippingRequirementChangeRequested::dispatch($changeRequest);

        return response()->json([
            'message' => 'Change request submitted successfully. Awaiting approval.',
            'data' => $changeRequest->load('requester'),
        ]);
    }

    /**
     * Get all change requests (for approval page).
     */
    public function getPendingChangeRequests(Request $request)
    {
        $user = auth()->user();

        // Check if user has shipping approve permission
        if (!$user->hasPermissionTo('container.shipping.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = \App\Models\ShippingRequirementChange::with(['shippingRequirement', 'requester', 'reviewer']);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('shippingRequirement', function ($subQ) use ($search) {
                    $subQ->where('destination', 'like', "%{$search}%")
                         ->orWhere('region', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'change_type'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $changeRequests = $query->paginate($perPage);

        return response()->json($changeRequests);
    }

    /**
     * Approve a change request.
     */
    public function approveChangeRequest(Request $request, $changeRequestId)
    {
        $user = auth()->user();

        // Check if user has shipping approve permission
        if (!$user->hasPermissionTo('container.shipping.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $changeRequest = \App\Models\ShippingRequirementChange::findOrFail($changeRequestId);

        if ($changeRequest->status !== 'pending') {
            return response()->json(['message' => 'Change request has already been processed'], 400);
        }

        $validated = $request->validate([
            'review_comments' => 'nullable|string|max:1000',
        ]);

        // Process the change based on type
        $shippingRequirement = $changeRequest->shippingRequirement;

        if ($changeRequest->change_type === 'update') {
            // Apply the update
            $shippingRequirement->update(array_merge($changeRequest->proposed_data, [
                'last_updated_by' => $changeRequest->requested_by,
                'attachment_path' => $changeRequest->attachment_path,
                'change_requested_at' => $changeRequest->created_at,
                'requires_approval' => false,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'status' => 'normal', // Set status back to normal
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
            'review_comments' => $validated['review_comments'] ?? null,
        ]);

        // Fire event for real-time updates
        \App\Events\ShippingRequirementChangeProcessed::dispatch($changeRequest, $shippingRequirement, 'approved');

        return response()->json([
            'message' => 'Change request approved successfully.',
            'data' => $changeRequest->load(['shippingRequirement', 'requester', 'reviewer']),
        ]);
    }

    /**
     * Reject a change request.
     */
    public function rejectChangeRequest(Request $request, $changeRequestId)
    {
        $user = auth()->user();

        // Check if user has shipping approve permission
        if (!$user->hasPermissionTo('container.shipping.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $changeRequest = \App\Models\ShippingRequirementChange::findOrFail($changeRequestId);

        if ($changeRequest->status !== 'pending') {
            return response()->json(['message' => 'Change request has already been processed'], 400);
        }

        $validated = $request->validate([
            'review_comments' => 'required|string|max:1000',
        ]);

        // Update the change request
        $changeRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_comments' => $validated['review_comments'],
        ]);

        // Set status back to normal for the shipping requirement
        if ($changeRequest->shippingRequirement) {
            $changeRequest->shippingRequirement->update(['status' => 'normal']);
        }

        // Fire event for real-time updates
        \App\Events\ShippingRequirementChangeProcessed::dispatch($changeRequest, $changeRequest->shippingRequirement, 'rejected');

        return response()->json([
            'message' => 'Change request rejected successfully.',
            'data' => $changeRequest->load(['shippingRequirement', 'requester', 'reviewer']),
        ]);
    }

    /**
     * Send email notifications when container is released from hold.
     */
    private function sendReleaseNotifications(ShipmentTransport $container, $user)
    {
        // Send email to container creator
        try {
            Mail::to($container->createdBy->email)->send(new ContainerReleased($container, $user));
        } catch (\Exception $e) {
            \Log::error("Failed to send release notification to container creator: " . $e->getMessage());
        }

        // Send email to department users who might be affected
        $departmentsToNotify = ['warehouse', 'shipping', 'security'];
        foreach ($departmentsToNotify as $department) {
            $users = $this->getDepartmentUsers($department);
            if ($users->isNotEmpty()) {
                try {
                    Mail::to($users)->send(new ContainerReleased($container, $user));
                } catch (\Exception $e) {
                    \Log::error("Failed to send release notification to {$department} department: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Get users for a specific department.
     */
    private function getDepartmentUsers($department)
    {
        // Return users with the specific permission
        return \App\Models\User::permission("container.{$department}_approve")->get();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShipmentTransport $shipmentTransport)
    {
        //
    }
}
