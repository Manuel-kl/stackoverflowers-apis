<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegistrationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create($data);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => $user,
            'message' => 'Registration successful',
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => $user,
            'message' => 'Login successful',
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $shouldLogoutAllDevices = $request->input('all_devices', false);
        $user = Auth::user();

        $shouldLogoutAllDevices ? $user->tokens()->delete() : $user->currentAccessToken()->delete();

        return response()->json([
            'data' => null,
            'message' => 'Logged out successfully',
        ]);
    }

    public function deleteUser(Request $request): JsonResponse
    {
        $password = $request->input('password');

        if (!Hash::check($password, Auth::user()->password)) {
            return response()->json(['message' => 'Invalid password'], 401);
        }

        $user = Auth::user();

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
