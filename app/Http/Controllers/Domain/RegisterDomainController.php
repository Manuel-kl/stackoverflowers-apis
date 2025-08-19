<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Services\DomainService;
use Illuminate\Http\Request;

class RegisterDomainController extends Controller
{
    protected DomainService $domainService;

    public function __construct(DomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function index(Request $request)
    {
        $domain = $request->input('domain');

        if (!$domain) {
            return response()->json([
                'error' => 'Domain is required.',
            ], 422);
        }

        $params = [
            'domain' => $domain,
            'regperiod' => $request->input('regperiod', '1'),
            'addons' => $request->input('addons', [
                'dnsmanagement' => 0,
                'emailforwarding' => 1,
                'idprotection' => 1,
            ]),
        ];

        $response = $this->domainService->registerDomain($params);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error' => 'Domain registration failed.',
            'details' => $response->body(),
        ], $response->status() ?: 500);
    }
}
