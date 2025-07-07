<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //return index page with data
        $visitor = Visitor::with('visitorCompany')->get();

        return Inertia::render('Security/Visitor/VisitorTable',[
            'data' => $visitor,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //return form page
        return Inertia::render('Security/Visitor/VisitorForm');
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
            'time_register' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'time_in' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'time_out' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'visitor_company_id' => ['required', 'integer', 'exists:visitor_companies,id'],
            'reasons' => ['nullable', 'string'],
            'ic_number' => ['nullable', 'string', 'max:20'],
            'pass_number' => ['nullable', 'string', 'max:20'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $visitor = Visitor::create($validated);

        return back()->with('success', 'Visitor registered successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Visitor $Visitor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Visitor $Visitor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Visitor $Visitor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Visitor $Visitor)
    {
        //
    }
}
