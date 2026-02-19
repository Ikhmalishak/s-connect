<?php

namespace App\Http\Controllers\ManageVisitor;

use App\Http\Controllers\Controller;
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
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Mpdf\Mpdf;
use Illuminate\Support\Str;
use App\Exports\VisitorsExport;
use Maatwebsite\Excel\Facades\Excel;
use function activity;

class VisitorController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        if ($user->hasRole(['superadmin', 'admin'])) {
            return Inertia::render('ManageVisitor/Dashboard/AdminDashboard');
        }

        if ($user->hasRole('guard')) {
            return Inertia::render('ManageVisitor/Dashboard/GuardDashboard');
        }

        if ($user->hasRole('receptionist')) {
            return Inertia::render('ManageVisitor/Dashboard/ReceptionistDashboard');
        }

        abort(403);
    }

    public function getReportDashboard()
    {
        return Inertia::render('ManageVisitor/Dashboard/VisitorReportDashboard');
    }

    public function getVisitorForm($siteCode)
    {
        $site = Site::where('site_code', $siteCode)->firstOrFail();

        //return form page
        return Inertia::render(
            'ManageVisitor/Form/RegisterVisitorForm',
            [
                'site' => $site,
            ]
        );
    }

    public function getVisitorTableData(Request $request)
    {
        $limit = $request->input('limit', 25);
        $keyword = $request->input('keyword');
        $user = auth()->user();
        $site = $user->site->id;
        $filterSite = $request->input('site');

        if ($user->hasAnyRole(['admin', 'superadmin'])) {

            $query = Visitor::with([
                'gatePass:id,pass_number',
                'site:id,site_code',
                'acknowledgements',
                'shipmentTransport' => function ($q) {
                    $q->select('shipment_transports.id', 'shipment_transports.transport_number', 'shipment_transports.driver_name');
                }
            ]);

            // Admin filter based on dropdown selection
            if ($filterSite) {
                $query->where('site_id', $filterSite);
            }

        } else {

            // Non-admin always restricted to own site
            $query = Visitor::with([
                'gatePass:id,pass_number',
                'acknowledgements',
                'shipmentTransport' => function ($q) {
                    $q->select('shipment_transports.id', 'shipment_transports.transport_number', 'shipment_transports.driver_name');
                }
            ])
                ->whereDate('date', now()->toDateString())
                ->where('site_id', $site);
        }

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

    public function getVisitorData(Request $request)
    {
        $date = Carbon::today()->toDateString();
        $startOfDay = Carbon::today();
        $endOfDay = Carbon::now();
        $user = auth()->user();
        $userSite = $user->site->id;   // non-admin restriction
        $filterSite = $request->input('site'); // admin filter

        // Build site filtering rule
        $siteFilter = function ($q) use ($user, $userSite, $filterSite) {

            if ($user->hasRole('admin')) {
                // admin can filter by selected site (optional)
                if ($filterSite) {
                    $q->where('site_id', $filterSite);
                }
            } else {
                // non-admin ALWAYS restricted to their own site
                $q->where('site_id', $userSite);
            }
        };

        // 1. Inside visitors
        $visitor_inside = Visitor::whereNotNull('time_in')
            ->whereNull('time_out')
            ->whereDate('date', $date)
            ->where($siteFilter)
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->groupBy('visitor_type')
            ->get();

        // 2. Today (already out)
        $visitor_today = Visitor::whereDate('date', $date)
            ->whereNotNull('time_out')
            ->where($siteFilter)
            ->selectRaw('visitor_type, COUNT(*) as total')
            ->groupBy('visitor_type')
            ->get();

        // 3. Time in by hour
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

        // 4. Time out by hour
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

        // 5. Total visitors today
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

    public function getVisitorInside(Request $request)
    {
        $user = auth()->user();
        $userSite = $user->site->id;   // non-admin restriction
        $filterSite = $request->input('site'); // admin filter

        // Build site filtering rule
        $siteFilter = function ($q) use ($user, $userSite, $filterSite) {

            if ($user->hasRole('admin')) {
                // admin can filter by selected site (optional)
                if ($filterSite) {
                    $q->where('site_id', $filterSite);
                }
            } else {
                // non-admin ALWAYS restricted to their own site
                $q->where('site_id', $userSite);
            }
        };
        $date = now()->toDateString(); // or Carbon::today()->toDateString()

        $visitor_inside = Visitor::whereNotNull('time_in')
            ->whereNull('time_out')
            ->where($siteFilter)
            ->whereDate('date', $date)
            ->with('gatePass')
            ->get();

        return response()->json([
            'data' => $visitor_inside
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
            'container_number' => 'nullable|string',
            'video_watched' => 'nullable|boolean',
            'security_guidelines_confirmed' => 'nullable|boolean',
            'visitors' => 'required|array|min:1',
            'visitors.*.visitor_name' => 'required|string',
            'visitors.*.id_type' => 'required|in:IC,Passport',
            'visitors.*.id_number' => 'required|string',
            'visitors.*.phone_number' => 'required|string',
        ]);

        // Additional validation for shipping visitors
        if ($validated['visitor_type'] === 'shipping') {
            $request->validate([
                'container_number' => 'required|string',
            ]);

            // Check if container exists and has correct stage
            $container = \App\Models\ShipmentTransport::where('transport_number', $validated['container_number'])
                ->where('site_id', $validated['site_id'])
                // ->where('stage', 'onboarding_ready')
                ->first();

            if (!$container) {
                return response()->json([
                    'error' => 'Container not found, not in correct stage, or does not belong to this site.'
                ], 422);
            }

            // Store the container ID for later use
            $validated['shipment_transport_id'] = $container->id;
        }

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
                    $pass_id = $this->getPassNumber($validated['visitor_type'], $validated['site_id']);
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

                // Create shipping assignment for shipping visitors
                if ($validated['visitor_type'] === 'shipping' && $validated['shipment_transport_id']) {
                    \App\Models\ShipmentTransportDriver::create([
                        'visitor_id' => $new_visitor->id,
                        'shipment_transport_id' => $validated['shipment_transport_id'],
                    ]);
                }

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
                        'outbound-shipment/transfer',
                        'shipping',
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

    public function getPassNumber($visitor_type, $site_id)
    {
        if (
            str_starts_with($visitor_type, 'inbound-') ||
            str_starts_with($visitor_type, 'outbound-') ||
            $visitor_type === 'shipping'
        ) {
            $visitor_type = 'driver';
        }

        return DB::transaction(function () use ($visitor_type, $site_id) {
            $gate_pass = GatePass::where('pass_type', $visitor_type)
                ->where('site_id', $site_id)
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
        $site = $request->input('site');

        if (Str::startsWith($code, 'SKP')) {
            return $this->scanAcknowledgement($code);
        }

        if (Str::startsWith($code, 'V') || Str::startsWith($code, 'C') || Str::startsWith($code, 'D')) {
            return $this->scanGatePass($code);
        }

        activity()
            ->causedBy(auth()->user())
            ->log("Scan Gate Pass");

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

            activity()
                ->causedBy(auth()->user())
                ->log("Verify Security Acknowledgement");

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
        $site_id = auth()->user()->site->id;

        $visitor = Visitor::whereHas('gatePass', function ($q) use ($passNumber, $site_id) {
            $q->where('pass_number', $passNumber)
                ->where('site_id', $site_id); // ✅ ensure pass belongs to this site
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
            $ack = $visitor->acknowledgements->first();

            if (!in_array($visitor->visitor_type, ['inbound-shipment/transfer', 'outbound-shipment/transfer'])) {
                $ack = $visitor->acknowledgements->first();

                if (!$ack || is_null($ack->acknowledged_at) || is_null($ack->acknowledged_at_security)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Visitor cannot check out until both staff and guard acknowledgements are completed.'
                    ], 400);
                }
            }

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

            activity()
                ->causedBy(auth()->user())
                ->log("Update Visitor Remarks");

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

    public function generateReport(Request $request)
    {
        // Set global limit untuk proses ini
        ini_set('max_execution_time', 120);
        set_time_limit(120);

        $query = Visitor::query();

        if ($request->dateRange) {
            $start = $request->dateRange['start'];
            $end = $request->dateRange['end'];

            if ($start && $end) {
                $startDate = sprintf('%04d-%02d-%02d', $start['year'], $start['month'], $start['day']);
                $endDate = sprintf('%04d-%02d-%02d', $end['year'], $end['month'], $end['day']);

                $query->whereBetween('date', [$startDate, $endDate]);
            }
        }

        if ($request->visitor_type && $request->visitor_type !== "all") {
            // ✅ Handle multiple selections (array from checkbox)
            if (is_array($request->visitor_type)) {
                $query->whereIn('visitor_type', $request->visitor_type);
            } else {
                $query->where('visitor_type', $request->visitor_type);
            }
        }

        if ($request->visitor_company && $request->visitor_company !== "all") {
            $query->where('visitor_company', $request->visitor_company);
        }

        $query->orderBy('id', 'desc');

        activity()
            ->causedBy(auth()->user())
            ->log("Generate Report");

        // Return Excel file instead of PDF
        return Excel::download(new VisitorsExport($query), 'visitors-report.xlsx');
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
            'tempDir' => storage_path('tmp/mpdf')
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($pdfPath, 'F');

        $printerName = "Brother_QL_820NWB";
        $cmd = "lp -d {$printerName} -o PageSize=Custom.62x30mm -o print-scaling=none -o CutMedia=Auto " . escapeshellarg($pdfPath);
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json(['status' => 'error', 'output' => $output], 500);
        }

        return response()->json(['status' => 'success']);
    }
}
