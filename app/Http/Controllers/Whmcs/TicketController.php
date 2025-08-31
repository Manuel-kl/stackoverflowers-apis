<?php

namespace App\Http\Controllers\Whmcs;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * Open a new ticket
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            $whmcsData = $request->only(['subject', 'message']);
            $whmcsData['clientid'] = $user->whmcs_client_id;
            $whmcsData['action'] = 'OpenTicket';
            $whmcsData['deptid'] = 1;
            $whmcsData['username'] = config('services.whmcs.identifier');
            $whmcsData['password'] = config('services.whmcs.secret');
            $whmcsData['responsetype'] = 'json';

            $response = Http::asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

            if ($response->successful()) {
                $result = $response->json();

                if ($result['result'] === 'success') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Ticket opened successfully',
                        'data' => $result,
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'WHMCS API error: '.($result['message'] ?? 'Unknown error'),
                        'data' => $result,
                    ], 400);
                }
            } else {
                Log::error('WHMCS API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request_data' => $whmcsData,
                ]);

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
            Log::error('WHMCS OpenTicket exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error while opening ticket',
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $whmcsData['clientid'] = $user->whmcs_client_id;
        $whmcsData['action'] = 'GetTickets';
        $whmcsData['username'] = config('services.whmcs.identifier');
        $whmcsData['password'] = config('services.whmcs.secret');
        $whmcsData['responsetype'] = 'json';

        $response = Http::asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

        if ($response->successful()) {
            $result = $response->json();

            return response()->json($result);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to connect to WHMCS API',
            'data' => [
                'status' => $response->status(),
                'body' => $response->body(),
            ],
        ], 500);
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $whmcsData['action'] = 'GetTicket';
            $whmcsData['replysort'] = 'ASC';
            $whmcsData['ticketid'] = $id;
            $whmcsData['username'] = config('services.whmcs.identifier');
            $whmcsData['password'] = config('services.whmcs.secret');
            $whmcsData['responsetype'] = 'json';

            $response = Http::asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

            if ($response->successful()) {
                $result = $response->json();

                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to WHMCS API',
                'data' => [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            ], 500);
        } catch (\Exception $e) {
            Log::error('WHMCS GetTicket exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ticket_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error while retrieving ticket',
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:Open,Closed',
        ]);

        $whmcsData['action'] = 'UpdateTicket';
        $whmcsData['ticketid'] = $id;
        $whmcsData['status'] = $request->status;
        $whmcsData['username'] = config('services.whmcs.identifier');
        $whmcsData['password'] = config('services.whmcs.secret');
        $whmcsData['responsetype'] = 'json';

        $response = Http::asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

        if ($response->successful()) {
            $result = $response->json();

            return response()->json($result);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to connect to WHMCS API',
            'data' => [
                'status' => $response->status(),
                'body' => $response->body(),
            ],
        ], 500);
    }

    public function destroy($id): JsonResponse
    {
        $whmcsData['action'] = 'DeleteTicket';
        $whmcsData['ticketid'] = $id;
        $whmcsData['username'] = config('services.whmcs.identifier');
        $whmcsData['password'] = config('services.whmcs.secret');
        $whmcsData['responsetype'] = 'json';

        $response = Http::asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

        if ($response->successful()) {
            $result = $response->json();

            return response()->json($result);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to connect to WHMCS API',
            'data' => [
                'status' => $response->status(),
                'body' => $response->body(),
            ],
        ], 500);
    }

    public function ticketReply(Request $request, $id): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'message' => 'required|string',
        ]);

        try {
            $whmcsData = $request->only(['message']);
            $whmcsData['action'] = 'AddTicketReply';
            $whmcsData['ticketid'] = $id;
            $whmcsData['clientid'] = $user->whmcs_client_id;
            $whmcsData['username'] = config('services.whmcs.identifier');
            $whmcsData['password'] = config('services.whmcs.secret');
            $whmcsData['responsetype'] = 'json';

            $response = Http::asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

            if ($response->successful()) {
                $result = $response->json();

                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to WHMCS API',
                'data' => [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            ], 500);
        } catch (\Exception $e) {
            Log::error('WHMCS AddTicketReply exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ticket_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error while adding ticket reply',
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
