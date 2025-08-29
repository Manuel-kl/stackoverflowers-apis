<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RenewDomainController extends Controller
{
    public function index(Request $request)
    {
        $domainId = $request->input('domainid');
        $domain = $request->input('domain');
        $regPeriod = $request->input('regperiod');

        if (!$domainId && !$domain) {
            return response()->json([
                'success' => false,
                'message' => 'Either domain ID or domain name is required',
            ], 422);
        }

        $identifier = config('services.whmcs.identifier');
        $secret = config('services.whmcs.secret');
        $whmcsUrl = rtrim(config('services.whmcs.url'), '/').'/includes/api.php';

        $params = [
            'action' => 'DomainRenew',
            'identifier' => $identifier,
            'secret' => $secret,
            'responsetype' => 'json',
        ];

        if ($domainId) {
            $params['domainid'] = $domainId;
        }

        if ($domain) {
            $params['domain'] = $domain;
        }

        if ($regPeriod !== null) {
            $params['regperiod'] = $regPeriod;
        }

        $renewRes = Http::asForm()
            ->timeout(300)
            ->post($whmcsUrl, $params);

        if (!$renewRes->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to communicate with WHMCS API',
                'data' => $renewRes->body(),
            ], 500);
        }

        $response = $renewRes->json();

        if (($response['result'] ?? '') !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew domain',
                'data' => $response,
            ], 400);
        }

        $renewalInfo = [
            'domainid' => $domainId,
            'domain' => $domain,
            'regperiod' => $regPeriod,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Domain renewed successfully',
            'data' => $renewalInfo,
        ]);
    }
}
