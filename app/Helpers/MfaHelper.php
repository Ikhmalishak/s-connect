<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MfaHelper
{
    public static function sendMfaCode($user)
    {
        // Generate a 6-digit code
        $code = random_int(100000, 999999);
 
        // Save it to the user
        $user->update([
            'mfa_code' => $code,
            'mfa_expires_at' => now()->addMinutes(5),
        ]);

        // Send via email
        Mail::raw("Your admin MFA verification code is: {$code}", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Admin MFA Code');
        });

        // Store user email temporarily in session
        session(['mfa_email' => $user->email]);
    }
}
