<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserDomainController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (is_null($user->whmcs_client_id)) {
            return response()->json(['message' => 'User does not have a WHMCS client ID'], 404);
        }

        try {
            $baseUrl = rtrim(config('services.whmcs.url'), '/');
            $username = (string) config('services.whmcs.identifier');
            $password = (string) config('services.whmcs.secret');

            $body = array_filter([
                'action' => 'GetClientsDomains',
                'username' => $username,
                'password' => $password,
                'responsetype' => 'json',
                'clientid' => $user->whmcs_client_id,
                'limitstart' => $request->get('limitstart', 0),
                'limitnum' => $request->get('limitnum', 25),
            ], fn ($v) => !is_null($v));

            $response = Http::asForm()->timeout(300)->post($baseUrl.'/includes/api.php', $body);

            if ($response->failed()) {
                Log::error('WHMCS GetClientsDomains request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'client_id' => $user->whmcs_client_id,
                ]);
                throw new \RuntimeException('Failed to connect to WHMCS API');
            }

            $result = $response->json();

            if (!isset($result['result']) || $result['result'] !== 'success') {
                Log::error('WHMCS GetClientsDomains error', [
                    'message' => $result['message'] ?? 'Unknown error',
                    'data' => $result,
                    'client_id' => $user->whmcs_client_id,
                ]);
                throw new \RuntimeException('WHMCS API error: '.($result['message'] ?? 'Unknown error'));
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch domains',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
