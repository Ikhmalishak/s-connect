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

    public function getReport(Request $request)
    {
        $start_date = $request->start_date ?? now()->toDateString();
        $end_date = $request->end_date ?? now()->toDateString();
        $site = $request->site ?? "all";

        // base query (date + site if provided)
        $baseQuery = Visitor::query()
            ->whereBetween('date', [$start_date, $end_date])
            ->when($site !== "all", function ($query) use ($site) {
                $query->whereHas('site', function ($q) use ($site) {
                    $q->where('site_code', $site);
                });
            });

        // total visitor
        $total_visitor = (clone $baseQuery)
            ->where('visitor_type', 'visitor')
            ->count();

        // total driver
        $total_driver = (clone $baseQuery)
            ->where(function ($query) {
                $query->where('visitor_type', 'like', 'inbound-%')
                    ->orWhere('visitor_type', 'like', 'outbound-%');
            })
            ->count();

        // total contractor
        $total_contractor = (clone $baseQuery)
            ->where('visitor_type', 'contractor')
            ->count();

        // total all
        $total_all = $total_visitor + $total_driver + $total_contractor;

        // Get all acknowledged IDs
        $acknowledgedIds = DB::table('visitor_acknowledgements')
            ->pluck('id_number')
            ->toArray();

        // Get visitors in date range
        $visitors = (clone $baseQuery)->get(['ic_number', 'passport']);

        // Normalize acknowledged IDs by removing dashes
        $normalizedAcknowledgedIds = array_map(function ($id) {
            return str_replace('-', '', $id);
        }, $acknowledgedIds);

        $existing = 0;
        $new = 0;

        foreach ($visitors as $visitor) {
            $ic = str_replace('-', '', $visitor->ic_number);
            $passport = str_replace('-', '', $visitor->passport);

            if (
                ($ic !== 'N/A' && in_array($ic, $normalizedAcknowledgedIds)) ||
                ($passport !== 'N/A' && in_array($passport, $normalizedAcknowledgedIds))
            ) {
                $existing++;
            } else {
                $new++;
            }
        }

        //visit by purpose
        $purpose = (clone $baseQuery)
            ->select('purpose', DB::raw('count(*) as total'))
            ->groupBy('purpose')
            ->orderByDesc('total')
            ->get();

        //driver incoming and outcoming
        $driver = (clone $baseQuery)
            ->whereIn('visitor_type', ['inbound-shipment/transfer', 'outbound-shipment/transfer'])
            ->selectRaw('purpose, COUNT(*) as total')
            ->groupBy('purpose')
            ->get();

        //find highest company
        $topCompanies = (clone $baseQuery)
            ->selectRaw('visitor_company, COUNT(*) as total')
            ->groupBy('visitor_company')
            ->orderByDesc('total')
            ->limit(5)
            ->get();


        return response()->json([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'site' => $site,
            'total_visitor' => $total_visitor,
            'total_driver' => $total_driver,
            'total_contractor' => $total_contractor,
            'total_all' => $total_all,
            'new_visitor' => $new,
            'existing_visitor' => $existing,
            'purpose' => $purpose,
            'driver' => $driver,
            'company' => $topCompanies
        ]);
    }

    public function getVisitorByWeek(Request $request)
    {
        $year = $request->year ?? now()->year;
        $site = $request->site ?? "all";

        $raw = Visitor::query()
            ->selectRaw('WEEK(created_at, 1) as week, visitor_type, COUNT(*) as total')
            ->when($site !== "all", function ($query) use ($site) {
                $query->whereHas('site', function ($q) use ($site) {
                    $q->where('site_code', $site);
                });
            })
            ->whereYear('created_at', $year)
            ->groupBy('week', 'visitor_type')
            ->orderBy('week')
            ->get();

        // Transform to analyticsData-like format
        $weeks = collect(range(1, 52))->map(function ($week) use ($raw) {
            return [
                'name' => "Week {$week}",
                'contractor' => $raw->where('week', $week)->where('visitor_type', 'contractor')->sum('total'),
                'driver-inbound' => $raw->where('week', $week)->where('visitor_type', 'inbound-shipment/transfer')->sum('total'),
                'driver-outbound' => $raw->where('week', $week)->where('visitor_type', 'outbound-shipment/transfer')->sum('total'),
                'visitor' => $raw->where('week', $week)->where('visitor_type', 'visitor')->sum('total'),
            ];
        });

        return response()->json([
            'year' => $year,
            'site' => $site,
            'data' => $weeks
        ]);
    }

}
