<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Services\DomainService;
use Illuminate\Http\JsonResponse;

class RenewDomainController extends Controller
{
    protected DomainService $domainService;

    public function __construct(DomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function registerDomain(): JsonResponse
    {
        $domain = request('domain');

        if (!$domain) {
            return response()->json([
                'error' => 'Domain is required.',
            ], 422);
        }

        $params = [
            'domain' => $domain,
            'regperiod' => request('regperiod', '3'),
            'addons' => request('addons', [
                'dnsmanagement' => 0,
                'emailforwarding' => 1,
                'idprotection' => 1,
            ]),
        ];

        $response = $this->domainService->renewDomain($params);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error' => 'Domain renewal failed.',
            'details' => $response->body(),
        ], $response->status() ?: 500);
    }
}
