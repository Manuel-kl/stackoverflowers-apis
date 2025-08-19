<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Services\DomainService;
use Illuminate\Http\JsonResponse;

class DomainPricingController extends Controller
{
    protected DomainService $domainService;

    public function __construct(DomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function tldPricing(): JsonResponse
    {
        $response = $this->domainService->getTldPricing();

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error' => 'Could not fetch TLD pricing.',
            'details' => $response->body(),
        ], $response->status() ?: 500);
    }
}
