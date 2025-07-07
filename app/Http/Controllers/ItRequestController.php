<?php

namespace App\Http\Controllers;

use App\Models\ItRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ItRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $form = ItRequest::with('user:id,name')->get();

        return Inertia::render('Table', [
            'data' => $form,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Form/It/ITRequestForm');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_date' => ['required', 'date'],
            'type' => ['required', 'in:laptop,desktop,others'],
            'justification' => ['required', 'in:new staff,replacement,others'],
            'urgency' => ['required', 'in:low,medium,high'],
            'description' => ['required', 'string', 'min:5'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'min:100', 'max:1024'], // size in kilobytes
        ]);

        // Handle file storage if present
        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachments');
        }

        $validated['user_id'] = auth()->id();

        // Store to DB
        ItRequest::create($validated);

        return redirect()->back()->with('success', 'Request submitted successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ItRequest $itRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItRequest $itRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItRequest $itRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItRequest $itRequest)
    {
        //
    }
}
