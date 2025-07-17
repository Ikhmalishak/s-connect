<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
class VisitorController extends Controller
{
    public function index()
    {
        //return index page with data
        $visitor = Visitor::with('visitorCompany')->get();

        return Inertia::render('Security/Visitor/VisitorTable', [
            'data' => $visitor,
        ]);
    }

    public function refreshVisitorTablePage()
    {
        $visitor = Visitor::with('visitorCompany')->latest()->get();
        return response()->json($visitor);
    }

    public function getVisitorForm()
    {
        //return form page
        return Inertia::render('Security/Visitor/VisitorForm');
    }

    public function getArchivedVisitorForm()
    {
        //return form page
        return Inertia::render('Security/Visitor/ArchivedVisitorForm');
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
        // Validate form data
        $validated = $request->validate([
            'visitor_name' => 'required|string',
            'id_type' => 'required|in:IC,Passport',
            'id_number' => 'required|string',
            'pass_number' => 'required|string',
            'phone_number' => 'required|string',
            'purpose' => 'required|string',
            'remarks' => 'nullable|string',
            'site' => 'required|string',
            'time_in' => 'nullable',
            'time_out' => 'nullable',
            'time_register' => 'nullable',
            'date' => 'nullable|date',
            'vehicle_number' => 'nullable|string',
            'visitor_company_id' => 'required|integer',
            'pax' => 'array',
            'pax.*.visitor_name' => 'required|string',
            'pax.*.id_type' => 'required|in:IC,Passport',
            'pax.*.id_number' => 'required|string',
            'pax.*.phone_number' => 'required|string',
        ]);

        $timeRegister = $validated['time_register'] ?? now()->format('H:i');
        $date = !empty($validated['date']) ? $validated['date'] : now()->format('Y-m-d');

        // Map ID fields for main visitor
        $mainIc = $validated['id_type'] === 'IC' ? $validated['id_number'] : null;
        $mainPassport = $validated['id_type'] === 'Passport' ? $validated['id_number'] : null;

        // Create main visitor
        Visitor::create([
            'visitor_name' => $validated['visitor_name'],
            'ic_number' => $mainIc,
            'passport' => $mainPassport,
            'pass_number' => $validated['pass_number'],
            'phone_number' => $validated['phone_number'],
            'purpose' => $validated['purpose'],
            'remarks' => $validated['remarks'],
            'site' => $validated['site'],
            'time_in' => $validated['time_in'],
            'time_out' => $validated['time_out'],
            'time_register' => $timeRegister,
            'date' => $date,
            'vehicle_number' => $validated['vehicle_number'],
            'visitor_company_id' => $validated['visitor_company_id'],
            'is_acknowledge' => true, // This assumes acknowledgment if passed
        ]);

        // Create pax rows
        if (!empty($validated['pax'])) {
            foreach ($validated['pax'] as $paxEntry) {
                $paxIc = $paxEntry['id_type'] === 'IC' ? $paxEntry['id_number'] : null;
                $paxPassport = $paxEntry['id_type'] === 'Passport' ? $paxEntry['id_number'] : null;

                Visitor::create([
                    'visitor_name' => $paxEntry['visitor_name'],
                    'ic_number' => $paxIc,
                    'passport' => $paxPassport,
                    'pass_number' => $validated['pass_number'],
                    'phone_number' => $paxEntry['phone_number'],
                    'purpose' => $validated['purpose'],
                    'remarks' => $validated['remarks'],
                    'site' => $validated['site'],
                    'time_in' => $validated['time_in'],
                    'time_out' => $validated['time_out'],
                    'time_register' => $timeRegister,
                    'date' => $date,
                    'vehicle_number' => $validated['vehicle_number'],
                    'visitor_company_id' => $validated['visitor_company_id'],
                    'is_acknowledge' => true,
                ]);
            }
        }

        return response()->json([
            'message' => 'Visitor registered successfully.'
        ]);
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

    public function show(Visitor $Visitor)
    {
        //
    }

    public function edit(Visitor $visitor)
    {
        return Inertia::render('Security/Visitor/VisitorForm', [
            'visitor' => $visitor
        ]);
    }

    public function update(Request $request, Visitor $visitor)
    {
        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:255'],
            'vehicle_number' => ['required', 'string', 'max:20'],
            'time_register' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'time_in' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'time_out' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'reasons' => ['required', 'string', 'max:200'],
            'ic_number' => ['required', 'string', 'size:12'],
            'pass_number' => ['required', 'string', 'max:20'],
            'phone_number' => ['required', 'string', 'max:20'],
            'visitor_company_id' => ['required', 'integer', 'exists:visitor_companies,id'],
        ]);

        $visitor->update($validated);

        return redirect('/visitor')->with('success', 'Visitor updated.');
    }

    public function destroy(Visitor $Visitor)
    {
        //
    }
}
