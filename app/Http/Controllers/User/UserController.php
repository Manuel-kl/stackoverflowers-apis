<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserDetailsRequest;
use App\Services\WhmcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    protected WhmcsService $whmcsService;

    public function __construct(WhmcsService $whmcsService)
    {
        $this->whmcsService = $whmcsService;
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'data' => $user,
            'message' => 'User retrieved successfully',
        ]);
    }

    public function userDetails(UserDetailsRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->whmcs_client_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found in WHMCS',
                ], 404);
            }

            $whmcsData = $request->validated();
            $whmcsData['action'] = 'UpdateClient';
            $whmcsData['clientid'] = $user->whmcs_client_id;
            $whmcsData['username'] = config('services.whmcs.identifier');
            $whmcsData['password'] = config('services.whmcs.secret');
            $whmcsData['responsetype'] = 'json';
            $whmcsData['currency'] = 2;

            $response = Http::timeout(300)->asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

            if ($response->successful()) {
                $result = $response->json();

                if ($result['result'] === 'success') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Client updated successfully',
                        'data' => [
                            'clientid' => $user->whmcs_client_id,
                        ],
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'WHMCS API error: '.($result['message'] ?? 'Unknown error'),
                        'data' => $result,
                    ], 400);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to WHMCS API',
                    'data' => [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error while updating client',
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    public function checkWhmcsUserDetails(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->whmcs_client_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found in WHMCS',
                ], 404);
            }

            $user['whmcs_details'] = $this->whmcsService->getClient($user->whmcs_client_id);

            return response()->json([
                'success' => true,
                'message' => 'WHMCS client details retrieved successfully',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve WHMCS client details',
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
