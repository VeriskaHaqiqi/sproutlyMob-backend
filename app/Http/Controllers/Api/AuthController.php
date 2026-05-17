<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ==================== REGISTER USER ====================
    public function registerUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'gender'   => 'nullable|in:Male,Female,Other',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'gender'   => $request->gender,
            'password' => Hash::make($request->password),
            'role'     => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data'    => [
                'user'  => $user,
                'token' => $token,
            ],
        ], 201);
    }

    // ==================== REGISTER EXPERT ====================
    public function registerExpert(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:100',
            'email'              => 'required|email|unique:users,email',
            'phone'              => 'nullable|string|max:20',
            'gender'             => 'nullable|in:Male,Female,Other',
            'password'           => 'required|string|min:8|confirmed',
            'university'         => 'nullable|string|max:150',
            'years_of_experience'=> 'nullable|integer|min:0',
            'description'        => 'nullable|string',
            'bank_name'          => 'nullable|string|max:100',
            'account_holder'     => 'nullable|string|max:100',
            'account_number'     => 'nullable|string|max:50',
        ]);

        // Buat user dengan role expert
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'gender'   => $request->gender,
            'password' => Hash::make($request->password),
            'role'     => 'expert',
        ]);

        // Buat expert profile
        ExpertProfile::create([
            'user_id'             => $user->id,
            'university'          => $request->university,
            'years_of_experience' => $request->years_of_experience ?? 0,
            'description'         => $request->description,
            'bank_name'           => $request->bank_name,
            'account_holder'      => $request->account_holder,
            'account_number'      => $request->account_number,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Expert registration successful',
            'data'    => [
                'user'  => $user->load('expertProfile'),
                'token' => $token,
            ],
        ], 201);
    }

    // ==================== LOGIN ====================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Hapus token lama, buat token baru
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'  => $user->load('expertProfile'),
                'token' => $token,
            ],
        ]);
    }

    // ==================== LOGOUT ====================
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    // ==================== GET PROFILE ====================
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => $request->user()->load('expertProfile', 'specializations'),
        ]);
    }

    // ==================== FORGOT PASSWORD ====================
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Untuk sekarang return success saja
        // Nanti bisa ditambah kirim email reset
        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email',
        ]);
    }

    // ==================== RESET PASSWORD ====================
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successful',
        ]);
    }
}