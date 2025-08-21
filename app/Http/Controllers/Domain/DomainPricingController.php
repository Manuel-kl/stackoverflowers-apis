<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\DomainPricingRequest;
use App\Services\DomainService;
use Illuminate\Http\JsonResponse;

class DomainPricingController extends Controller
{
    protected DomainService $domainService;

    public function __construct(DomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    /**
     * Get domain pricing for a specific type (register, renew, transfer)
     *
     * @param  string  $type  The pricing type (register, renew, transfer)
     */
    public function index(DomainPricingRequest $request, string $type): JsonResponse
    {
        // Validate the type parameter (case-insensitive)
        $validTypes = ['register', 'renew', 'transfer'];
        $type = strtolower($type);

        if (!in_array($type, $validTypes)) {
            return response()->json([
                'error' => 'Invalid pricing type. Must be one of: '.implode(', ', $validTypes),
            ], 422);
        }

        $domain = $request->validated('domain');

        $response = $this->domainService->getDomainPricing($type, $domain);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error' => 'Could not fetch domain pricing.',
            'details' => $response->body(),
        ], $response->status() ?: 500);
    }
}
