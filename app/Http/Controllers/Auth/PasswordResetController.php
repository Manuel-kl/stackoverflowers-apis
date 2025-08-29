<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6',
        ]);

        $user = Auth::user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid current password',
                'success' => false,
            ], 400);
        }

        $user->update([
            'password' => $data['password'],
        ]);

        return response()->json([
            'message' => 'Password reset successful',
            'success' => true,
        ], 200);
    }
}
