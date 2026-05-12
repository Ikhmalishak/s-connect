<?php

namespace App\Http\Controllers\ManageSafety;

use App\Http\Controllers\Controller;
use App\Models\AuditPic;
use App\Models\User;
use App\Models\Site;
use App\Models\Department;
use Illuminate\Http\Request;

class AuditPicController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditPic::with(['user', 'site', 'department']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $pics = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $pics,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'site_id' => 'required|integer|exists:sites,id',
            'department_id' => 'required|integer|exists:departments,id',
        ]);

        // Check for duplicates
        $exists = AuditPic::where([
            'user_id' => $validated['user_id'],
            'site_id' => $validated['site_id'],
            'department_id' => $validated['department_id'],
        ])->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This PIC assignment already exists.',
            ], 409);
        }

        $pic = AuditPic::create($validated);
        $pic->load(['user', 'site', 'department']);

        return response()->json([
            'message' => 'PIC assigned successfully!',
            'data' => $pic,
        ], 201);
    }

    public function destroy(AuditPic $auditPic)
    {
        $auditPic->delete();

        return response()->json([
            'message' => 'PIC assignment removed successfully!',
        ]);
    }

    // Helper: fetch users, sites, departments for dropdowns
    public function getFormData()
    {
        return response()->json([
            'users' => User::select('id', 'name', 'site_id', 'department_id')
                ->with(['site:id,name', 'department:id,name'])
                ->orderBy('name')
                ->get(),
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
        ]);
    }
}