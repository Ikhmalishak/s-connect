<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordExpiredController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(5)
                    ->letters()   // must contain letters
                    ->numbers()   // must contain numbers
                    ->mixedCase() // require both upper & lower case (optional)
                    ->symbols()
            ],
        ]);
        $user = $request->user();

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
