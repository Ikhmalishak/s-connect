<?php

namespace App\Http\Controllers;

use App\Helpers\MfaHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Inertia\Inertia;

class MFAController extends Controller
{
    public function showVerifyForm()
    {
        return Inertia::render('Auth/MfaVerify');
    }
public function verifyCode(Request $request)
{
    $request->validate(['code' => 'required|numeric']);

    $user = User::where('email', session('mfa_email'))->first();

    if (!$user || $user->mfa_code !== $request->code || now()->gt($user->mfa_expires_at)) {
        // If it's an Axios (JSON) request, return JSON error
        if ($request->expectsJson()) {
            return response()->json([
                'errors' => [
                    'code' => ['Invalid or expired code.']
                ]
            ], 422);
        }

        return back()->withErrors(['code' => 'Invalid or expired code.']);
    }

    $user->update(['mfa_code' => null, 'mfa_expires_at' => null]);
    session()->forget('mfa_email');

    Auth::login($user);

    if ($request->expectsJson()) {
        return response()->json(['message' => 'Authenticated successfully.']);
    }
dd("here");
    return redirect()->route('welcome');
}

public function resendCode(Request $request)
{
    $email = session('mfa_email');
    $user = User::where('email', $email)->first();

    if (!$user) {
        return response()->json(['message' => 'Session expired. Please log in again.'], 401);
    }

    // Generate and send a new code
    MfaHelper::sendMfaCode($user);

    return response()->json(['message' => 'A new MFA code has been sent to your email.']);
}
}
