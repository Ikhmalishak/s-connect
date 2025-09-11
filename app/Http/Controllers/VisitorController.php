<?php

namespace App\Http\Controllers;

use App\Events\GuardAcknowledgeVisitor;
use App\Events\GuardScanInAndOut;
use App\Models\GatePass;
use App\Models\Site;
use App\Models\Visitor;
use App\Models\VisitorAcknowledgement;
use App\Models\VisitorStaffAcknowledgement;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Events\VisitorRegistered;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Mpdf\Mpdf;
use Illuminate\Support\Str;

class VisitorController extends Controller
{
    public function getVisitorDashboard()
    {

        return Inertia::render('Security/Visitor/VisitorDashboard');
    }

    public function getAdminVisitorDashboard(Request $request)
    {
        return Inertia::render('Security/Visitor/AdminVisitorDashboard');
    }
    public function getAdminVisitorReportingDashboard(Request $request)
    {
        return Inertia::render('Security/Visitor/AdminVisitorReportingDashboard');
    }

    public function getStaffVerification(Request $request)
    {
        return Inertia::render('Security/Visitor/StaffVerification');
    }

    public function getAdminVisitorTableData(Request $request)
    {
        $limit = $request->input('limit', 25);
        $keyword = $request->input('keyword');
        $site = $request->input('site');

        $query = Visitor::with(['gatePass:id,pass_number', 'site:id,site_code','acknowledgements']);

        if ($site) {
            $query->whereHas('site', function ($q) use ($site) {
                $q->where('site_code', $site);
            });
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword, $site) {
                $q->where('visitor_name', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('gatePass', function ($sub) use ($keyword) {
                        $sub->where('pass_number', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhere('date', 'LIKE', "%{$keyword}%") // Database format: 2025-08-15
                    ->orWhereRaw("DATE_FORMAT(date, '%d/%m/%Y') LIKE ?", ["%{$keyword}%"]) // Display format: 15/08/2025
                    ->orWhere('vehicle_number', 'LIKE', "%{$keyword}%")
                    ->orWhere('visitor_company', "LIKE", "%{$keyword}%")
                    ->orWhere('purpose', "LIKE", "%{$keyword}%");
            });
        }

        // Apply limit only if no search keyword
        if (!$keyword) {
            $query->take($limit);
        }

        $visitors = $query->latest()->get();
        return response()->json([
            'visitor' => $visitors,
        ]);
    }

    public function getVisitorTableData(Request $request)
    {
        $limit = $request->input('limit', 25);
        $keyword = $request->input('keyword');

        $query = Visitor::with(['gatePass:id,pass_number', 'acknowledgements'])
            ->whereDate('date', now()->toDateString());

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('visitor_name', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('gatePass', function ($sub) use ($keyword) {
                        $sub->where('pass_number', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhere('date', 'LIKE', "%{$keyword}%") // Database format: 2025-08-15
                    ->orWhereRaw("DATE_FORMAT(date, '%d/%m/%Y') LIKE ?", ["%{$keyword}%"]) // Display format: 15/08/2025
                    ->orWhere('vehicle_number', 'LIKE', "%{$keyword}%")
                    ->orWhere('visitor_company', "LIKE", "%{$keyword}%")
                    ->orWhere('purpose', "LIKE", "%{$keyword}%");
            });
        }

        // Apply limit only if no search keyword
        if (!$keyword) {
            $query->take($limit);
        }

        $visitors = $query->latest()->get();

        return response()->json([
            'visitor' => $visitors,
        ]);
    }

    public function refreshVisitorTablePage(Request $request)
    {
        $date = Carbon::now()->format('Y-m-d');
        $startOfDay = Carbon::today();
        $endOfDay = Carbon::now();

        $site = $request->input('site'); // site_code

        // Base query builder with optional site filter
        $siteFilter = function ($query) use ($site) {
            if ($site) {
                $query->whereHas('site', function ($q) use ($site) {
                    $q->where('site_code', $site);
                });
            }
        };

        // Currently inside
        $visitor_inside = Visitor::whereNotNull('time_in')
            ->whereNull('time_out')
            ->whereDate('date', $date)
            ->where($siteFilter)
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->groupBy('visitor_type')
            ->get();

        // Visitors out
        $visitor_today = Visitor::whereDate('date', $date)
            ->whereNotNull('time_out')
            ->where($siteFilter)
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
            ->where($siteFilter)
            ->groupBy(DB::raw("HOUR(time_in)"))
            ->orderBy("hour")
            ->get();

        // Get time_out count grouped by hour
        $visitor_out_by_hour = Visitor::select([
            DB::raw("HOUR(time_out) as hour"),
            DB::raw("COUNT(*) as total_out")
        ])
            ->whereBetween('time_out', [$startOfDay, $endOfDay])
            ->whereDate('date', $date)
            ->where($siteFilter)
            ->groupBy(DB::raw("HOUR(time_out)"))
            ->orderBy("hour")
            ->get();

        $total_visitor_today = Visitor::whereDate('date', $date)
            ->where($siteFilter)
            ->count();

        return response()->json([
            'visitor_inside' => $visitor_inside,
            'visitor_today' => $visitor_today,
            'visitor_in_by_hour' => $visitor_in_by_hour,
            'visitor_out_by_hour' => $visitor_out_by_hour,
            'total_visitor_today' => $total_visitor_today,
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
            $failedCreatedVisitors = [];

            foreach ($validated['visitors'] as $visitor) {

                // Check if foreigner is doing sorting/rework → reject
                if ($visitor['id_type'] === 'Passport' && $validated['purpose'] === 'Sorting/Rework') {
                    $failedCreatedVisitors[] = [
                        'visitor' => $visitor,
                        'reason' => 'Foreign visitors are not allowed for Sorting/Rework.'
                    ];
                    continue; // Skip creation for this visitor
                }

                // Clean IC number (digits only)
                $ic = $visitor['id_type'] === 'IC'
                    ? preg_replace('/\D/', '', $visitor['id_number'])
                    : "N/A";

                $passport = $visitor['id_type'] === 'Passport' ? $visitor['id_number'] : "N/A";

                $rawPhone = $visitor['phone_number'] ?? null;
                if ($rawPhone) {
                    $digitsOnly = preg_replace('/\D/', '', $rawPhone);
                    $phone = ($digitsOnly === '0000000000') ? 'N/A' : $digitsOnly;
                } else {
                    $phone = null;
                }

                // Get pass number - handle failure gracefully
                try {
                    $pass_id = $this->getPassNumber($validated['visitor_type']);
                } catch (Exception $e) {
                    // Add to failed and continue instead of returning
                    $failedCreatedVisitors[] = [
                        'visitor' => $visitor,
                        'reason' => $e->getMessage()
                    ];
                    continue; // Skip this visitor and process the next one
                }

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

                // Broadcast event
                event(new VisitorRegistered());

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

            // After foreach loop, still inside the transaction
            $ackRow = null;

            if (!empty($createdVisitors)) {
                $visitorIds = array_column($createdVisitors, 'id');

                // Drivers = inbound/outbound shipment transfer → skip acknowledgement + sticker
                if (
                    !in_array($validated['visitor_type'], [
                        'inbound-shipment/transfer',
                        'outbound-shipment/transfer'
                    ])
                ) {
                    $ackRow = $this->createAcknowledgementWithVisitors($visitorIds);

                    $this->printSticker(
                        $ackRow->id,
                        count($visitorIds),
                        $ackRow->ack_number
                    );
                }
            }

            return response()->json([
                'created' => $createdVisitors,
                'failed' => $failedCreatedVisitors,
                'acknowledgement_id' => $ackRow?->id,
            ]);

        });
    }

    public function getPassNumber($visitor_type)
    {
        if (
            str_starts_with($visitor_type, 'inbound-') ||
            str_starts_with($visitor_type, 'outbound-')
        ) {
            $visitor_type = 'driver';
        }

        return DB::transaction(function () use ($visitor_type) {
            $gate_pass = GatePass::where('pass_type', $visitor_type)
                ->where('state', 'free')
                ->lockForUpdate()
                ->first();

            if (!$gate_pass) {
                throw new Exception('No available gate passes for this visitor type.');
            }

            $gate_pass->state = 'occupied';
            $gate_pass->save();

            return $gate_pass;
        });
    }

    protected function createAcknowledgementWithVisitors(array $visitorIds): VisitorStaffAcknowledgement
    {
        // Step 1: Create empty acknowledgement row
        $ackRow = VisitorStaffAcknowledgement::create([
            'acknowledged_by' => null,
            'staff_id' => null,
            'acknowledged_at' => null,
            'acknowledged_by_security' => null,
            'acknowledged_at_security' => null,
        ]);

        // Step 2: Attach visitors into pivot
        $ackRow->visitors()->attach($visitorIds);

        return $ackRow;
    }

    public function scan(Request $request)
    {
        $code = $request->input('pass_number');

        if (Str::startsWith($code, 'SKP')) {
            return $this->scanAcknowledgement($code);
        }

        if (Str::startsWith($code, 'V') || Str::startsWith($code, 'C') || Str::startsWith($code, 'D')) {
            return $this->scanGatePass($code);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid QR code format.'
        ], 400);
    }

    private function scanAcknowledgement(string $ackNumber)
    {
        $ack = VisitorStaffAcknowledgement::where('ack_number', $ackNumber)->first();

        if (!$ack) {
            return response()->json([
                'status' => 'error',
                'message' => 'Acknowledgement not found.'
            ], 404);
        }

        // Guard acknowledgement (only if staff already did)
        if (is_null($ack->acknowledged_at_security)) {
            if (is_null($ack->acknowledged_at)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Staff must acknowledge first before guard.'
                ], 400);
            }

            $ack->acknowledged_at_security = now();
            $ack->acknowledged_by_security = auth()->user()->name ?? 'Guard';
            $ack->save();

            event(new GuardAcknowledgeVisitor());

            return response()->json([
                'status' => 'success',
                'action' => 'acknowledge-guard',
                'message' => 'Visitor acknowledged by security.',
                'acknowledgement' => $ack
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Both staff and guard have already acknowledged.'
        ], 400);
    }

    private function scanGatePass(string $passNumber)
    {
        $visitor = Visitor::whereHas('gatePass', function ($q) use ($passNumber) {
            $q->where('pass_number', $passNumber);
        })
            ->where(function ($query) {
                $query->whereNull('time_out') // still in premises
                    ->orWhereNull('time_in'); // not yet checked in
            })
            ->with(['gatePass', 'acknowledgements'])
            ->first();

        if (!$visitor) {
            return response()->json([
                'status' => 'error',
                'message' => 'No visitor found for this gate pass.'
            ], 404);
        }

        // Check-in
        if (is_null($visitor->time_in)) {
            $visitor->time_in = now();
            $visitor->save();

            if ($visitor->gatePass) {
                $visitor->gatePass->state = 'occupied';
                $visitor->gatePass->save();
            }

            event(new GuardScanInAndOut());

            return response()->json([
                'status' => 'success',
                'action' => 'check-in',
                'message' => 'Visitor successfully checked in.',
                'visitor' => $visitor
            ]);
        }

        // Check-out (with restriction: staff + guard must acknowledge first)
        if (is_null($visitor->time_out)) {
            //$ack = $visitor->acknowledgements->first();

            // if (!$ack || is_null($ack->acknowledged_at) || is_null($ack->acknowledged_at_security)) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Visitor cannot check out until both staff and guard acknowledgements are completed.'
            //     ], 400);
            // }

            $visitor->time_out = now();
            $visitor->duration = Carbon::parse($visitor->time_in)->diffInMinutes(now());
            $visitor->save();

            if ($visitor->gatePass) {
                $visitor->gatePass->state = 'free';
                $visitor->gatePass->save();
            }

            event(new GuardScanInAndOut());

            return response()->json([
                'status' => 'success',
                'action' => 'check-out',
                'message' => 'Visitor successfully checked out.',
                'visitor' => $visitor
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'This visitor has already completed their visit.'
        ], 400);
    }

    // public function scanByPass(Request $request)
    // {
    //     $pass_number = $request->input('pass_number');

    //     return DB::transaction(function () use ($pass_number) {
    //         // Find visitor by pass
    //         $visitor = Visitor::whereHas('gatePass', function ($q) use ($pass_number) {
    //             $q->where('pass_number', $pass_number);
    //         })
    //             ->where(function ($query) {
    //                 $query->whereNull('time_out') // still in premises
    //                     ->orWhereNull('time_in'); // not yet checked in
    //             })
    //             ->with('gatePass')
    //             ->first();

    //         if (!$visitor) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'No active visitor found for this gate pass.'
    //             ], 404);
    //         }

    //         // Case 1: Check-in
    //         if (is_null($visitor->time_in)) {
    //             $visitor->time_in = now();
    //             $visitor->save();

    //             // Mark gate pass as occupied
    //             if ($visitor->gatePass) {
    //                 $visitor->gatePass->state = 'occupied';
    //                 $visitor->gatePass->save();
    //             }

    //             return response()->json([
    //                 'status' => 'success',
    //                 'action' => 'check-in',
    //                 'message' => 'Visitor successfully checked in.',
    //                 'visitor' => $visitor
    //             ]);
    //         }

    //         // Case 2: Check-out
    //         if (is_null($visitor->time_out)) {
    //             $visitor->time_out = now();
    //             $visitor->duration = Carbon::parse($visitor->time_in)->diffInMinutes(now());
    //             $visitor->save();

    //             // Release the gate pass
    //             if ($visitor->gatePass) {
    //                 $visitor->gatePass->state = 'free';
    //                 $visitor->gatePass->save();
    //             }

    //             return response()->json([
    //                 'status' => 'success',
    //                 'action' => 'check-out',
    //                 'message' => 'Visitor successfully checked out.',
    //                 'visitor' => $visitor
    //             ]);
    //         }

    //         // Case 3: Already checked in and out
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'This visitor has already completed their visit.'
    //         ], 400);
    //     });
    // }

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

    // public function generateReport(Request $request)
    // {
    //     $query = Visitor::query();

    //     if ($request->dateRange) {
    //         $start = $request->dateRange['start'];
    //         $end = $request->dateRange['end'];

    //         if ($start && $end) {
    //             // build date strings in Y-m-d format
    //             $startDate = sprintf('%04d-%02d-%02d', $start['year'], $start['month'], $start['day']);
    //             $endDate = sprintf('%04d-%02d-%02d', $end['year'], $end['month'], $end['day']);

    //             $query->whereBetween('date', [$startDate, $endDate]);
    //         }
    //     }

    //     // Filter visitor type
    //     if ($request->visitor_type && $request->visitor_type !== "all") {
    //         $query->where('visitor_type', $request->visitor_type);
    //     }

    //     // Filter company
    //     if ($request->visitor_company && $request->visitor_company !== "all") {
    //         $query->where('visitor_company', $request->visitor_company);
    //     }

    //     $visitors = $query->get();

    //     $total_visitors = Visitor::all();

    //     $visitor_counts = $total_visitors->groupBy('visitor_type')->map->count();

    //     $chartData = [
    //         'type' => 'pie',
    //         'data' => [
    //             'labels' => $visitor_counts->keys(),
    //             'datasets' => [
    //                 [
    //                     'data' => $visitor_counts->values(),
    //                     'backgroundColor' => ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0'],
    //                 ]
    //             ],
    //         ],
    //     ];

    //     $chartDataDonut = [
    //         'type' => 'doughnut',
    //         'data' => [
    //             'labels' => $visitor_counts->keys(),
    //             'datasets' => [
    //                 [
    //                     'data' => $visitor_counts->values(),
    //                     'backgroundColor' => ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0'],
    //                     'label' => 'Dataset 1',
    //                 ]
    //             ],
    //         ],
    //     ];

    //     // Get chart image from QuickChart
    //     $chartUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($chartData));
    //     $donutChartUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($chartDataDonut));

    //     // Get image as base64
    //     $imageData = base64_encode(file_get_contents($chartUrl));
    //     $imageSrc = 'data:image/png;base64,' . $imageData;

    //     $imageData2 = base64_encode(file_get_contents($donutChartUrl));
    //     $imageSrc2 = 'data:image/png;base64,' . $imageData2;


    //     $pdf = PDF::loadView('report.advanced-reports', compact('visitors', 'imageSrc', 'imageSrc2'));

    //     return $pdf->download('visitors-reports.pdf');
    // }

    public function getStatisticAllSites(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        // total visitor
        $total_visitor = Visitor::where('visitor_type', 'visitor')
            ->whereDate('date', $date)
            ->count();

        // total driver (inbound-xxx OR outbound-xxx)
        $total_driver = Visitor::where(function ($query) {
            $query->where('visitor_type', 'like', 'inbound-%')
                ->orWhere('visitor_type', 'like', 'outbound-%');
        })
            ->whereDate('date', $date)
            ->count();

        // total contractor
        $total_contractor = Visitor::where('visitor_type', 'contractor')
            ->whereDate('date', $date)
            ->count();

        // total all
        $total_all = $total_visitor + $total_driver + $total_contractor;

        return response()->json([
            'date' => $date,
            'total_visitor' => $total_visitor,
            'total_driver' => $total_driver,
            'total_contractor' => $total_contractor,
            'total_all' => $total_all,
        ]);
    }


    public function getStatisticBySites(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        $site_1 = Visitor::where('site_id', '1')
            ->whereDate('date', $date)
            ->whereNotNull('time_out')
            ->groupBy('visitor_type')
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->get();

        $site_2 = Visitor::where('site_id', '2')
            ->whereDate('date', $date)
            ->whereNotNull('time_out')
            ->groupBy('visitor_type')
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->get();

        $site_3 = Visitor::where('site_id', '3')
            ->whereDate('date', $date)
            ->whereNotNull('time_out')
            ->groupBy('visitor_type')
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->get();

        $site_4 = Visitor::where('site_id', '4')
            ->whereDate('date', $date)
            ->whereNotNull('time_out')
            ->groupBy('visitor_type')
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->get();

        return response()->json([
            'message' => "success",
            'site1' => $site_1,
            'site2' => $site_2,
            'site3' => $site_3,
            'site4' => $site_4,
        ]);
    }

    public function printSticker($ackId, $totalPax, $ackNumber)
    {
        $qrPath = storage_path("app/public/qr_ack_{$ackId}.png");
        QrCode::format('png')->size(360)->margin(0)->generate($ackNumber, $qrPath);

        $html = view('sticker', [
            'qr' => base64_encode(file_get_contents($qrPath)),
            'logo' => base64_encode(file_get_contents(public_path('assets/ss3.png'))),
            'ack_id' => $ackId,
            'total_pax' => $totalPax,
            'ack_number' => $ackNumber,
        ])->render();

        $pdfPath = storage_path("app/public/sticker_ack_{$ackId}.pdf");
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [62, 30], // mm
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($pdfPath, 'F');

        $printerName = "Brother_QL_820NWB";
        $cmd = "lp -d {$printerName} -o PageSize=Custom.62x30mm -o print-scaling=none -o CutMedia=Auto " . escapeshellarg($pdfPath);
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json(['status' => 'error', 'output' => $output], 500);
        }

        return response()->json(['status' => 'success', 'output' => $output]);
    }
}
