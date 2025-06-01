<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        // Find user by email
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['message' => 'No account found with this email'], 404);
        }

        // Check password
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Password is incorrect'], 401);
        }

        // Create token
        $token = $user->createToken('user-token')->plainTextToken;

        return response()->json([
            'userId' => $user->id,
            'role' => $user->role,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful'], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:user,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Generate a 10-character alphanumeric token
            $token = Str::random(10);

            // Store the token in the `password_resets` table
            DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'created_at' => Carbon::now(),
                ]
            );

            // Send the reset email
            // Mail::to($request->email)->send(new ResetPasswordMail($token));

            return response()->json(['message' => 'Reset token sent to your email.']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }



    public function validateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:user,email',
            'token' => 'required|string|size:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        try {

            $reset = DB::table('password_resets')->where([
                ['email', $request->email],
                ['token', $request->token],
            ])->first();

            //the toekn should be generated in the last 60 minutes to be considered valid, otherwise it's considered expired
            if (!$reset || Carbon::now()->diffInMinutes($reset->created_at) > 60) {
                return response()->json(['message' => 'Invalid or expired token'], 400);
            }

            return response()->json(['message' => 'Token is valid']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:user,email',
            'token' => 'required|string|size:10',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {

            $reset = DB::table('password_resets')->where([
                ['email', $request->email],
                ['token', $request->token],
            ])->first();

            if (!$reset || Carbon::now()->diffInMinutes($reset->created_at) > 60) {
                return response()->json(['message' => 'Invalid or expired token'], 400);
            }

            // Update the user's password
            $user = User::where('email', $request->email)->firstOrFail();
            if (!$user)
                return response()->json(['message' => 'User not found'], 404);

            $user->update(['password' => Hash::make($request->password)]);

            // Delete the reset token
            DB::table('password_resets')->where(['email' => $request->email])->delete();

            return response()->json(['message' => 'Password reset successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}