<?php

namespace App\Http\Controllers;

use App\Models\VisitorCompany;
use Illuminate\Http\Request;

class VisitorCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $visitorCompany = VisitorCompany::all();

    }

    //get list of visitor company
    public function getVisitorCompany()
    {
        $visitor_company = VisitorCompany::all();

        return response()->json([
            'visitor_company' => $visitor_company,
        ]);
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
    public function show(VisitorCompany $visitorCompany)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VisitorCompany $visitorCompany)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VisitorCompany $visitorCompany)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VisitorCompany $visitorCompany)
    {
        //
    }
}
