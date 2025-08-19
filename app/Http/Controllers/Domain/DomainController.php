<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\DomainLookupRequest;
use App\Services\DomainService;
use Illuminate\Http\JsonResponse;

class DomainController extends Controller
{
    protected DomainService $domainService;

    public function __construct(DomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function index(DomainLookupRequest $request): JsonResponse
    {
        $data = $request->validated();

        $params = [
            'searchTerm' => $data['searchTerm'],
            'punyCodeSearchTerm' => $data['punyCodeSearchTerm'],
            'tldsToInclude' => $data['tldsToInclude'],
            'isIdnDomain' => $data['isIdnDomain'],
            'premiumEnabled' => $data['premiumEnabled'],
        ];

        $response = $this->domainService->checkAvailability($params);

        if ($response->successful()) {
            $data = $response->json();

            return response()->json($data);
        }

        return response()->json([
            'error' => 'Could not check domain availability at this time.',
            'details' => $response->json(),
        ], $response->status() ?: 500);
    }
}
