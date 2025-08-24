<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\DomainLookupRequest;
use App\Models\KeDomainPricing;
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

        $searchTerm = $data['searchTerm'];
        $tld = '';
        $name = $searchTerm;

        if (preg_match('/^([^.]+)\.(.+)$/', $searchTerm, $matches)) {
            $name = $matches[1];
            $tld = $matches[2];
        }

        $params = [
            'searchTerm' => $name,
            'tldsToInclude' => [$tld],
        ];

        $response = $this->domainService->checkAvailability($params);

        if ($response->successful()) {
            $responseData = $response->json();

            $uniqueResults = collect($responseData)->unique('domainName')->values();

            $responseData = $uniqueResults->map(function ($domain) {
                if ($domain['isAvailable'] === true) {
                    $tld = '.'.$domain['tld'];
                    $pricing = KeDomainPricing::where('tld', $tld)->first();

                    if ($pricing) {
                        $domain['pricing'] = $this->formatPricing($pricing);
                        $domain['ke_pricing'] = [
                            'registration_price' => $pricing->registration_price,
                            'renewal_price' => $pricing->renewal_price,
                            'transfer_price' => $pricing->transfer_price,
                            'grace_fee' => $pricing->grace_fee,
                            'grace_days' => $pricing->grace_days,
                            'redemption_days' => $pricing->redemption_days,
                            'redemption_fee' => $pricing->redemption_fee,
                            'available_years' => $pricing->years,
                            'currency' => 'KES',
                        ];
                    }
                }

                return $domain;
            })->toArray();

            $originalRequest = request()->all();
            request()->replace(['searchTerm' => $name]);
            $suggestions = $this->suggestions($request)->getData();
            request()->replace($originalRequest);

            if (count($responseData) === 1) {
                return response()->json([
                    'domain' => $responseData[0],
                    'suggestions' => $suggestions,
                ]);
            }

            return response()->json([
                'domains' => $responseData,
                'suggestions' => $suggestions,
            ]);
        }

        return response()->json([
            'error' => 'Could not check domain availability at this time.',
            'details' => $response->json(),
        ], $response->status() ?: 500);
    }

    private function formatPricing(KeDomainPricing $pricing): array
    {
        $formattedPricing = [];

        foreach ($pricing->years as $year) {
            $formattedPricing[$year] = [
                'register' => [
                    [
                        'price' => $pricing->registration_price * $year,
                        'currency' => 'KES',
                        'period' => $year,
                        'type' => 'registration',
                    ],
                ],
                'renew' => [
                    [
                        'price' => $pricing->renewal_price * $year,
                        'currency' => 'KES',
                        'period' => $year,
                        'type' => 'renewal',
                    ],
                ],
                'transfer' => $pricing->transfer_price > 0 ? [
                    [
                        'price' => $pricing->transfer_price,
                        'currency' => 'KES',
                        'period' => 1,
                        'type' => 'transfer',
                    ],
                ] : [],
            ];
        }

        return $formattedPricing;
    }

    public function suggestions(DomainLookupRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tlds = KeDomainPricing::pluck('tld')->toArray();

        $params = [
            'searchTerm' => $data['searchTerm'],
            'tldsToInclude' => $tlds,
            'suggestionSettings' => [
                'maxResults' => 10,
            ],
        ];

        $response = $this->domainService->getDomainSuggestions($params);

        if ($response->successful()) {
            $suggestions = $response->json();

            $keDomains = collect($suggestions)->filter(function ($suggestion) {
                return str_ends_with($suggestion['tld'], '.ke');
            })->values();

            return response()->json($keDomains);
        }

        return response()->json([
            'error' => 'Could not fetch domain suggestions.',
            'details' => $response->body(),
        ], $response->status() ?: 500);
    }
}
