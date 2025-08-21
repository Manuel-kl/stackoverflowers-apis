<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Models\KeDomainPricing;
use App\Services\DomainService;
use Illuminate\Http\JsonResponse;

class TldController extends Controller
{
    protected DomainService $domainService;

    public function __construct(DomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function pricing(): JsonResponse
    {
        $response = $this->domainService->getTldPricing();

        if ($response->successful()) {
            $data = $response->json();

            $keDomains = collect($data)->filter(function ($item) {
                return str_ends_with($item['tld'], '.ke') && $item['currencyCode'] === 'KES';
            })->values();

            foreach ($keDomains as $domain) {
                $this->saveKeDomainPricing($domain);
            }

            return response()->json($keDomains);
        }

        return response()->json([
            'error' => 'Could not fetch TLD pricing.',
            'details' => $response->body(),
        ], $response->status() ?: 500);
    }

    public function all(): JsonResponse
    {
        $keDomains = KeDomainPricing::where('is_active', true)->get();

        return response()->json($keDomains);
    }

    private function saveKeDomainPricing(array $domainData): void
    {
        $tldNames = [
            '.ke' => 'Kenya General',
            '.co.ke' => 'Companies & Businesses',
            '.or.ke' => 'Non-profit Organizations',
            '.ac.ke' => 'Higher Learning Institutions',
            '.sc.ke' => 'Primary & Secondary Schools',
            '.go.ke' => 'Government Institutions',
            '.ne.ke' => 'Network Providers',
            '.me.ke' => 'Personal Domains',
            '.mobi.ke' => 'Mobile Content Services',
            '.info.ke' => 'Information Services',
        ];

        $descriptions = [
            '.ke' => 'For any Kenyan entity, general use',
            '.co.ke' => 'Companies, businesses, and commercial entities',
            '.or.ke' => 'Non-profit organizations, NGOs, CBOs',
            '.ac.ke' => 'Accredited higher learning institutions (universities, colleges)',
            '.sc.ke' => 'Primary and secondary schools',
            '.go.ke' => 'Government institutions only',
            '.ne.ke' => 'Network providers, ISPs',
            '.me.ke' => 'Personal domains for individuals',
            '.mobi.ke' => 'Mobile content/services',
            '.info.ke' => 'Information services/resources',
        ];

        KeDomainPricing::updateOrCreate(
            ['tld' => $domainData['tld']],
            [
                'name' => $tldNames[$domainData['tld']] ?? $domainData['tld'],
                'description' => $descriptions[$domainData['tld']] ?? null,
                'registration_price' => $domainData['registrationPrice'],
                'renewal_price' => $domainData['renewalPrice'],
                'transfer_price' => $domainData['transferPrice'],
                'grace_fee' => $domainData['graceFee'],
                'grace_days' => $domainData['graceDays'],
                'redemption_days' => $domainData['redemptionDays'],
                'redemption_fee' => $domainData['redemptionFee'],
                'years' => $domainData['years'],
                'is_active' => true,
            ]
        );
    }
}
