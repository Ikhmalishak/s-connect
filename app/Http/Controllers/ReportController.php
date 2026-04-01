<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class ReportController extends Controller
{

    public function getSystemLogPage(Request $request)
    {
        return Inertia::render('SystemLog');
    }

    public function getSystemLog(Request $request)
    {
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $limit = $request->input('limit', 200);
        $sortBy = $request->input('sort_by', 'created_at');
        $sort = $request->input('sort', 'desc');

        // ✅ Restrict sorting to specific columns for safety
        $allowedSortColumns = ['created_at', 'description'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        // ✅ Base query with limited causer fields
        $query = Activity::with(['causer:id,name,email']);

        // ✅ Search by description or causer name/email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // ✅ Filter by date range safely using Carbon
        if ($startDate && $endDate) {
            try {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format.'], 400);
            }
        }

        // ✅ Sort and paginate results
        $logs = $query->orderBy($sortBy, $sort)->paginate($limit);

        // ✅ Transform data to frontend-friendly structure
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'description' => $log->description,
                'causer_name' => $log->causer->name ?? 'System',
                'causer_email' => $log->causer->email ?? null,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($logs);
    }

    public function getReport()
    {
        $date = now();

        $total_visitor_per_site = Visitor::whereDate('date', $date)
            ->select('site_id', DB::raw('COUNT(*) as total'))
            ->groupBy('site_id')
            ->get();

        return $total_visitor_per_site;
    }

}
