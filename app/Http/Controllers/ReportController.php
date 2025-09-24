<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use DB;
use Illuminate\Http\Request;
use function Laravel\Prompts\select;

class ReportController extends Controller
{
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

        // new visitors = IC not found in acknowledgement table
        $new = (clone $baseQuery)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('visitor_acknowledgements')
                    ->whereColumn('visitor_acknowledgements.id_number', 'visitors.ic_number')
                    ->orWhereColumn('visitor_acknowledgements.id_number', 'visitors.passport');
            })
            ->count();

        // existing visitors = IC found in acknowledgement table
        $existing = (clone $baseQuery)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('visitor_acknowledgements')
                    ->where(function ($q) {
                        $q->whereColumn('visitor_acknowledgements.id_number', 'visitors.ic_number')
                            ->orWhereColumn('visitor_acknowledgements.id_number', 'visitors.passport');
                    });
            })
            ->count();

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
