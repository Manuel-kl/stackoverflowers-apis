<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(RegistrationRequest $request)
    {
        $data = $request->validated();

        $user = User::create($data);
        $token = $user->createToken('auth_token')->plainTextToken;
        $phoneNumberWithoutCountryCode = preg_replace('/^(\+254)/', '', $data['phone_number']);

        try {
            $whmcsData = [
                'username' => config('services.whmcs.identifier'),
                'password' => config('services.whmcs.secret'),
                'responsetype' => 'json',
                'action' => 'AddClient',
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'password2' => $data['password'],
                'phonenumber' => $phoneNumberWithoutCountryCode,
                'skipvalidation' => true,
            ];

            $response = Http::timeout(300)->asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

            if ($response->successful()) {
                $result = $response->json();

                if ($result['result'] === 'success') {
                    $user->update(['whmcs_client_id' => $result['clientid'] ?? null]);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }

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

        if ($shouldLogoutAllDevices) {
            $user->tokens()->delete();
        } else {
            $user->currentAccessToken()->delete();
        }

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
        $whmcsClientId = $user->whmcs_client_id;

        if ($whmcsClientId) {
            try {
                $whmcsData = [
                    'username' => config('services.whmcs.identifier'),
                    'password' => config('services.whmcs.secret'),
                    'responsetype' => 'json',
                    'action' => 'DeleteClient',
                    'clientid' => $whmcsClientId,
                    'deleteusers' => false,
                    'deletetransactions' => true,
                ];

                $response = Http::timeout(300)->asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

                if ($response->successful()) {
                    $result = $response->json();

                    if ($result['result'] !== 'success') {
                        Log::warning('Failed to delete WHMCS client', [
                            'client_id' => $whmcsClientId,
                            'deleteusers' => true,
                            'deletetransactions' => true,
                            'response' => $result,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error deleting WHMCS client', [
                    'client_id' => $whmcsClientId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
