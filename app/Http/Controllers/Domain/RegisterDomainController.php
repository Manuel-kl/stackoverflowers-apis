<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RegisterDomainController extends Controller
{
    public function index(Request $request)
    {
        $domainId = $request->input('domainid');
        $domain = $request->input('domain');
        $idnLanguage = $request->input('idnlanguage');

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
            'action' => 'DomainRegister',
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

        if ($idnLanguage) {
            $params['idnlanguage'] = $idnLanguage;
        }

        $registerRes = Http::asForm()
            ->timeout(300)
            ->post($whmcsUrl, $params);

        if (!$registerRes->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to communicate with WHMCS API',
                'data' => $registerRes->body(),
            ], 500);
        }

        $response = $registerRes->json();

        if (($response['result'] ?? '') !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Domain registration failed',
                'data' => $response,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Domain registered successfully',
            'data' => [
                'domainid' => $domainId,
                'domain' => $domain,
                'idnlanguage' => $idnLanguage,
            ],
        ]);
    }
}
