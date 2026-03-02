<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoginVerificationCode;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (in_array($user->status ?? 'active', ['suspended', 'banned'], true)) {
            return response()->json(['message' => 'Your account is restricted.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }

    // Step 1: Request login code
    public function requestLoginCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (in_array($user->status ?? 'active', ['suspended', 'banned'], true)) {
            return response()->json(['message' => 'Your account is restricted.'], 403);
        }

        $code = rand(100000, 999999);

        $user->loginVerifications()->create([
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new LoginVerificationCode($user, $code));

        return response()->json(['message' => 'Verification code sent to email.']);
    }

    // Step 2: Verify code and issue token
    public function verifyLoginCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        $verification = $user->loginVerifications()
            ->where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid or expired code'], 401);
        }

        $verification->update(['used' => true]);

        // 👉 Issue token instead of session
        $token = $user->createToken('admin-login')->plainTextToken;
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    // Register user and issue token
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    // Logout (revoke token)
    public function logout(Request $request)
    {
        $request->user()?->tokens()?->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
