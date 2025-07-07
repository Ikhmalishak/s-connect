<?php

namespace App\Http\Controllers;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        return Inertia::render('Form');
    }
    public function testSubmit(Request $request)
    {
        // For testing purposes, just return JSON
        return response()->json([
            'message' => '✅ Hello from testSubmit!',
            'data_received' => $request->all(),
        ]);
    }

    public function showTable(){

        $department = Department::all()->withRelationshipAutoloading();
        dd($department);
        return Inertia::render('Table',[
            'data' => $department,
        ]);
    }
}

