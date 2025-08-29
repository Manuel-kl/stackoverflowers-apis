<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DomainNameserversController extends Controller
{
    public function index(Request $request)
    {
        $domainId = $request->input('domainid');

        if (!$domainId) {
            return response()->json([
                'success' => false,
                'message' => 'Domain ID is required',
            ], 422);
        }

        $identifier = config('services.whmcs.identifier');
        $secret = config('services.whmcs.secret');
        $whmcsUrl = rtrim(config('services.whmcs.url'), '/').'/includes/api.php';

        $params = [
            'action' => 'DomainGetNameservers',
            'username' => $identifier,
            'password' => $secret,
            'domainid' => $domainId,
            'responsetype' => 'json',
        ];

        $nameserversRes = Http::asForm()
            ->timeout(300)
            ->post($whmcsUrl, $params);

        if (!$nameserversRes->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to communicate with WHMCS API',
                'data' => $nameserversRes->body(),
            ], 500);
        }

        $response = $nameserversRes->json();

        if (($response['result'] ?? '') !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get nameservers',
                'data' => $response,
            ], 400);
        }

        $nameservers = [];
        for ($i = 1; $i <= 5; $i++) {
            $nsKey = "ns{$i}";
            if (isset($response[$nsKey]) && !empty($response[$nsKey])) {
                $nameservers[] = $response[$nsKey];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Nameservers retrieved successfully',
            'data' => [
                'domainid' => $domainId,
                'nameservers' => $nameservers,
                'count' => count($nameservers),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $domainId = $request->input('domainid');
        $ns1 = $request->input('ns1');
        $ns2 = $request->input('ns2');
        $ns3 = $request->input('ns3');
        $ns4 = $request->input('ns4');
        $ns5 = $request->input('ns5');

        if (!$domainId) {
            return response()->json([
                'success' => false,
                'message' => 'Domain ID is required',
            ], 422);
        }

        if (!$ns1 || !$ns2) {
            return response()->json([
                'success' => false,
                'message' => 'ns1 and ns2 are required',
            ], 422);
        }

        $identifier = config('services.whmcs.identifier');
        $secret = config('services.whmcs.secret');
        $whmcsUrl = rtrim(config('services.whmcs.url'), '/').'/includes/api.php';

        $params = [
            'action' => 'DomainUpdateNameservers',
            'username' => $identifier,
            'password' => $secret,
            'ns1' => $ns1,
            'ns2' => $ns2,
            'responsetype' => 'json',
        ];

        if ($domainId) {
            $params['domainid'] = $domainId;
        }

        if ($ns3) {
            $params['ns3'] = $ns3;
        }
        if ($ns4) {
            $params['ns4'] = $ns4;
        }
        if ($ns5) {
            $params['ns5'] = $ns5;
        }

        $updateRes = Http::asForm()
            ->timeout(300)
            ->post($whmcsUrl, $params);

        if (!$updateRes->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to communicate with WHMCS API',
                'data' => $updateRes->body(),
            ], 500);
        }

        $response = $updateRes->json();

        if (($response['result'] ?? '') !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update nameservers',
                'data' => $response,
            ], 400);
        }

        $nameservers = [$ns1, $ns2];
        if ($ns3) {
            $nameservers[] = $ns3;
        }
        if ($ns4) {
            $nameservers[] = $ns4;
        }
        if ($ns5) {
            $nameservers[] = $ns5;
        }

        return response()->json([
            'success' => true,
            'message' => 'Nameservers updated successfully',
            'data' => [
                'domainid' => $domainId,
                'nameservers' => $nameservers,
                'count' => count($nameservers),
            ],
        ]);
    }
}
