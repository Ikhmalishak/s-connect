<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgreementController extends Controller
{
    /**
     * Display the agreement page.
     */
    public function show(): Response
    {
        return Inertia::render('Agreement');
    }

    /**
     * Handle agreement acceptance.
     */
    public function accept(Request $request)
    {
        // Since agreement is required every login, just redirect to dashboard
        return redirect()->route('welcome');
    }
}