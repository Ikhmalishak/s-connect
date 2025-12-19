<?php

namespace App\Http\Controllers;

use App\Models\ContainerShipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContainerShipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContainerShipment::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('skp_site', 'like', "%{$search}%")
                  ->orWhere('container_type', 'like', "%{$search}%")
                  ->orWhere('container_number', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('forwarder', 'like', "%{$search}%")
                  ->orWhere('hauler', 'like', "%{$search}%")
                  ->orWhere('sku_number', 'like', "%{$search}%")
                  ->orWhere('container_size', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('work_order', 'like', "%{$search}%");
            });
        }

        // Advanced filters
        if ($request->has('skp_site') && !empty($request->skp_site)) {
            $query->where('skp_site', 'like', '%' . $request->skp_site . '%');
        }

        if ($request->has('container_type') && !empty($request->container_type)) {
            $query->where('container_type', 'like', '%' . $request->container_type . '%');
        }

        if ($request->has('container_number') && !empty($request->container_number)) {
            $query->where('container_number', 'like', '%' . $request->container_number . '%');
        }

        if ($request->has('country') && !empty($request->country)) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        if ($request->has('forwarder') && !empty($request->forwarder)) {
            $query->where('forwarder', 'like', '%' . $request->forwarder . '%');
        }

        if ($request->has('hauler') && !empty($request->hauler)) {
            $query->where('hauler', 'like', '%' . $request->hauler . '%');
        }

        if ($request->has('sku_number') && !empty($request->sku_number)) {
            $query->where('sku_number', 'like', '%' . $request->sku_number . '%');
        }

        if ($request->has('container_size') && !empty($request->container_size)) {
            $query->where('container_size', 'like', '%' . $request->container_size . '%');
        }

        if ($request->has('model') && !empty($request->model)) {
            $query->where('model', 'like', '%' . $request->model . '%');
        }

        if ($request->has('work_order') && !empty($request->work_order)) {
            $query->where('work_order', 'like', '%' . $request->work_order . '%');
        }

        if ($request->has('high_sec') && $request->high_sec !== '') {
            $query->where('high_sec', $request->high_sec === '1');
        }

        // Date range filters
        if ($request->has('shipment_date_from') && !empty($request->shipment_date_from)) {
            $query->where('shipment_date', '>=', $request->shipment_date_from);
        }

        if ($request->has('shipment_date_to') && !empty($request->shipment_date_to)) {
            $query->where('shipment_date', '<=', $request->shipment_date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['skp_site', 'container_type', 'container_number', 'shipment_date', 'country', 'forwarder', 'hauler', 'sku_number', 'container_size', 'model', 'work_order', 'high_sec', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $shipments = $query->paginate($perPage);

        return response()->json($shipments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'skp_site' => 'required|string|max:255',
            'container_type' => 'required|string|max:255',
            'container_number' => 'required|string|max:255|unique:container_shipments',
            'shipment_date' => 'required|date',
            'country' => 'required|string|max:255',
            'forwarder' => 'required|string|max:255',
            'hauler' => 'required|string|max:255',
            'sku_number' => 'required|string|max:255',
            'container_size' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'work_order' => 'required|string|max:255',
            'high_sec' => 'boolean',
        ]);

        $shipment = ContainerShipment::create($validated);

        return response()->json($shipment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ContainerShipment $containerShipment): JsonResponse
    {
        return response()->json($containerShipment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ContainerShipment $containerShipment): JsonResponse
    {
        $validated = $request->validate([
            'skp_site' => 'required|string|max:255',
            'container_type' => 'required|string|max:255',
            'container_number' => 'required|string|max:255|unique:container_shipments,container_number,' . $containerShipment->id,
            'shipment_date' => 'required|date',
            'country' => 'required|string|max:255',
            'forwarder' => 'required|string|max:255',
            'hauler' => 'required|string|max:255',
            'sku_number' => 'required|string|max:255',
            'container_size' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'work_order' => 'required|string|max:255',
            'high_sec' => 'boolean',
        ]);

        $containerShipment->update($validated);

        return response()->json($containerShipment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContainerShipment $containerShipment): JsonResponse
    {
        $containerShipment->delete();

        return response()->json(['message' => 'Container shipment deleted successfully']);
    }
}
