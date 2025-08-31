<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\DomainLookupRequest;
use App\Models\KeDomainPricing;
use App\Services\DomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

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

            $suggestions = $this->getManualSuggestions($name);

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

    private function getManualSuggestions(string $domainName): array
    {
        $tlds = ['.ke', '.co.ke', '.me.ke', '.mobi.ke', '.info.ke'];
        $availableDomains = [];

        foreach ($tlds as $tld) {
            $fullDomain = $domainName.$tld;

            $originalSearchTerm = request('searchTerm');
            if ($fullDomain === $originalSearchTerm) {
                continue;
            }

            $params = [
                'searchTerm' => $domainName,
                'tldsToInclude' => [ltrim($tld, '.')],
            ];

            $response = $this->domainService->checkAvailability($params);

            if ($response->successful()) {
                $responseData = $response->json();

                foreach ($responseData as $domain) {
                    if ($domain['isAvailable'] === true && $domain['domainName'] === $fullDomain) {
                        $tldForPricing = $tld;
                        $pricing = KeDomainPricing::where('tld', $tldForPricing)->first();

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

                        $availableDomains[] = $domain;
                        break;
                    }
                }
            }
        }

        return $availableDomains;
    }

    public function check(DomainLookupRequest $request)
    {
        $data = $request->validated();

        $identifier = config('services.whmcs.identifier');
        $secret = config('services.whmcs.secret');
        $whmcsUrl = rtrim(config('services.whmcs.url'), '/').'/includes/api.php';
        $currencyId = config('services.whmcs.currency_id');

        $allowedTlds = [
            'ke', 'co.ke', 'me.ke', 'mobi.ke', 'info.ke', 'ne.ke', 'or.ke', 'go.ke', 'ac.ke', 'sc.ke',
        ];
        $institutional = ['ac.ke', 'sc.ke', 'go.ke', 'or.ke', 'ne.ke'];

        $search = strtolower(trim($data['searchTerm']));
        $sld = $search;

        if (preg_match('/^([^.]+)\.([a-z0-9.-]+)$/i', $search, $m)) {
            $sld = $m[1];
            $tld = $m[2];
        }
        if (!in_array($tld, $allowedTlds, true)) {
            return response()->json([
                'error' => 'Unsupported TLD. Allowed: '.implode(', ', $allowedTlds),
            ], 422);
        }

        $domainFqdn = $sld.'.'.$tld;

        $whoisRes = Http::asForm()
            ->timeout(300)
            ->post($whmcsUrl, [
                'action' => 'DomainWhois',
                'identifier' => $identifier,
                'secret' => $secret,
                'domain' => $domainFqdn,
                'responsetype' => 'json',
            ]);

        $isAvailable = null;
        $status = null;
        $whoisBody = $whoisRes->ok() ? $whoisRes->json() : null;
        if (is_array($whoisBody) && ($whoisBody['result'] ?? '') === 'success') {
            $status = $whoisBody['status'] ?? '';
            $isAvailable = ($status === 'available');
        }

        $pricingParams = [
            'action' => 'GetTLDPricing',
            'identifier' => $identifier,
            'secret' => $secret,
            'tld' => $tld,
            'responsetype' => 'json',
            'currencyid' => $currencyId,
        ];

        $pricingRes = Http::asForm()->timeout(300)->post($whmcsUrl, $pricingParams);
        $pricingBody = $pricingRes->ok() ? $pricingRes->json() : null;
        $currencyCode = $pricingBody['currency']['code'] ?? 'KES';

        $tldPricing = [];
        if (is_array($pricingBody) && isset($pricingBody['pricing']) && is_array($pricingBody['pricing'])) {
            $tldPricing = $pricingBody['pricing'][$tld] ?? $pricingBody['pricing'][str_replace('.', '', $tld)] ?? [];
        }

        $pricingFormatted = [];
        if ($isAvailable && !empty($tldPricing)) {
            $years = array_keys($tldPricing['register'] ?? []);
            foreach ($years as $year) {
                $yearInt = (int) $year;
                $registerPrice = $tldPricing['register'][$year] ?? null;
                $renewPrice = $tldPricing['renew'][$year] ?? null;
                $transferPrice = $tldPricing['transfer'][$year] ?? null;

                $pricingFormatted[$yearInt] = [
                    'register' => $registerPrice !== null ? [[
                        'price' => is_numeric($registerPrice) ? ($registerPrice + 0) : $registerPrice,
                        'currency' => $currencyCode,
                        'period' => $yearInt,
                        'type' => 'registration',
                    ]] : [],
                    'renew' => $renewPrice !== null ? [[
                        'price' => is_numeric($renewPrice) ? ($renewPrice + 0) : $renewPrice,
                        'currency' => $currencyCode,
                        'period' => $yearInt,
                        'type' => 'renewal',
                    ]] : [],
                    'transfer' => $transferPrice !== null ? [[
                        'price' => is_numeric($transferPrice) ? ($transferPrice + 0) : $transferPrice,
                        'currency' => $currencyCode,
                        'period' => $yearInt,
                        'type' => 'transfer',
                    ]] : [],
                ];
            }
        }

        $shortestPeriod = !empty($pricingFormatted) ? min(array_keys($pricingFormatted)) : 1;

        $selectedDomain = [
            'domainName' => $domainFqdn,
            'idnDomainName' => $domainFqdn,
            'tld' => $tld,
            'tldNoDots' => str_replace('.', '', $tld),
            'sld' => $sld,
            'idnSld' => $sld,
            'status' => $status,
            'legacyStatus' => $status,
            'score' => 1,
            'isRegistered' => $isAvailable === null ? false : !$isAvailable,
            'isAvailable' => $isAvailable === null ? false : $isAvailable,
            'isValidDomain' => true,
            'domainErrorMessage' => '',
            'pricing' => $pricingFormatted,
            'shortestPeriod' => [
                'period' => $shortestPeriod,
                'register' => [],
                'transfer' => [],
                'renew' => [],
            ],
            'group' => $tldPricing['group'] ?? '',
            'minLength' => 0,
            'maxLength' => 0,
            'isPremium' => false,
            'premiumCostPricing' => [],
        ];

        $suggestions = [];
        $suggestionTlds = array_diff($allowedTlds, $institutional, [$tld]);
        $maxSuggestions = 8;

        if (is_array($pricingBody) && isset($pricingBody['pricing']) && is_array($pricingBody['pricing'])) {
            $allTldPricing = $pricingBody['pricing'];

            foreach ($suggestionTlds as $suggTld) {
                if (count($suggestions) >= $maxSuggestions) {
                    break;
                }

                $domainSuggestion = $sld.'.'.$suggTld;

                $sWhois = Http::asForm()
                    ->timeout(300)
                    ->post($whmcsUrl, [
                        'action' => 'DomainWhois',
                        'identifier' => $identifier,
                        'secret' => $secret,
                        'domain' => $domainSuggestion,
                        'responsetype' => 'json',
                    ]);

                $avail = false;
                if ($sWhois->ok()) {
                    $wb = $sWhois->json();
                    $avail = (is_array($wb) && ($wb['result'] ?? '') === 'success' && ($wb['status'] ?? '') === 'available');
                }
                if (!$avail) {
                    continue;
                }

                $tldData = $allTldPricing[$suggTld] ?? ($allTldPricing[str_replace('.', '', $suggTld)] ?? []);
                $suggPricing = [];
                if (!empty($tldData)) {
                    $yrs = array_keys($tldData['register'] ?? []);
                    foreach ($yrs as $yr) {
                        $yrInt = (int) $yr;
                        $reg = $tldData['register'][$yr] ?? null;
                        $ren = $tldData['renew'][$yr] ?? null;
                        $tra = $tldData['transfer'][$yr] ?? null;
                        $suggPricing[$yrInt] = [
                            'register' => $reg !== null ? [[
                                'price' => is_numeric($reg) ? ($reg + 0) : $reg,
                                'currency' => $currencyCode,
                                'period' => $yrInt,
                                'type' => 'registration',
                            ]] : [],
                            'renew' => $ren !== null ? [[
                                'price' => is_numeric($ren) ? ($ren + 0) : $ren,
                                'currency' => $currencyCode,
                                'period' => $yrInt,
                                'type' => 'renewal',
                            ]] : [],
                            'transfer' => $tra !== null ? [[
                                'price' => is_numeric($tra) ? ($tra + 0) : $tra,
                                'currency' => $currencyCode,
                                'period' => $yrInt,
                                'type' => 'transfer',
                            ]] : [],
                        ];
                    }
                }

                $suggestions[] = [
                    'domainName' => $domainSuggestion,
                    'idnDomainName' => $domainSuggestion,
                    'tld' => $suggTld,
                    'tldNoDots' => str_replace('.', '', $suggTld),
                    'sld' => $sld,
                    'idnSld' => $sld,
                    'status' => 'available',
                    'legacyStatus' => 'available',
                    'score' => 1,
                    'isRegistered' => false,
                    'isAvailable' => true,
                    'isValidDomain' => true,
                    'domainErrorMessage' => '',
                    'pricing' => $suggPricing,
                    'shortestPeriod' => [
                        'period' => !empty($suggPricing) ? min(array_keys($suggPricing)) : 1,
                        'register' => [],
                        'transfer' => [],
                        'renew' => [],
                    ],
                    'group' => $tldData['group'] ?? '',
                    'minLength' => 0,
                    'maxLength' => 0,
                    'isPremium' => false,
                    'premiumCostPricing' => [],
                ];
            }
        }

        return response()->json([
            'domain' => $selectedDomain,
            'suggestions' => $suggestions,
        ]);
    }
}
