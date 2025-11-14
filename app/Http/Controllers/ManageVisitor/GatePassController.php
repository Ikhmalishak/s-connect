<?php

namespace App\Http\Controllers\ManageVisitor;

use App\Http\Controllers\Controller;
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

        $gate_pass = GatePass::where('site_id', $site)->get();
        $available_gatepass = GatePass::where('state', 'free')
            ->where('site_id', $site)
            ->count();

        return response()->json([
            'gate_pass' => $gate_pass,
            'available_gatepass' => $available_gatepass,
        ]);
    }
}
