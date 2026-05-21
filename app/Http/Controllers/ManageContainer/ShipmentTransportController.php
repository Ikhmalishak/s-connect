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
use Mpdf\Mpdf;

class ShipmentTransportController extends Controller
{

    //get dashboard for shipment transport
    public function dashboard()
    {
        return Inertia::render('ManageContainer/Dashboard');
    }

    public function getStats(Request $request)
    {
        $user = auth()->user();
        $query = ShipmentTransport::query();

        // Apply site filter if specified
        $siteId = $request->input('site_id');
        if ($siteId && $siteId !== 'all') {
            $query->where('site_id', $siteId);
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
        $siteId = $request->input('site_id');

        $query = ShipmentTransport::with(['inspection', 'photo', 'holdBy', 'site']);

        //if skpbp only see skpbp
        // Apply site filter if specified, otherwise show all sites
        if ($user->site_id === 6) {
            $query->where('site_id', $user->site_id);
        } else {
            if ($siteId && $siteId !== 'all') {
                $query->where('site_id', $siteId);
            }
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

    public function getShipmentTransportInfoById(ShipmentTransport $shipmentTransport)
    {
        return $shipmentTransport;
    }

    /**
     * Validate container number for visitor registration (public access for visitor form).
     * Only allows proceeding if container exists and is ready for pickup (stage = 'onboarding_ready').
     */
    public function validateContainerForVisitor(Request $request)
    {
        $validated = $request->validate([
            'container_number' => 'required|string',
            'site_id' => 'required|integer|exists:sites,id',
        ]);

        // Find container by transport number (exact match only)
        $container = ShipmentTransport::where('transport_number', $validated['container_number'])
            ->where('site_id', $validated['site_id'])
            ->first();

        if (!$container) {
            // Container doesn't exist - cannot proceed
            return response()->json([
                'valid' => false,
                'message' => 'Container number not found in system'
            ]);
        }

        // Check if container stage is 'onboarding_ready'
        // if ($container->stage !== 'onboarding_ready') {
        //     // Container exists but not ready for pickup - cannot proceed
        //     return response()->json([
        //         'valid' => false,
        //         'message' => 'Container is not ready for visitor registration (current stage: ' . str_replace('_', ' ', $container->stage) . ')'
        //     ]);
        // }

        // Container exists and is ready for pickup - success
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
     * Get driver and container information for security checking.
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

        $driver = null;
        if ($driverAssignment && $driverAssignment->visitor) {
            $visitor = $driverAssignment->visitor;
            $driver = [
                'visitor_name' => $visitor->visitor_name,
                'ic_number' => $visitor->ic_number,
                'passport' => $visitor->passport,
                'visitor_company' => $visitor->visitor_company,
                'vehicle_number' => $visitor->vehicle_number,
                'phone_number' => $visitor->phone_number,
            ];
        }

        // Get container details
        $container = [
            'transport_number' => $shipmentTransport->transport_number,
            'transport_type' => $shipmentTransport->transport_type,
            'sku_number' => $shipmentTransport->sku_number,
            'model_project' => $shipmentTransport->model_project,
            'high_security_seal_sn' => $shipmentTransport->high_security_seal_sn,
            'fork_seal_sn' => $shipmentTransport->fork_seal_sn,
            'fork_seal_size' => $shipmentTransport->fork_seal_size,
            'temporary_seal_sn' => $shipmentTransport->temporary_seal_sn,
            'inside_gps_sn' => $shipmentTransport->inside_gps_sn,
            'outside_gps_sn' => $shipmentTransport->outside_gps_sn,
            'country' => $shipmentTransport->country,
            'forwarder' => $shipmentTransport->forwarder,
            'hauler' => $shipmentTransport->hauler,
        ];

        return response()->json([
            'driver' => $driver,
            'container' => $container,
            'message' => $driver ? 'Driver and container information retrieved' : 'Container information retrieved (no driver assigned)'
        ]);
    }

    public function store(Request $request)
    {
        // Get base validation rules
        $rules = [
            'site_id' => 'required|exists:sites,id',
            'transport_type' => 'required|string',
            'size' => 'nullable|string|in:20GP,40HC',
            'transport_number' => 'required|string',
            'sku_number' => 'required|string',
            'model_project' => 'required|string',
            'forwarder' => 'required|string',
            'country' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    // Only validate country for Container transport type
                    $transportType = $request->input('transport_type');
                    if ($transportType !== 'Container') {
                        return; // Skip validation for non-container types
                    }

                    // Check if country exists in shipping requirements
                    $requirement = ShippingRequirement::whereRaw('LOWER(destination) = ?', [strtolower($value)])->first();
                    if (!$requirement) {
                        $requirement = ShippingRequirement::whereRaw('LOWER(destination) LIKE ?', ['%' . strtolower($value) . '%'])->first();
                    }

                    if (!$requirement) {
                        $availableCountries = ShippingRequirement::pluck('destination')->toArray();
                        $fail("Country '{$value}' is not found in shipping requirements. Available destinations: " . implode(', ', $availableCountries));
                    }
                },
            ],
            'work_order' => 'required|string',
            'hauler' => 'required|string',
            'driver_name' => 'nullable|string',
            'driver_id' => 'nullable|string',
            'high_security_seal_sn' => 'nullable|string',
            'inside_gps_sn' => 'nullable|string',
            'outside_gps_sn' => 'nullable|string',
            'fork_seal_sn' => 'nullable|string',
            'fork_seal_size' => 'nullable|string',
            'temporary_seal_sn' => 'nullable|string',
        ];

        // Check if transport type is Container - make high_security_seal_sn and size required
        $transportType = $request->input('transport_type');
        if ($transportType === 'Container') {
            $rules['size'] = 'required|string|in:20GP,40HC';
            $rules['high_security_seal_sn'] = 'required|string';
        }

        // Check country-specific requirements (only for containers)
        if ($transportType === 'Container') {
            $country = $request->input('country');
            if ($country) {
                $requirement = ShippingRequirement::whereRaw('LOWER(destination) = ?', [strtolower($country)])->first();
                if (!$requirement) {
                    $requirement = ShippingRequirement::whereRaw('LOWER(destination) LIKE ?', ['%' . strtolower($country) . '%'])->first();
                }

                if ($requirement) {
                    // Require fork seal only if country requires it
                    if ($requirement->requires_fork_seal) {
                        $rules['fork_seal_sn'] = 'required|string';
                    }

                    // Require GPS for high/medium risk countries
                    if (in_array($requirement->risk_level, ['high', 'medium'])) {
                        $rules['inside_gps_sn'] = 'required|string';
                    }
                } else {
                    // Country not found in requirements - this will be caught by the country validation rule
                }
            }
        }

        $validated = $request->validate($rules);

        $shipment = ShipmentTransport::create(array_merge($validated, [
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

    public function update(Request $request, ShipmentTransport $shipmentTransport)
    {
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasAnyPermission(['superadmin', 'container.shipping.access']) && $shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        // Get base validation rules
        $rules = [
            'site_id' => 'required|exists:sites,id',
            'transport_type' => 'required|string',
            'size' => 'nullable|string|in:20GP,40HC',
            'transport_number' => 'required|string',
            'sku_number' => 'required|string',
            'model_project' => 'required|string',
            'forwarder' => 'required|string',
            'country' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    // Only validate country for Container transport type
                    $transportType = $request->input('transport_type');
                    if ($transportType !== 'Container') {
                        return; // Skip validation for non-container types
                    }

                    // Check if country exists in shipping requirements
                    $requirement = ShippingRequirement::whereRaw('LOWER(destination) = ?', [strtolower($value)])->first();
                    if (!$requirement) {
                        $requirement = ShippingRequirement::whereRaw('LOWER(destination) LIKE ?', ['%' . strtolower($value) . '%'])->first();
                    }

                    if (!$requirement) {
                        $availableCountries = ShippingRequirement::pluck('destination')->toArray();
                        $fail("Country '{$value}' is not found in shipping requirements. Available destinations: " . implode(', ', $availableCountries));
                    }
                },
            ],
            'work_order' => 'required|string',
            'hauler' => 'required|string',
            'driver_name' => 'nullable|string',
            'driver_id' => 'nullable|string',
            'high_security_seal_sn' => 'nullable|string',
            'inside_gps_sn' => 'nullable|string',
            'outside_gps_sn' => 'nullable|string',
            'fork_seal_sn' => 'nullable|string',
            'fork_seal_size' => 'nullable|string',
            'temporary_seal_sn' => 'nullable|string',
        ];

        // Check if transport type is Container - make high_security_seal_sn and size required
        $transportType = $request->input('transport_type');
        if ($transportType === 'Container') {
            $rules['size'] = 'required|string|in:20GP,40HC';
            $rules['high_security_seal_sn'] = 'required|string';
        }

        // Check country-specific requirements (only for containers)
        if ($transportType === 'Container') {
            $country = $request->input('country');
            if ($country) {
                $requirement = ShippingRequirement::whereRaw('LOWER(destination) = ?', [strtolower($country)])->first();
                if (!$requirement) {
                    $requirement = ShippingRequirement::whereRaw('LOWER(destination) LIKE ?', ['%' . strtolower($country) . '%'])->first();
                }

                if ($requirement) {
                    // Require fork seal only if country requires it
                    if ($requirement->requires_fork_seal) {
                        $rules['fork_seal_sn'] = 'required|string';
                    }

                    // Require GPS for high/medium risk countries
                    if (in_array($requirement->risk_level, ['high', 'medium'])) {
                        $rules['inside_gps_sn'] = 'required|string';
                    }
                }
            }
        }

        $validated = $request->validate($rules);

        $shipmentTransport->update($validated);

        return response()->json([
            'message' => 'Shipment Transport updated successfully.',
            'data' => $shipmentTransport->fresh(),
        ]);
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

        // Try exact match first (case-insensitive)
        $requirement = ShippingRequirement::whereRaw('LOWER(destination) = ?', [strtolower($country)])->first();

        // If no exact match, try partial match
        if (!$requirement) {
            $requirement = ShippingRequirement::whereRaw('LOWER(destination) LIKE ?', ['%' . strtolower($country) . '%'])->first();
        }

        if (!$requirement) {
            return response()->json(['error' => 'Requirements not found for this country. Available destinations: ' . implode(', ', ShippingRequirement::pluck('destination')->toArray())], 404);
        }

        return response()->json([
            'data' => $requirement
        ]);
    }

    /**
     * Get required photos for a shipment transport based on its configuration.
     */
    public function getRequiredPhotos(ShipmentTransport $shipmentTransport)
    {
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasPermissionTo('superadmin') && $shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        $requiredPhotos = $this->determineRequiredPhotos($shipmentTransport);

        return response()->json([
            'data' => $requiredPhotos
        ]);
    }

    /**
     * Determine which photos are required based on shipment transport configuration.
     */
    private function determineRequiredPhotos(ShipmentTransport $shipmentTransport)
    {
        // Always required photos
        $requiredPhotos = [
            ['key' => 'pallet_condition_photo', 'label' => 'Pallet Condition'],
            ['key' => 'pallet_label_photo', 'label' => 'Pallet Label'],
            ['key' => 'empty_container_photo', 'label' => 'Empty Container'],
            ['key' => 'half_loaded_photo', 'label' => 'Half Loaded'],
            ['key' => 'one_side_door_closed_with_container_number_photo', 'label' => 'Door Closed'],
            ['key' => 'complete_loaded_photo', 'label' => 'Complete Loaded'],
            ['key' => 'container_full_seal_photo', 'label' => 'Container Full Seal'],
            ['key' => 'container_truck_photo', 'label' => 'Container Truck'],
        ];

        // GPS photos - required if GPS serial numbers are present
        if (!empty($shipmentTransport->inside_gps_sn) || !empty($shipmentTransport->outside_gps_sn)) {
            $requiredPhotos = array_merge($requiredPhotos, [
                ['key' => 'gps_photo_before_installation', 'label' => 'GPS Before Installation'],
                ['key' => 'inside_gps_photo', 'label' => 'Inside GPS'],
                ['key' => 'outside_gps_photo', 'label' => 'Outside GPS'],
            ]);
        }

        // GPS photos - required if GPS serial numbers are present
        if (!empty($shipmentTransport->fork_seal_sn)) {
            $requiredPhotos = array_merge($requiredPhotos, [
                ['key' => 'fork_seal_photo', 'label' => 'Fork Seal Photo'],
            ]);
        }

        // Seal photos - required if seal serial numbers are present
        if (
            !empty($shipmentTransport->high_security_seal_sn) ||
            !empty($shipmentTransport->fork_seal_sn) ||
            !empty($shipmentTransport->temporary_seal_sn)
        ) {
            $requiredPhotos = array_merge($requiredPhotos, [
                ['key' => 'security_seal_photo', 'label' => 'Security Seal'],
            ]);
        }

        return $requiredPhotos;
    }

    /**
     * Get all shipping requirements for management page.
     */
    public function getShippingRequirements(Request $request)
    {
        $query = ShippingRequirement::with([
            'changes' => function ($q) {
                $q->where('status', 'pending')->latest();
            }
        ]);

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

        if (in_array($sortBy, ['region', 'destination', 'risk_level', 'strength', 'requires_gps', 'created_at'])) {
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
        if (!$user->hasPermissionTo('container.quality.access')) {
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

        // Send email notifications - DISABLED: Using Teams instead
        // $this->sendHoldNotifications($shipmentTransport, $user);

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
        if (!$user->hasPermissionTo('container.quality.access')) {
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

        // Send email notifications - DISABLED: Using Teams instead
        // $this->sendReleaseNotifications($shipmentTransport, $user);

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
            'proposed_data.region' => 'required_if:change_type,create|string',
            'proposed_data.destination' => 'required_if:change_type,create|string',
            'proposed_data.risk_level' => 'required_if:change_type,create|string',
            'proposed_data.strength' => 'nullable|string',
            'proposed_data.requires_fork_seal' => 'nullable|boolean',
            'proposed_data.requires_gps' => 'nullable|boolean',
            'attachment' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        // Custom validation: strength is required only if requires_fork_seal is true
        // For update operations, check the proposed_data from the change request
        if ($validated['change_type'] === 'update') {
            $proposedData = $validated['proposed_data'] ?? [];
            $requiresForkSeal = isset($proposedData['requires_fork_seal']) ? filter_var($proposedData['requires_fork_seal'], FILTER_VALIDATE_BOOLEAN) : false;
            if ($requiresForkSeal && empty($proposedData['strength'])) {
                return response()->json(['message' => 'Strength is required when fork seal is required'], 422);
            }
        }

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
                'region' => $validated['proposed_data']['region'] ?? null,
                'destination' => $validated['proposed_data']['destination'] ?? null,
                'risk_level' => $validated['proposed_data']['risk_level'] ?? null,
                'strength' => $validated['proposed_data']['strength'] ?? null,
                'requires_fork_seal' => isset($validated['proposed_data']['requires_fork_seal']) ? filter_var($validated['proposed_data']['requires_fork_seal'], FILTER_VALIDATE_BOOLEAN) : false,
                'requires_gps' => isset($validated['proposed_data']['requires_gps']) ? filter_var($validated['proposed_data']['requires_gps'], FILTER_VALIDATE_BOOLEAN) : false,
            ];
        } elseif ($validated['change_type'] === 'update') {
            // For update operations
            $shippingRequirement = ShippingRequirement::findOrFail($validated['shipping_requirement_id']);

            // Check if at least one field has been provided for update
            $providedFields = array_filter($validated['proposed_data'], function ($value) {
                return $value !== null && $value !== '';
            });

            if (empty($providedFields)) {
                return response()->json(['message' => 'No changes detected. Please modify at least one field.'], 422);
            }

            // Check if at least one provided field is actually different from current
            $hasChanges = false;
            $currentData = $shippingRequirement->only(['region', 'destination', 'risk_level', 'strength', 'requires_fork_seal', 'requires_gps']);

            foreach ($providedFields as $field => $proposedValue) {
                $currentValue = $currentData[$field];
                $compareValue = in_array($field, ['requires_gps', 'requires_fork_seal']) ?
                    filter_var($proposedValue, FILTER_VALIDATE_BOOLEAN) :
                    $proposedValue;

                if ($compareValue != $currentValue) {
                    $hasChanges = true;
                    break;
                }
            }

            if (!$hasChanges) {
                return response()->json(['message' => 'No actual changes detected. The provided values are the same as current values.'], 422);
            }

            $changeRequestData['shipping_requirement_id'] = $shippingRequirement->id;
            $changeRequestData['original_data'] = $currentData;
            // Get the raw input data to ensure we capture all fields
            $proposedData = $request->input('proposed_data', []);

            $changeRequestData['proposed_data'] = [
                'region' => $proposedData['region'] ?? null,
                'destination' => $proposedData['destination'] ?? null,
                'risk_level' => $proposedData['risk_level'] ?? null,
                'strength' => $proposedData['strength'] ?? null,
                'requires_fork_seal' => isset($proposedData['requires_fork_seal']) ? filter_var($proposedData['requires_fork_seal'], FILTER_VALIDATE_BOOLEAN) : false,
                'requires_gps' => isset($proposedData['requires_gps']) ? filter_var($proposedData['requires_gps'], FILTER_VALIDATE_BOOLEAN) : false,
            ];

            // Set status to pending
            $shippingRequirement->update(['status' => 'pending']);
        } elseif ($validated['change_type'] === 'delete') {
            // For delete operations
            $shippingRequirement = ShippingRequirement::findOrFail($validated['shipping_requirement_id']);
            $changeRequestData['shipping_requirement_id'] = $shippingRequirement->id;
            $changeRequestData['original_data'] = $shippingRequirement->only(['region', 'destination', 'risk_level', 'strength', 'requires_fork_seal', 'requires_gps']);

            // Set status to pending
            $shippingRequirement->update(['status' => 'pending']);
        }

        // Create change request
        $changeRequest = \App\Models\ShippingRequirementChange::create($changeRequestData);

        // Send Power Automate notification directly with change request ID as approval ID
        $this->sendShippingRequirementApprovalNotification($changeRequest);

        // Fire event for real-time updates
        \Log::info('Firing ShippingRequirementChangeRequested event', ['change_request_id' => $changeRequest->id]);
        \App\Events\ShippingRequirementChangeRequested::dispatch($changeRequest);

        $actionMessage = match ($validated['change_type']) {
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
            'region' => 'nullable|string',
            'destination' => 'nullable|string',
            'risk_level' => 'nullable|string',
            'strength' => 'nullable|string',
            'requires_fork_seal' => 'nullable|boolean',
            'requires_gps' => 'nullable|boolean',
            'attachment' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        // Convert boolean fields
        $validated['requires_fork_seal'] = filter_var($validated['requires_fork_seal'], FILTER_VALIDATE_BOOLEAN);
        $validated['requires_gps'] = filter_var($validated['requires_gps'], FILTER_VALIDATE_BOOLEAN);

        // Check if at least one field has changed
        $hasChanges = false;
        $currentData = $shippingRequirement->only(['region', 'destination', 'risk_level', 'strength', 'requires_fork_seal', 'requires_gps']);

        foreach (['region', 'destination', 'risk_level', 'strength', 'requires_fork_seal', 'requires_gps'] as $field) {
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
            'original_data' => $shippingRequirement->only(['region', 'destination', 'risk_level', 'strength', 'requires_fork_seal', 'requires_gps']),
            'proposed_data' => [
                'region' => $validated['region'],
                'destination' => $validated['destination'],
                'risk_level' => $validated['risk_level'],
                'strength' => $validated['strength'],
                'requires_fork_seal' => $validated['requires_fork_seal'],
                'requires_gps' => $validated['requires_gps'],
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

        if ($changeRequest->change_type === 'create') {
            // Create the new shipping requirement
            $shippingRequirement = ShippingRequirement::create(array_merge($changeRequest->proposed_data, [
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
     * Send Power Automate notification for shipping requirement change approval.
     */
    private function sendShippingRequirementApprovalNotification(\App\Models\ShippingRequirementChange $changeRequest)
    {
        try {
            // Get shipping department users who can approve
            $shippingUsers = \App\Models\User::permission('container.shipping.approve')->get();

            if ($shippingUsers->isEmpty()) {
                \Illuminate\Support\Facades\Log::warning("No shipping users found for requirement change approval notifications");
                return;
            }

            $triggerUrl = config('services.power_automate.shipping_trigger_url');
            if (!$triggerUrl) {
                \Illuminate\Support\Facades\Log::error("Power Automate shipping trigger URL not configured");
                return;
            }

            // Collect all shipping approver emails
            $approverEmails = $shippingUsers->pluck('email')->toArray();

            // Create title based on change type and destination
            $destination = $changeRequest->shippingRequirement ? $changeRequest->shippingRequirement->destination : $changeRequest->proposed_data['destination'];
            $changeType = ucfirst($changeRequest->change_type);
            $title = "Shipping Requirement {$changeType} - {$destination}";

            $payload = [
                'approval_id' => $changeRequest->id, // Use change request ID directly
                'title' => $title,
                'description' => 'Shipping requirement change requires shipping department approval',
                'approver_email' => $approverEmails,
            ];

            $response = \Illuminate\Support\Facades\Http::post($triggerUrl, $payload);

            if ($response->successful()) {
                \Illuminate\Support\Facades\Log::info("Sent Power Automate notification to " . count($approverEmails) . " shipping users for requirement change {$changeRequest->id}");
            } else {
                \Illuminate\Support\Facades\Log::error("Failed to send Power Automate notification for shipping requirement change {$changeRequest->id}: " . $response->body());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Exception sending Power Automate notifications for shipping requirement change {$changeRequest->id}: " . $e->getMessage());
        }
    }

    /**
     * Get users for a specific department.
     */
    private function getDepartmentUsers($department)
    {
        // Return users with the specific permission
        return \App\Models\User::permission("container.{$department}.approve")->get();
    }

    /**
     * Download container report as PDF.
     */
    public function downloadContainerReport(ShipmentTransport $shipmentTransport)
    {
        \Log::info("PDF Download: Starting download for container ID {$shipmentTransport->id}");

        $user = auth()->user();

        // Check if user has shipping access
        if (!$user->hasPermissionTo('container.shipping.access')) {
            \Log::warning("PDF Download: Access denied for user {$user->id} - no shipping access");
            return response()->json(['message' => 'Unauthorized. Shipping department access required.'], 403);
        }

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasAnyPermission(['superadmin','container.shipping.access']) && $shipmentTransport->site_id !== $user->site_id) {
            \Log::warning("PDF Download: Access denied for user {$user->id} - wrong site access");
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        // Check if container is completed
        if ($shipmentTransport->status !== 'completed') {
            \Log::warning("PDF Download: Container {$shipmentTransport->id} not completed (status: {$shipmentTransport->status})");
            return response()->json(['message' => 'Report can only be downloaded for completed containers'], 400);
        }

        \Log::info("PDF Download: Access checks passed for container {$shipmentTransport->id}");

        // Check for cached PDF first
        $cacheKey = 'container_report_' . $shipmentTransport->id . '_' . $shipmentTransport->updated_at->timestamp;
        $cachePath = storage_path('app/pdf-cache/' . $cacheKey . '.pdf');

        if (file_exists($cachePath) && filemtime($cachePath) > $shipmentTransport->updated_at->timestamp) {
            \Log::info("PDF Download: Serving cached PDF for container {$shipmentTransport->id}");
            // Return cached PDF
            $filename = 'container-report-' . $shipmentTransport->transport_number . '.pdf';
            return response()->file($cachePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        \Log::info("PDF Download: Cache miss, generating new PDF for container {$shipmentTransport->id}");

        $startTime = microtime(true);

        // Load all relations
        \Log::info("PDF Download: Loading container data with relations");
        $container = ShipmentTransport::with([
            'inspection',
            'photo',
            'approvals',
            'createdBy',
            'holdBy',
            'site',
            'shipmentTransportDrivers.visitor'
        ])->findOrFail($shipmentTransport->id);

        $loadTime = microtime(true) - $startTime;
        \Log::info("PDF Download: Data loaded in " . round($loadTime, 2) . " seconds. Photos: " . $container->photo->count());

        // Generate PDF
        \Log::info("PDF Download: Starting PDF generation");
        $pdfStartTime = microtime(true);
        $pdf = $this->generateContainerReportPDF($container);

        // Try alternative output method
        $tempPdfPath = storage_path('tmp/temp_' . $container->id . '_' . time() . '.pdf');
        $pdf->Output($tempPdfPath, 'F'); // Save to file first

        if (file_exists($tempPdfPath)) {
            $pdfContent = file_get_contents($tempPdfPath);
            unlink($tempPdfPath); // Clean up temp file
        } else {
            \Log::error("PDF Download: Failed to save PDF to temp file");
            $pdfContent = $pdf->output(); // Fallback to direct output
        }

        $pdfGenTime = microtime(true) - $pdfStartTime;
        \Log::info("PDF Download: PDF generated in " . round($pdfGenTime, 2) . " seconds");
        \Log::info("PDF Download: PDF content size: " . strlen($pdfContent) . " bytes");

        // Ensure cache directory exists
        $cacheDir = storage_path('app/pdf-cache');
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
            \Log::info("PDF Download: Created cache directory");
        }

        // Save to cache
        \Log::info("PDF Download: Saving to cache at: {$cachePath}");
        $writeResult = file_put_contents($cachePath, $pdfContent);
        \Log::info("PDF Download: Cache write result: {$writeResult} bytes written");
        \Log::info("PDF Download: Cache file exists: " . (file_exists($cachePath) ? 'yes' : 'no'));
        if (file_exists($cachePath)) {
            \Log::info("PDF Download: Cache file size: " . filesize($cachePath) . " bytes");
        }

        $totalTime = microtime(true) - $startTime;
        \Log::info("PDF Download: Total processing time: " . round($totalTime, 2) . " seconds for container {$shipmentTransport->id}");

        // Return PDF as download
        $filename = 'container-report-' . $container->transport_number . '.pdf';
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate PDF report for container.
     */
    private function generateContainerReportPDF(ShipmentTransport $container)
    {
        try {
            \Log::info("PDF Generation: Starting MPDF initialization");

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'tempDir' => storage_path('tmp/mpdf'),
                'setAutoTopMargin' => 'stretch',
                'setAutoBottomMargin' => 'stretch',
                'debug' => true
            ]);

            \Log::info("PDF Generation: MPDF initialized successfully");

            // Build HTML content
            \Log::info("PDF Generation: Building HTML content");
            $html = $this->buildContainerReportHTML($container);
            \Log::info("PDF Generation: HTML content built, length: " . strlen($html) . " characters");

            \Log::info("PDF Generation: Writing HTML to PDF");
            $mpdf->WriteHTML($html);
            \Log::info("PDF Generation: HTML written successfully");

            \Log::info("PDF Generation: PDF generation completed");
            return $mpdf;

        } catch (\Exception $e) {
            \Log::error("PDF Generation: Exception during PDF creation: " . $e->getMessage());
            \Log::error("PDF Generation: Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Build HTML content for container report.
     */
    private function buildContainerReportHTML(ShipmentTransport $container)
    {
        $html = '
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
            .section { margin-bottom: 20px; }
            .section h3 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; font-weight: bold; }
            .status-completed { color: green; font-weight: bold; }
            .status-failed { color: red; font-weight: bold; }
            .photo-list { margin: 10px 0; }
            .photo-item { margin: 5px 0; padding: 5px; background-color: #f9f9f9; }

            @page {
    margin: 120px 40px 100px 40px; /* top right bottom left */
}

.header-fixed {
    position: fixed;
    top: -100px;
    left: 0;
    right: 0;
    height: 90px;
    border-bottom: 2px solid #333;
}

.footer-fixed {
    position: fixed;
    bottom: -80px;
    left: 0;
    right: 0;
    height: 70px;
    border-top: 1px solid #333;
    font-size: 11px;
    color: #555;
    text-align: center;
    padding-top: 10px;
}

.company-table {
    width: 100%;
    border: none;
}

.company-table td {
    border: none;
    vertical-align: middle;
}

.company-info {
    text-align: right;
    font-size: 12px;
    line-height: 1.4;
}
        </style>

<div class="header-fixed">
    <table class="company-table">
        <tr>
            <td style="width: 120px;">
                <img src="' . public_path('assets/skpLogo.png') . '" style="height:70px;">
            </td>
            <td class="company-info">
                <strong style="font-size:16px;">Syarikat Sin Kwang Plastic Industries Sdn Bhd</strong><br>
                No 6, Jalan Teknologi 5,<br>
                Taman Teknologi Johor, 81400 Senai, Johor, Malaysia<br>
                Phone: 07 4330 777<br>
                Email: info@skpres.com
            </td>
        </tr>
    </table>
</div>


        <div class="section">
            <h3>Basic Information</h3>
            <table>
                <tr><th>Transport Type</th><td>' . ucfirst($container->transport_type) . '</td></tr>
                <tr><th>Transport Number</th><td>' . $container->transport_number . '</td></tr>
                <tr><th>Size</th><td>' . ($container->size ?? 'N/A') . '</td></tr>
                <tr><th>SKU Number</th><td>' . $container->sku_number . '</td></tr>
                <tr><th>Model/Project</th><td>' . $container->model_project . '</td></tr>
                <tr><th>Forwarder</th><td>' . $container->forwarder . '</td></tr>
                <tr><th>Hauler</th><td>' . $container->hauler . '</td></tr>
                <tr><th>Country</th><td>' . $container->country . '</td></tr>
                <tr><th>Work Order</th><td>' . $container->work_order . '</td></tr>
                <tr><th>Site</th><td>' . ($container->site ? $container->site->name : 'N/A') . '</td></tr>
                <tr><th>Created By</th><td>' . ($container->createdBy ? $container->createdBy->name : 'N/A') . '</td></tr>
                <tr><th>Created Date</th><td>' . $container->created_at->format('d/m/Y H:i:s') . '</td></tr>
                <tr><th>Status</th><td class="status-' . $container->status . '">' . ucfirst(str_replace('_', ' ', $container->status)) . '</td></tr>
                <tr><th>Stage</th><td>' . ucfirst(str_replace('_', ' ', $container->stage)) . '</td></tr>
            </table>
        </div>';

        // Seals & Security Section
        $html .= '
        <div class="section">
            <h3>Seals & Security</h3>
            <table>
                <tr><th>High Security Seal</th><td>' . ($container->high_security_seal_sn ? 'Yes - ' . $container->high_security_seal_sn : 'No') . '</td></tr>
                <tr><th>Fork Seal</th><td>' . ($container->fork_seal_sn ? 'Yes - ' . $container->fork_seal_sn . ' (Size: ' . ($container->fork_seal_size ?? 'N/A') . ')' : 'No') . '</td></tr>
                <tr><th>Temporary Seal</th><td>' . ($container->temporary_seal_sn ? 'Yes - ' . $container->temporary_seal_sn : 'No') . '</td></tr>
                <tr><th>Inside GPS</th><td>' . ($container->inside_gps_sn ? 'Yes - ' . $container->inside_gps_sn : 'No') . '</td></tr>
                <tr><th>Outside GPS</th><td>' . ($container->outside_gps_sn ? 'Yes - ' . $container->outside_gps_sn : 'No') . '</td></tr>
            </table>
        </div>';

        // Inspection Section
        if ($container->inspection) {
            $html .= '
            <div class="section">
                <h3>Inspection Details</h3>
                <table>
                    <tr><th>Inspection Status</th><td>' . ucfirst($container->inspection->status) . '</td></tr>
                    <tr><th>Inspection Date</th><td>' . $container->inspection->created_at->format('d/m/Y H:i:s') . '</td></tr>
                    <tr><th>Inspector</th><td>' . ($container->inspection->inspector ? $container->inspection->inspector->name : 'N/A') . '</td></tr>
                </table>
            </div>';
        }

        // Photos Section - Optimized for performance
        if ($container->photo && $container->photo->count() > 0) {
            $html .= '
            <div class="section">
                <h3>Uploaded Photos (' . $container->photo->count() . ')</h3>
                <div class="photo-list">';

            // Limit photos to first 15 for performance, or show summary
            $photosToShow = $container->photo->take(15);
            $remainingCount = $container->photo->count() - $photosToShow->count();

            foreach ($photosToShow as $photo) {
                // Format photo label: remove underscores and capitalize first letter
                $formattedLabel = ucfirst(str_replace('_', ' ', $photo->label));

                // Get base64 encoded image for better performance with size optimization
                $imagePath = storage_path('app/public/' . $photo->photo_path);
                $base64Image = '';
                if (file_exists($imagePath)) {
                    // Resize image to reduce memory usage
                    $resizedImage = $this->resizeImageForPDF($imagePath);
                    if ($resizedImage) {
                        $base64Image = 'data:image/jpeg;base64,' . base64_encode($resizedImage);
                    }
                }

                if ($base64Image) {
                    $html .= '<div class="photo-item" style="margin-bottom: 20px; page-break-inside: avoid;">
                        <strong>' . $formattedLabel . '</strong><br>
                        <em>Uploaded: ' . $photo->created_at->format('d/m/Y H:i:s') . '</em><br>
                        <img src="' . $base64Image . '" style="max-width: 400px; max-height: 250px; width: auto; height: auto; margin-top: 10px; border: 1px solid #ddd; padding: 5px;" alt="' . $formattedLabel . '">
                    </div>';
                } else {
                    $html .= '<div class="photo-item" style="margin-bottom: 20px; page-break-inside: avoid;">
                        <strong>' . $formattedLabel . '</strong><br>
                        <em>Uploaded: ' . $photo->created_at->format('d/m/Y H:i:s') . '</em><br>
                        <em style="color: #666;">[Image not available]</em>
                    </div>';
                }
            }

            if ($remainingCount > 0) {
                $html .= '<div class="photo-item" style="margin-bottom: 10px; padding: 10px; background-color: #e8f4f8; border-left: 4px solid #2196F3;">
                    <em>+' . $remainingCount . ' additional photos available but not displayed for optimal performance.</em>
                </div>';
            }

            $html .= '</div></div>';
        }

        // Approvals Section
        if ($container->approvals && $container->approvals->count() > 0) {
            $html .= '
            <div class="section">
                <h3>Approval History</h3>
                <table>
                    <tr>
                        <th>Department</th>
                        <th>Type</th/>
                        <th>Approver</th>
                        <th>Status</th>
                        <th>Decision Date</th>
                        <th>Comments</th>
                    </tr>';

            foreach ($container->approvals as $approval) {
                $html .= '<tr>
                    <td>' . ucfirst($approval->department) . '</td>
                    <td>' . ($approval->approver ? $approval->approver->name : 'N/A') . '</td>
                    <td>' . ($approval->approver ? $approval->approval_type : 'N/A') . '</td>
                    <td>' . ucfirst($approval->approval_status) . '</td>
                    <td>' . ($approval->approved_at ? $approval->approved_at->format('d/m/Y H:i:s') : 'N/A') . '</td>
                    <td>' . ($approval->remarks ?? 'N/A') . '</td>
                </tr>';
            }

            $html .= '</table></div>';
        }

        // Driver Information
        if ($container->shipmentTransportDrivers && $container->shipmentTransportDrivers->count() > 0) {
            $html .= '
            <div class="section">
                <h3>Driver Information</h3>';

            foreach ($container->shipmentTransportDrivers as $driverAssignment) {
                if ($driverAssignment->visitor) {
                    $visitor = $driverAssignment->visitor;
                    $html .= '
                    <table>
                        <tr><th>Name</th><td>' . $visitor->visitor_name . '</td></tr>
                        <tr><th>IC Number</th><td>' . $visitor->ic_number . '</td></tr>
                        <tr><th>Passport</th><td>' . ($visitor->passport ?? 'N/A') . '</td></tr>
                        <tr><th>Company</th><td>' . $visitor->visitor_company . '</td></tr>
                        <tr><th>Vehicle Number</th><td>' . $visitor->vehicle_number . '</td></tr>
                        <tr><th>Phone</th><td>' . $visitor->phone_number . '</td></tr>
                        <tr><th>Assigned Date</th><td>' . $driverAssignment->created_at->format('d/m/Y H:i:s') . '</td></tr>
                    </table>';
                }
            }

            $html .= '</div>';
        }

        // Hold Information
        if ($container->is_on_hold) {
            $html .= '
            <div class="section">
                <h3>Hold Information</h3>
                <table>
                    <tr><th>Hold Reason</th><td>' . $container->hold_reason . '</td></tr>
                    <tr><th>Held By</th><td>' . ($container->holdBy ? $container->holdBy->name : 'N/A') . '</td></tr>
                    <tr><th>Held Date</th><td>' . ($container->hold_at ? $container->hold_at->format('d/m/Y H:i:s') : 'N/A') . '</td></tr>
                </table>
            </div>';
        }

        $html .= '
<div class="footer-fixed">
    Confidential Notice: This container inspection report contains confidential and proprietary information of 
    <strong>Syarikat Sin Kwang Plastic Industries Sdn Bhd</strong>. Unauthorized disclosure, copying, distribution, 
    or use of this report, in whole or in part, is strictly prohibited without prior written approval from Management.
</div>';


        $html .= '</body></html>';

        return $html;
    }

    /**
     * Resize and compress image for PDF to reduce memory usage.
     */
    private function resizeImageForPDF($imagePath, $maxWidth = 800, $maxHeight = 600, $quality = 75)
    {
        try {
            \Log::info("Image processing: Starting for {$imagePath}");

            // Check if file exists
            if (!file_exists($imagePath)) {
                \Log::error("Image processing: File does not exist: {$imagePath}");
                return false;
            }

            // Get image info
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                \Log::error("Image processing: Cannot get image info for {$imagePath}");
                return false;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            \Log::info("Image processing: Original size {$width}x{$height}, type: {$mimeType}");

            // Calculate new dimensions
            $aspectRatio = $width / $height;

            if ($width > $height) {
                // Landscape
                if ($width > $maxWidth) {
                    $newWidth = $maxWidth;
                    $newHeight = $maxWidth / $aspectRatio;
                } else {
                    $newWidth = $width;
                    $newHeight = $height;
                }
            } else {
                // Portrait
                if ($height > $maxHeight) {
                    $newHeight = $maxHeight;
                    $newWidth = $maxHeight * $aspectRatio;
                } else {
                    $newWidth = $width;
                    $newHeight = $height;
                }
            }

            // Ensure minimum dimensions
            $newWidth = max($newWidth, 200);
            $newHeight = max($newHeight, 150);

            \Log::info("Image processing: New size {$newWidth}x{$newHeight}");

            // Create image resource based on type
            $sourceImage = null;
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($imagePath);
                    break;
                default:
                    \Log::error("Image processing: Unsupported image type: {$mimeType}");
                    return false;
            }

            if (!$sourceImage) {
                \Log::error("Image processing: Failed to create image resource");
                return false;
            }

            // Create new resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            }

            // Resize the image
            $resizeResult = imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            if (!$resizeResult) {
                \Log::error("Image processing: Failed to resize image");
                imagedestroy($sourceImage);
                return false;
            }

            // Output to buffer
            ob_start();
            $outputResult = imagejpeg($resizedImage, null, $quality);
            $compressedImage = ob_get_clean();

            if (!$outputResult || empty($compressedImage)) {
                \Log::error("Image processing: Failed to output compressed image");
                imagedestroy($sourceImage);
                imagedestroy($resizedImage);
                return false;
            }

            // Clean up memory
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            \Log::info("Image processing: Successfully processed image, size: " . strlen($compressedImage) . " bytes");

            return $compressedImage;

        } catch (\Exception $e) {
            \Log::error("Image processing: Exception for {$imagePath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShipmentTransport $shipmentTransport)
    {
        //
    }
}
