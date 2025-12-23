<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckPasswordExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            // First-time login → force password update
            if ($user->is_first_time_login) {
                return redirect()->route('password.expired')->with('reason', 'first_time');
            }

            // if user never changed password, fallback to created_at
            $lastChanged = $user->password_changed_at ?? $user->created_at;

            if (Carbon::parse($lastChanged)->addDays(180)->isPast()) {
                // force redirect to password update page
                return redirect()->route('password.expired')->with('reason', 'expired');
            }
        }

        return $next($request);
    }
}
