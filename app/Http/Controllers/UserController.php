<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller; // Import the base Controller class

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        if ($users->count() > 0) {
            return response()->json([
                'users' => $users
            ], 200);
        }
        return response()->json([
            'message' => 'No users found'
        ], 400);
    }

    public function show($id)
    {
        $userId = $id; //?? Auth::guard('sanctum')->user()->id;
        $user = User::findOrFail($userId);
        if ($user) {
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                'priviliges' => $user->priviliges
            ], 200);
        }
        return response()->json([
            'message' => 'No user found'
        ], 404);
    }

    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255|unique:user,email',
                'password' => 'required|string|min:8',
                'role' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->messages()
                ], 422);
            }

            $validated = $validator->validated();

            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role']
            ]);

            if ($user) {
                return response()->json(['message' => 'User registered successfully'], 201);
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $user = User::findOrFail($id);

            $request->validate([
                'email' => 'sometimes|string|email|max:255|unique:user,email,' . $user->id,
                'role' => 'sometimes|string'
            ]);

            $user->update([
                'email' => $request->input('email') ?? $user->email,
                'role' => $request->input('role') ?? $user->role
            ]);

            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($request->password)]);
            }

            return response()->json(['message' => 'user updatef succefully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $id = Auth::guard('sanctum')->user()->id;
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'oldPassword' => 'required|string',
                'newPassword' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->messages()
                ], 422);
            }

            $validated = $validator->validated();

            // Check if the old password matches
            if (!Hash::check($validated['oldPassword'], $user->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Old password is incorrect.'
                ], 401);
            }

            // Update the password
            $user->password = Hash::make($validated['newPassword']);
            $user->save();

            return response()->json([
                'status' => 200,
                'message' => 'Password updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            //delete the user notification token
            DB::table('personal_access_tokens')->where('tokenable_id', $id)->delete();
            $user->delete();

            return response()->json(['message' => 'user deleted succefully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function saveUserNotificationToken(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'userToken' => 'required|string|unique:users_tokens,token',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {

            $validated = $validator->validated();
            $userToken = $validated['userToken'];

            // Retrieve authenticated user
            $user = Auth::guard('sanctum')->user();

            // Save the new token
            DB::table('users_tokens')->insertGetId([
                'user_id' => $user->id,
                'token' => $userToken,
                'role' => $user->role,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['message' => 'Token saved successfully', 'role' => $user->role], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
