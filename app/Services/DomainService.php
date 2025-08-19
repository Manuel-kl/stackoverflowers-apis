<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class DomainService
{
    protected string $endpoint;

    protected string $username;

    protected string $secret;

    protected array $headers;

    public function __construct()
    {
        $this->endpoint = config('services.hostraha.base_url');
        $this->username = config('services.hostraha.username');
        $this->secret = config('services.hostraha.api_key');

        $token = base64_encode(hash_hmac('sha256', $this->secret, $this->username.':'.gmdate('y-m-d H')));
        $this->headers = [
            'username' => $this->username,
            'token' => $token,
        ];
    }

    /**
     * Check domain availability
     */
    public function checkAvailability(array $params): Response
    {
        $action = '/domains/lookup';

        $response = Http::withHeaders($this->headers)
            ->asForm()
            ->post($this->endpoint.$action, $params);

        return $response;
    }

    /**
     * Get TLD pricing information
     */
    public function getTldPricing(): Response
    {
        $action = '/tlds/pricing';

        $response = Http::withHeaders($this->headers)
            ->asForm()
            ->post($this->endpoint.$action);

        return $response;
    }

    /**
     * Register a domain
     */
    public function registerDomain(array $params): Response
    {
        $action = '/order/domains/register';

        // Set default values if not provided
        $params = array_merge([
            'regperiod' => '1',
            'addons' => [
                'dnsmanagement' => 0,
                'emailforwarding' => 1,
                'idprotection' => 1,
            ],
        ], $params);

        $response = Http::withHeaders($this->headers)
            ->asForm()
            ->post($this->endpoint.$action, $params);

        return $response;
    }

    /**
     * Renew a domain
     */
    public function renewDomain(array $params): Response
    {
        $action = '/order/domains/renew';

        // Set default values if not provided
        $params = array_merge([
            'regperiod' => '3',
            'addons' => [
                'dnsmanagement' => 0,
                'emailforwarding' => 1,
                'idprotection' => 1,
            ],
        ], $params);

        $response = Http::withHeaders($this->headers)
            ->asForm()
            ->post($this->endpoint.$action, $params);

        return $response;
    }
}
