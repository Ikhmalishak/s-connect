<?php
namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ShipmentTransport;
use Illuminate\Http\Request;
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

        $query = ShipmentTransport::with(['inspection', 'photo']);

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

    public function store(Request $request)
    {
        $site_id = auth()->user()->site_id;
        $validated = $request->validate([
            'transport_type' => 'required|string',
            'transport_number' => 'required|string',
            'sku_number' => 'required|string',
            'model_project' => 'required|string',
            'forwarder' => 'required|string',
            'country' => 'required|string',
            'work_order' => 'required|string',
            'hauler' => 'required|string',
            'high_security_seal' => 'nullable|string',
            'gps' => 'nullable|string',
            'fork_seal' => 'nullable|string',
            'temporary_seal' => 'nullable|string',
        ]);

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
     * Remove the specified resource from storage.
     */
    public function destroy(ShipmentTransport $shipmentTransport)
    {
        //
    }
}
