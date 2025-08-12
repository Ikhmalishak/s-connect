<?php

namespace App\Http\Controllers;

use App\Models\GatePass;
use App\Models\Site;
use App\Models\Visitor;
use App\Models\VisitorAcknowledgement;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Events\VisitorRegistered;
use Illuminate\Validation\ValidationException;
class VisitorController extends Controller
{
    public function index()
    {

        return Inertia::render('Security/Visitor/VisitorDashboard');
    }

    public function refreshVisitorTablePage(Request $request)
    {
        $limit = $request->input('limit', 10);

        $date = Carbon::now()->format('Y-m-d');
        $startOfDay = Carbon::today();
        $endOfDay = Carbon::now();

        // Get all visitors
        $visitor = Visitor::with('gatePass:id,pass_number')
            ->whereDate('date', $date)
            ->latest()
            ->take($limit)
            ->get();

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

    public function getVisitorForm($siteCode)
    {
        $site = Site::where('site_code', $siteCode)->firstOrFail();

        //return form page
        return Inertia::render(
            'Security/Visitor/VisitorForm',
            [
                'site' => $site,
            ]
        );
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
            'site_id' => 'required|integer',
            'time_in' => 'nullable|string',
            'time_out' => 'nullable|string',
            'time_register' => 'nullable',
            'date' => 'nullable|date',
            'visitor_type' => 'required|string',
            'person_to_meet' => 'nullable|string',
            'other_reasons' => 'nullable|string',
            'vehicle_number' => 'nullable|string',
            'visitor_company' => 'nullable|string',
            'video_watched' => 'nullable|boolean',
            'security_guidelines_confirmed' => 'nullable|boolean',
            'visitors' => 'required|array|min:1',
            'visitors.*.visitor_name' => 'required|string',
            'visitors.*.id_type' => 'required|in:IC,Passport',
            'visitors.*.id_number' => 'required|string',
            'visitors.*.phone_number' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $timeRegister = $validated['time_register'] ??= now()->format('H:i');
            $date = $validated['date'] ??= now()->format('Y-m-d');
            $company = $validated['visitor_company'] ?: "N/A";
            $vehicle_number = $validated['vehicle_number'] ?: "N/A";

            $createdVisitors = [];

            foreach ($validated['visitors'] as $visitor) {
                // Clean IC number (digits only)
                $ic = $visitor['id_type'] === 'IC'
                    ? preg_replace('/\D/', '', $visitor['id_number'])
                    : "N/A";

                $passport = $visitor['id_type'] === 'Passport' ? $visitor['id_number'] : "N/A";

                $rawPhone = isset($visitor['phone_number']) ? $visitor['phone_number'] : null;

                if ($rawPhone) {
                    // Remove all non-digit characters (e.g., spaces, dashes)
                    $digitsOnly = preg_replace('/\D/', '', $rawPhone);

                    // If it's the bypass code, return 'N/A'
                    $phone = ($digitsOnly === '0000000000') ? 'N/A' : $digitsOnly;
                } else {
                    $phone = null;
                }

                $pass_id = $this->getPassNumber($validated['visitor_type']);

                $new_visitor = Visitor::create([
                    'visitor_name' => $visitor['visitor_name'],
                    'gate_pass_id' => $pass_id->id,
                    'ic_number' => $ic,
                    'passport' => $passport,
                    'phone_number' => $phone,
                    'purpose' => $validated['purpose'],
                    'remarks' => $validated['remarks'] ?? null,
                    'site_id' => $validated['site_id'],
                    'time_register' => $timeRegister,
                    'person_to_meet' => $validated['person_to_meet'] ?? null,
                    'other_reasons' => $validated['other_reasons'] ?? null,
                    'date' => $date,
                    'visitor_type' => $validated['visitor_type'],
                    'vehicle_number' => $vehicle_number,
                    'visitor_company' => $company,
                    'is_acknowledge' => $validated['video_watched'] && $validated['security_guidelines_confirmed'],
                ]);

                //Broadcast event
                event(new VisitorRegistered($new_visitor));

                //Only store/update acknowledgement record if they really watched & confirmed
                if ($validated['video_watched'] && $validated['security_guidelines_confirmed']) {
                    VisitorAcknowledgement::updateOrCreate(
                        [
                            'id_type' => $visitor['id_type'],
                            'id_number' => $visitor['id_number'],
                        ],
                        [
                            'acknowledged_at' => now(),
                        ]
                    );
                }

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
        // ✅ Normalize visitor type
        if (
            str_starts_with($visitor_type, 'inbound-') ||
            str_starts_with($visitor_type, 'outbound-')
        ) {
            $visitor_type = 'driver';
        }

        return DB::transaction(function () use ($visitor_type) {
            $gate_pass = GatePass::where('pass_type', $visitor_type)
                ->where('state', 'free')
                ->lockForUpdate() // Prevents race condition
                ->first();

            if (!$gate_pass) {
                throw new Exception('There are no available passes');
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

    public function checkOut($id)
    {
        DB::transaction(function () use ($id) {
            $visitor = Visitor::findOrFail($id);
            $visitor->time_out = now();

            // Find and free the related gate pass
            $gate_pass = GatePass::find($visitor->gate_pass_id);
            if ($gate_pass) {
                $gate_pass->state = 'free';
                $gate_pass->save();
            }

            if ($visitor->time_in) {
                $visitor->duration = Carbon::parse($visitor->time_in)->diffInMinutes(now());
            }

            $visitor->save();
        });

        return redirect()->back();
    }

    public function scanByPass(Request $request)
    {
        $pass_number = $request->input('pass_number');

        return DB::transaction(function () use ($pass_number) {
            // Find visitor by pass
            $visitor = Visitor::whereHas('gatePass', function ($q) use ($pass_number) {
                $q->where('pass_number', $pass_number);
            })
                ->where(function ($query) {
                    $query->whereNull('time_out') // still in premises
                        ->orWhereNull('time_in'); // not yet checked in
                })
                ->with('gatePass')
                ->first();

            if (!$visitor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active visitor found for this gate pass.'
                ], 404);
            }

            // Case 1: Check-in
            if (is_null($visitor->time_in)) {
                $visitor->time_in = now();
                $visitor->save();

                // Mark gate pass as occupied
                if ($visitor->gatePass) {
                    $visitor->gatePass->state = 'occupied';
                    $visitor->gatePass->save();
                }

                return response()->json([
                    'status' => 'success',
                    'action' => 'check-in',
                    'message' => 'Visitor successfully checked in.',
                    'visitor' => $visitor
                ]);
            }

            // Case 2: Check-out
            if (is_null($visitor->time_out)) {
                $visitor->time_out = now();
                $visitor->duration = Carbon::parse($visitor->time_in)->diffInMinutes(now());
                $visitor->save();

                // Release the gate pass
                if ($visitor->gatePass) {
                    $visitor->gatePass->state = 'free';
                    $visitor->gatePass->save();
                }

                return response()->json([
                    'status' => 'success',
                    'action' => 'check-out',
                    'message' => 'Visitor successfully checked out.',
                    'visitor' => $visitor
                ]);
            }

            // Case 3: Already checked in and out
            return response()->json([
                'status' => 'error',
                'message' => 'This visitor has already completed their visit.'
            ], 400);
        });
    }

    //function to update the acknowledge
    public function checkAcknowledgement(Request $request)
    {
        $request->validate([
            'id_type' => 'required | string',
            'id_number' => 'required | string'
        ]);

        $ack = VisitorAcknowledgement::where('id_type', $request->id_type)
            ->where('id_number', $request->id_number)
            ->where('acknowledged_at', '>=', now()->subYear())
            ->first();

        return response()->json([
            'acknowledged' => (bool) $ack,
        ]);
    }

    public function editRemarks(Request $request, $visitorId)
    {
        try {
            // Find the visitor
            $visitor = Visitor::findOrFail($visitorId);

            // Validate the request
            $request->validate([
                'remarks' => 'nullable|string|max:500', // Adjust max length as needed
            ]);

            // Update the remarks
            $visitor->remarks = $request->input('remarks');
            $visitor->save();

            return response()->json([
                'success' => true,
                'message' => 'Remarks updated successfully',
                'data' => [
                    'id' => $visitor->id,
                    'remarks' => $visitor->remarks,
                    'updated_at' => $visitor->updated_at
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor not found',
                'error' => 'The specified visitor does not exist'
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating remarks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
