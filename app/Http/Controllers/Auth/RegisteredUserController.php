<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordPolicy;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rules\Password;
use function activity;
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function login()
    {
        return Inertia::render('Auth/Login');
    }
    
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $policy = PasswordPolicy::first();

        $passwordRule = Password::min($policy->min_length);

        if ($policy->require_letters)
            $passwordRule->letters();
        if ($policy->require_numbers)
            $passwordRule->numbers();
        if ($policy->require_mixed_case)
            $passwordRule->mixedCase();
        if ($policy->require_symbols)
            $passwordRule->symbols();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', $passwordRule],
            'role' => 'required|string',
            'site_id' => 'required|int',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'is_first_time_login' => 1,
            'site_id' => $request->site_id,
        ]);

        $user->syncRoles($request->role);

        event(new Registered($user));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Created New User: {$user->email}");

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully!',
            'user' => $user,
        ]);
    }
}
