<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    //get admin manage user page
    public function getManageUserPage(Request $request)
    {
        return Inertia::render('Security/Visitor/AdminManageUser');
    }

    public function getAllUser()
    {
        $users = User::all();

        return response()->json([
            'data' => $users,
            'message' => "Users successfully fetched"
        ]);
    }

    public function getUserTableData(Request $request)
    {
        $limit = $request->input('limit', 50);
        $keyword = $request->input('keyword');

        $query = User::query();

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('site', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', "LIKE", "%{$keyword}%")
                    ->orWhere('role', "LIKE", "%{$keyword}%");

            });
        }

        // Apply limit only if no search keyword
        if (!$keyword) {
            $query->take($limit);
        }

        $users = $query->latest()->get();
        return response()->json([
            'data' => $users,
            'message' => "Users successfully fetched"
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,guard,receptionist',
            'site' => 'required|in:Site 1,Site 2,Site 3,Site 4',
        ]);

        $user->update($request->all());

        return response()->json(['message' => 'User updated successfully']);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

}
