<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ContainerGps;
use Illuminate\Http\Request;

class ContainerGpsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        $search = $request->input('search', '');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $query = ContainerGps::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('overhaul_id', 'like', "%{$search}%")
                  ->orWhere('reject_reason', 'like', "%{$search}%");
            });
        }

        $allowedSortFields = ['overhaul_id', 'reject_reason', 'remark', 'date', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $container_gps = $query->paginate($perPage);

        return response()->json($container_gps);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate request and extract validated data
        $validatedData = $request->validate([
            'overhaul_id' => 'required|string',
            'reject_reason' => 'required|string',
            'remark' => 'nullable|string',
            'date' => 'required|date',
        ]);

        // Use the validated array to create the model
        $created_container_gps = ContainerGps::create($validatedData);

        // Return a redirect or response here...
        return response()->json([
            'message' => "Successfully created container gps",
            'data' => $created_container_gps
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(ContainerGps $containerGps)
    {
        return response()->json([
            'data' => $containerGps,
            'message' => "Successfully fetch data for {$containerGps->id}"
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ContainerGps $containerGps)
    {
        $validated = $request->validate([
            'overhaul_id' => 'required|string',
            'reject_reason' => 'required|string',
            'remark' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $containerGps->update($validated);

        return response()->json([
            'data' => $containerGps,
            'message' => "Successfully update container gps"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContainerGps $containerGps)
    {
        $containerGps->delete();

        return response()->json([
            'message' => "Successfully deleted container gps"
        ]);
    }
}
