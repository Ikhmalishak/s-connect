<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends Controller
{
    //get admin manage user page
    public function getManageUserPage()
    {
        return Inertia::render('ManageUser/UserDashboard');
    }

    public function getUserStatsCard()
    {
        $total_user = User::count();

        $total_admin = User::role('admin')->count();

        $total_recent_users = User::where('created_at', '>=', Carbon::now()->subWeeks(2))->count();

        return response()->json([
            'total_user' => $total_user,
            'total_admin' => $total_admin,
            'total_recent_users' => $total_recent_users,
            'message' => "Users successfully fetched"
        ]);
    }

    public function getUserTableData(Request $request)
    {
        $limit = $request->input('limit', 50);
        $keyword = $request->input('keyword');

        $query = User::with(['roles', 'site', 'department']); // eager load relationships

        // Exclude superadmin users
        $query->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'superadmin');
        });

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('roles', function ($qr) use ($keyword) {
                        $qr->where('name', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhereHas('site', function ($qs) use ($keyword) {
                        $qs->where('site_code', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhereHas('department', function ($qd) use ($keyword) {
                        $qd->where('name', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        // Paginate results instead of take()
        $users = $query->latest()->get();

        return response()->json([
            'data' => $users,
            'message' => 'Users successfully fetched'
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,guard,receptionist,staff',
            'site_id' => 'required',
            'department_id' => 'nullable|int|exists:departments,id',
        ]);

        // Update user info (exclude role since we handle it separately)
        $user->update($request->only(['name', 'email', 'site_id', 'department_id']));

        // Replace old roles with the new one
        $user->syncRoles([$request->role]);

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Updated user {$user->email} role to {$request->role}");

        return response()->json(['message' => 'User updated successfully']);
    }

    public function resetPassword(User $user)
    {
        // Save current password to history before resetting
        PasswordHistory::create([
            'user_id' => $user->id,
            'password' => $user->password,
        ]);

        $user->update([
            'password' => Hash::make('12345678'),
            'is_first_time_login' => 1,
            'password_changed_at' => now()
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Reset User Password: {$user->email}");

        return response()->json(['message' => 'User password successfully reset']);
    }

    public function unlockAccount(User $user)
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Unlocked User Account: {$user->email}");

        return response()->json(['message' => 'User account successfully unlocked']);
    }

    public function destroy(User $user)
    {
        $user->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Delete User: {$user->email}");

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function getUserPermissions(User $user)
    {
        return response()->json([
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'message' => 'User permissions fetched successfully'
        ]);
    }

    public function manageUserPermissions(Request $request, User $user)
    {
        $request->validate([
            'permission' => 'required|string',
            'action' => 'required|in:add,remove'
        ]);

        $permission = $request->permission;
        $action = $request->action;

        if ($action === 'add') {
            $user->givePermissionTo($permission);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log("Added permission '{$permission}' to user {$user->email}");
        } else {
            $user->revokePermissionTo($permission);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log("Removed permission '{$permission}' from user {$user->email}");
        }

        return response()->json([
            'message' => "Permission {$action}ed successfully",
            'permissions' => $user->getAllPermissions()->pluck('name')
        ]);
    }

    public function getAllPermissions()
    {
        $permissions = \Spatie\Permission\Models\Permission::orderBy('name')->get()->pluck('name');

        return response()->json([
            'permissions' => $permissions,
            'message' => 'All permissions fetched successfully'
        ]);
    }

}
