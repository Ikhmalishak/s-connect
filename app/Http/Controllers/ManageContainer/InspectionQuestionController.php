<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\InspectionQuestion;
use Illuminate\Http\Request;

class InspectionQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return InspectionQuestion::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InspectionQuestion $inspectionQuestion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InspectionQuestion $inspectionQuestion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InspectionQuestion $inspectionQuestion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InspectionQuestion $inspectionQuestion)
    {
        //
    }
}
