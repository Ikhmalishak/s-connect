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

    public function getVisitorForm()
    {
        //return form page
        return Inertia::render('Security/Visitor/VisitorForm');
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
        //validate the request
        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:255'],
            'vehicle_number' => ['required', 'string', 'max:20'],
            'time_register' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'time_in' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'time_out' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'visitor_company_id' => ['required', 'integer', 'exists:visitor_companies,id'],
            'purpose' => ['required', 'string'],
            'site' => ['required', 'string'],
            'person_to_meet' => ['nullable', 'string'],
            'remarks' => ['required', 'string', 'max:200'],
            'ic_number' => ['nullable', 'string', 'max:20', 'required_without:passport'],
            'passport' => ['nullable', 'string', 'max:20', 'required_without:ic_number'],
            'pass_number' => ['required', 'string', 'max:20'],
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        $validated['time_register'] = Carbon::now()->format('H:i');

        $visitor = Visitor::create($validated);

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
