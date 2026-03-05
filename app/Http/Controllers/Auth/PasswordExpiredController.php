<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordHistory;
use App\Models\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordExpiredController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        //call password policy
        $policy = PasswordPolicy::first();

        $passwordRule = Password::min($policy->min_length);

        if ($policy->require_letters) {
            $passwordRule->letters();
        }
        if ($policy->require_numbers) {
            $passwordRule->numbers();
        }
        if ($policy->require_mixed_case) {
            $passwordRule->mixedCase();
        }
        if ($policy->require_symbols) {
            $passwordRule->symbols();
        }

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                $passwordRule,
                function ($attribute, $value, $fail) use ($user) {
                    // Check against last 10 passwords
                    $recentPasswords = PasswordHistory::where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->pluck('password')
                        ->toArray();

                    foreach ($recentPasswords as $oldPassword) {
                        if (Hash::check($value, $oldPassword)) {
                            $fail('You cannot reuse a recent password.');
                            return;
                        }
                    }
                },
            ],
        ]);

        // Save current password to history before updating
        PasswordHistory::create([
            'user_id' => $user->id,
            'password' => $user->password,
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'is_first_time_login' => 0,
        ]);

        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.visitordashboard')->with('status', 'Password updated successfully!');
            case 'guard':
                return redirect()->route('security.visitordashboard')->with('status', 'Password updated successfully!');
            case 'receptionist':
                return redirect()->route('receptionist.visitordashboard')->with('status', 'Password updated successfully!');
            default:
                return redirect()->route('login');
        }
    }
}
