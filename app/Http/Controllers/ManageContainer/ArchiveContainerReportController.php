<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ArchiveContainerDetail;
use Illuminate\Http\Request;

class ArchiveContainerReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->input('search');

        $query = ArchiveContainerDetail::query();

        // Apply site filter unless user is superadmin
        if (!$user->hasPermissionTo('superadmin')) {
            // Assuming archive details might have site filtering in the future
            // For now, allow all if no site field exists
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('container_truck_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('archiveContainerReport', function ($reportQuery) use ($search) {
                      $reportQuery->where('skp_stapprovalnamewarehouse', 'LIKE', "%{$search}%")
                                  ->orWhere('skp_ndapprovalnameqa', 'LIKE', "%{$search}%")
                                  ->orWhere('skp_rdapprovalnameshipping', 'LIKE', "%{$search}%")
                                  ->orWhere('skp_thapprovalnamesecurity', 'LIKE', "%{$search}%");
                  });
            });
        }

        $details = $query->with('archiveContainerReport')
                        ->orderBy('date', 'desc')
                        ->paginate(20);

        return response()->json($details);
    }
}
