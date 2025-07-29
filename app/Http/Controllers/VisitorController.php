<?php

namespace App\Http\Controllers;

use App\Models\GatePass;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Events\VisitorRegistered;
class VisitorController extends Controller
{
    public function index()
    {
        //return index page with data
        $visitor = Visitor::orderByDesc('id')->get();

        return Inertia::render('Security/Visitor/VisitorDashboard', [
            'data' => $visitor,
        ]);
    }

    public function refreshVisitorTablePage(Request $request)
    {
        $limit = $request->input('limit', 10);

        $date = Carbon::now()->format('Y-m-d');
        $startOfDay = Carbon::today();
        $endOfDay = Carbon::now();

        // Get all visitors
        $visitor = Visitor::with('gatePass:id,pass_number')
            ->latest()->take($limit)->get();

        // Currently inside
        $visitor_inside = Visitor::whereNotNull('time_in')
            ->whereNull('time_out')
            ->whereDate('date', $date)
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->groupBy('visitor_type')
            ->get();

        // Visitors out
        $visitor_today = Visitor::whereDate('date', $date)
            ->whereNotNull('time_out')
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->groupBy('visitor_type')
            ->get();


        // Get time_in count grouped by hour
        $visitor_in_by_hour = Visitor::select([
            DB::raw("HOUR(time_in) as hour"),
            DB::raw("COUNT(*) as total_in")
        ])
            ->whereBetween('time_in', [$startOfDay, $endOfDay])
            ->whereDate('date', $date)
            ->groupBy(DB::raw("HOUR(time_in)"))
            ->orderBy("hour")
            ->get();

        // Get time_in count grouped by hour
        $visitor_out_by_hour = Visitor::select([
            DB::raw("HOUR(time_out) as hour"),
            DB::raw("COUNT(*) as total_out")
        ])
            ->whereBetween('time_out', [$startOfDay, $endOfDay])
            ->whereDate('date', $date)
            ->groupBy(DB::raw("HOUR(time_out)"))
            ->orderBy("hour")
            ->get();

        return response()->json([
            'visitor_inside' => $visitor_inside,
            'visitor_today' => $visitor_today,
            'visitor' => $visitor,
            'visitor_in_by_hour' => $visitor_in_by_hour,
            'visitor_out_by_hour' => $visitor_out_by_hour,
        ]);
    }

    public function getVisitorInside()
    {
        $date = now()->toDateString(); // or Carbon::today()->toDateString()

        $visitor_inside = Visitor::whereNotNull('time_in')
            ->whereNull('time_out')
            ->whereDate('date', $date)
            ->with('gatePass')
            ->get();

        return response()->json([
            'data' => $visitor_inside
        ]);
    }

    public function getVisitorForm()
    {
        //return form page
        return Inertia::render('Security/Visitor/VisitorForm');
    }

    public function getArchivedVisitorForm()
    {
        //return form page
        return Inertia::render('Security/Visitor/ArchivedFormSeparated');

    }

    public function getVisitorAcknowledgeForm()
    {
        $visitor = Visitor::with('visitorCompany')->get();

        //return form page
        return Inertia::render('Security/Visitor/VisitorAcknowledgeTable', [
            'data' => $visitor,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pass_number' => 'nullable|string',
            'purpose' => 'required|string',
            'remarks' => 'nullable|string',
            'site' => 'nullable|string',
            'time_in' => 'nullable|string',
            'time_out' => 'nullable|string',
            'time_register' => 'nullable',
            'date' => 'nullable|date',
            'visitor_type' => 'required|string',
            'vehicle_number' => 'nullable|string',
            'visitor_company' => 'nullable|string',
            'visitors' => 'required|array|min:1',
            'visitors.*.visitor_name' => 'required|string',
            'visitors.*.id_type' => 'required|in:IC,Passport',
            'visitors.*.id_number' => 'required|string',
            'visitors.*.phone_number' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {

            $timeRegister = $validated['time_register'] ??= now()->format('H:i');
            $date = $validated['date'] ??= now()->format('Y-m-d');
            $site = auth()->user()->site;
            $company = $validated['visitor_company'] ? $validated['visitor_company'] : "N/A";

            $createdVisitors = [];
            foreach ($validated['visitors'] as $visitor) {
                $ic = $visitor['id_type'] === 'IC' ? $visitor['id_number'] : "N/A";
                $passport = $visitor['id_type'] === 'Passport' ? $visitor['id_number'] : "N/A";

                $pass_id = $this->getPassNumber($validated['visitor_type']);

                $new_visitor = Visitor::create([
                    'visitor_name' => $visitor['visitor_name'],
                    'gate_pass_id' => $pass_id->id,
                    'ic_number' => $ic,
                    'passport' => $passport,
                    'phone_number' => $visitor['phone_number'],
                    'purpose' => $validated['purpose'],
                    'remarks' => $validated['remarks'] ?? null,
                    'site' => $site,
                    'time_register' => $timeRegister,
                    'date' => $date,
                    'visitor_type' => $validated['visitor_type'],
                    'vehicle_number' => $validated['vehicle_number'],
                    'visitor_company' => $company,
                    'is_acknowledge' => true,
                ]);

                event(new VisitorRegistered($new_visitor));

                $createdVisitors[] = [
                    ...$new_visitor->toArray(),
                    'pass_number' => $pass_id->pass_number
                ];
            }

            return response()->json([
                'message' => 'Visitor registered successfully.',
                'data' => $createdVisitors,
            ]);
        });
    }

    public function getPassNumber($visitor_type)
    {
        return DB::transaction(function () use ($visitor_type) {
            $gate_pass = GatePass::where('pass_type', $visitor_type)
                ->where('state', 'free')
                ->lockForUpdate() // Prevents race condition
                ->first();

            if (!$gate_pass) {
                throw new \Exception('There are no available passes');
            }

            $gate_pass->state = 'occupied';
            $gate_pass->save();

            return $gate_pass;
        });
    }

    //function to update check in time
    public function checkIn($id)
    {
        $visitor = Visitor::findOrFail($id);
        $visitor->time_in = now();
        $visitor->save();

        return redirect()->back();
    }

    //function to update check out time
    public function checkOut($id)
    {
        $visitor = Visitor::findOrFail($id);

        $visitor->time_out = now();

        // Ensure time_in is not null
        if ($visitor->time_in) {
            // Calculate the duration in minutes (or any format you want)
            $duration = Carbon::parse($visitor->time_in)->diffInMinutes($visitor->time_out);

            // Optionally store it in the database (you'll need to add a `duration` column to the visitors table)
            $visitor->duration = $duration;
        }

        $visitor->save();

        return redirect()->back();
    }

    //function to update the acknowledge
    public function updateAcknowledge($id)
    {
        $visitor = Visitor::findOrFail($id);
        $visitor->is_acknowledge = true;
        $visitor->save();

        return redirect()->back();
    }
}
