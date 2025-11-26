<?php
namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ShipmentTransport;
use Illuminate\Http\Request;

class ShipmentTransportController extends Controller
{

    //get dashboard for shipment transport
    public function dashboard()
    {
        return inertia('ManageContainer/Dashboard');
    }

    public function index()
    {
        $site_id = auth()->user()->site_id;
        $shipments = ShipmentTransport::where('site_id', $site_id)->with('inspection')->get();

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
