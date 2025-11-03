<?php

namespace App\Http\Controllers;

use App\Models\GatePass;
use Illuminate\Http\Request;

class GatePassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $site = $request->input('site');

        $gate_pass = GatePass::where('site_id',$site)->get();

        return response()->json($gate_pass);
    }
    public function getGatePassData(Request $request)
    {
        $site = $request->input('site');

        $available_gatepass = GatePass::where('state','free')
        ->where('site_id', $site)
        ->count();

        return response()->json([
            'available_gatepass' => $available_gatepass,
        ]);
    }
}
