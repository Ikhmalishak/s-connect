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
            'site' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'password_changed_at' => now(),
            'is_first_time_login' => 1,
            'site' => $request->site,
        ]);

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
