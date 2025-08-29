<?php

namespace App\Http\Controllers\Whmcs;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CreateOrderController extends Controller
{
    public function addOrder(Request $request): JsonResponse
    {
        $request->validate([
            'clientid' => 'required|integer',
            'paymentmethod' => 'required|string',
            'pid' => 'sometimes|array',
            'qty' => 'sometimes|array',
            'domain' => 'sometimes|array',
            'billingcycle' => 'sometimes|array',
            'domaintype' => 'sometimes|array',
            'regperiod' => 'sometimes|array',
            'idnlanguage' => 'sometimes|array',
            'eppcode' => 'sometimes|array',
            'nameserver1' => 'sometimes|string',
            'nameserver2' => 'sometimes|string',
            'nameserver3' => 'sometimes|string',
            'nameserver4' => 'sometimes|string',
            'nameserver5' => 'sometimes|string',
            'customfields' => 'sometimes|array',
            'configoptions' => 'sometimes|array',
            'priceoverride' => 'sometimes|array',
            'promocode' => 'sometimes|string',
            'promooverride' => 'sometimes|boolean',
            'affid' => 'sometimes|integer',
            'noinvoice' => 'sometimes|boolean',
            'noinvoiceemail' => 'sometimes|boolean',
            'noemail' => 'sometimes|boolean',
            'addons' => 'sometimes|array',
            'addonsqty' => 'sometimes|array',
            'hostname' => 'sometimes|array',
            'ns1prefix' => 'sometimes|array',
            'ns2prefix' => 'sometimes|array',
            'rootpw' => 'sometimes|array',
            'contactid' => 'sometimes|integer',
            'dnsmanagement' => 'sometimes|array',
            'domainfields' => 'sometimes|array',
            'emailforwarding' => 'sometimes|array',
            'idprotection' => 'sometimes|array',
            'domainpriceoverride' => 'sometimes|array',
            'domainrenewoverride' => 'sometimes|array',
            'domainrenewals' => 'sometimes|array',
            'clientip' => 'sometimes|string',
            'addonid' => 'sometimes|integer',
            'addonidqty' => 'sometimes|integer',
            'serviceid' => 'sometimes|integer',
            'addonids' => 'sometimes|array',
            'addonidsqty' => 'sometimes|array',
            'serviceids' => 'sometimes|array',
            'servicerenewals' => 'sometimes|array',
            'addonrenewals' => 'sometimes|array',
        ]);

        try {
            $whmcsData = $request->all();
            $whmcsData['action'] = 'AddOrder';
            $whmcsData['username'] = config('services.whmcs.identifier');
            $whmcsData['password'] = config('services.whmcs.secret');
            $whmcsData['responsetype'] = 'json';

            $response = Http::asForm()->post(config('services.whmcs.url').'/includes/api.php', $whmcsData);

            if ($response->successful()) {
                $result = $response->json();

                if ($result['result'] === 'success') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Order created successfully',
                        'data' => [
                            'orderid' => $result['orderid'] ?? null,
                            'serviceids' => $result['serviceids'] ?? null,
                            'addonids' => $result['addonids'] ?? null,
                            'domainids' => $result['domainids'] ?? null,
                            'invoiceid' => $result['invoiceid'] ?? null,
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
            Log::error('WHMCS CreateOrder exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error while creating order',
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
