<?php

namespace App\Http\Controllers;

use App\Models\PasswordPolicy;
use Illuminate\Http\Request;

class PasswordPolicyController extends Controller
{

    public function index()
    {
        $policy = PasswordPolicy::first();

        return response()->json([
            'data' => $policy,
            'messages' => "Successfully fetched policy"
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'min_length' => 'required|integer|min:6|max:20',
            'require_letters' => 'nullable|boolean',
            'require_numbers' => 'nullable|boolean',
            'require_mixed_case' => 'nullable|boolean',
            'require_symbols' => 'nullable|boolean',
        ]);

        $policy = PasswordPolicy::first();

        // Build dynamic message
        $parts = [];
        $parts[] = "at least {$request->min_length} characters";

        if ($request->boolean('require_letters')) {
            $parts[] = "letters";
        }
        if ($request->boolean('require_numbers')) {
            $parts[] = "numbers";
        }
        if ($request->boolean('require_mixed_case')) {
            $parts[] = "both uppercase and lowercase letters";
        }
        if ($request->boolean('require_symbols')) {
            $parts[] = "symbols";
        }

        $message = "Password must contain " . implode(", ", $parts) . ".";

        $policy->update([
            'min_length' => $request->min_length,
            'require_letters' => $request->boolean('require_letters'),
            'require_numbers' => $request->boolean('require_numbers'),
            'require_mixed_case' => $request->boolean('require_mixed_case'),
            'require_symbols' => $request->boolean('require_symbols'),
            'message' => $message,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password policy updated successfully!',
            'policy' => $policy,
        ]);
    }
}
