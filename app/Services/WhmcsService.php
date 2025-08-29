<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhmcsService
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.whmcs.url'), '/');
        $this->username = (string) config('services.whmcs.identifier');
        $this->password = (string) config('services.whmcs.secret');
    }

    public function addOrder(array $payload): array
    {
        $body = array_merge($payload, [
            'action' => 'AddOrder',
            'username' => $this->username,
            'password' => $this->password,
            'responsetype' => 'json',
        ]);

        $response = Http::asForm()->timeout(300)->post($this->baseUrl.'/includes/api.php', $body);

        if ($response->failed()) {
            $this->logError('WHMCS AddOrder request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Failed to connect to WHMCS API');
        }

        $result = $response->json();

        if (!isset($result['result']) || $result['result'] !== 'success') {
            $this->logError('WHMCS AddOrder error', [
                'message' => $result['message'] ?? 'Unknown error',
                'data' => $result,
            ]);
            throw new \RuntimeException('WHMCS API error: '.($result['message'] ?? 'Unknown error'));
        }

        return $result;
    }

    public function getInvoice(int $invoiceId): array
    {
        $body = [
            'action' => 'GetInvoice',
            'username' => $this->username,
            'password' => $this->password,
            'responsetype' => 'json',
            'invoiceid' => $invoiceId,
        ];

        $response = Http::asForm()->timeout(300)->post($this->baseUrl.'/includes/api.php', $body);
        if ($response->failed()) {
            throw new \RuntimeException('Failed to connect to WHMCS API');
        }
        $result = $response->json();
        if (($result['result'] ?? 'error') !== 'success') {
            throw new \RuntimeException('WHMCS API error: '.($result['message'] ?? 'Unknown error'));
        }

        return $result;
    }

    public function getOrder(int $orderId): array
    {
        $body = [
            'action' => 'GetOrders',
            'username' => $this->username,
            'password' => $this->password,
            'responsetype' => 'json',
            'id' => $orderId,
        ];

        $response = Http::asForm()->timeout(300)->post($this->baseUrl.'/includes/api.php', $body);
        if ($response->failed()) {
            throw new \RuntimeException('Failed to connect to WHMCS API');
        }
        $result = $response->json();
        if (($result['result'] ?? 'error') !== 'success') {
            throw new \RuntimeException('WHMCS API error: '.($result['message'] ?? 'Unknown error'));
        }

        return $result;
    }

    public function acceptOrder(
        int $orderId,
        ?int $serverId = null,
        ?string $serviceUsername = null,
        ?string $servicePassword = null,
        ?string $registrar = null
    ): array {
        $body = array_filter([
            'action' => 'AcceptOrder',
            'username' => $this->username,
            'password' => $this->password,
            'responsetype' => 'json',
            'orderid' => $orderId,
            'serverid' => $serverId,
            'serviceusername' => $serviceUsername,
            'servicepassword' => $servicePassword,
            'registrar' => $registrar,
            'sendregistrar' => true,
            'autosetup' => true,
            'sendemail' => true,
        ], fn ($v) => !is_null($v));

        $response = Http::asForm()->timeout(300)->post($this->baseUrl.'/includes/api.php', $body);
        if ($response->failed()) {
            $this->logError('WHMCS AcceptOrder request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to connect to WHMCS API');
        }

        $result = $response->json();
        if (!isset($result['result']) || $result['result'] !== 'success') {
            $this->logError('WHMCS AcceptOrder error', [
                'message' => $result['message'] ?? 'Unknown error',
                'data' => $result,
            ]);
        }

        return $result ?? ['result' => 'error', 'message' => 'No response'];
    }

    public function addInvoicePayment(array $data): array
    {
        $body = array_filter([
            'action' => 'AddInvoicePayment',
            'username' => $this->username,
            'password' => $this->password,
            'responsetype' => 'json',
            'invoiceid' => $data['invoiceid'],
            'transid' => $data['transid'],
            'gateway' => $data['gateway'],
            'date' => $data['date'],
            'noemail' => $data['noemail'] ?? false,
        ], fn ($v) => !is_null($v));

        $response = Http::asForm()
            ->timeout(300)
            ->post($this->baseUrl.'/includes/api.php', $body);
        if ($response->failed()) {
            $this->logError('WHMCS AddInvoicePayment request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to connect to WHMCS API');
        }

        $result = $response->json();
        if (!isset($result['result']) || $result['result'] !== 'success') {
            $this->logError('WHMCS AddInvoicePayment error', [
                'message' => $result['message'] ?? 'Unknown error',
                'data' => $result,
            ]);
        }

        return $result ?? ['result' => 'error', 'message' => 'No response'];
    }

    public function domainRegister(int $domainId, ?string $domain = null, ?string $idnLanguage = null): array
    {
        $body = array_filter([
            'action' => 'DomainRegister',
            'username' => $this->username,
            'password' => $this->password,
            'responsetype' => 'json',
            'domainid' => $domainId,
            'domain' => $domain,
            'idnlanguage' => $idnLanguage,
            ''
        ], fn ($v) => !is_null($v));

        $response = Http::asForm()->timeout(300)->post($this->baseUrl.'/includes/api.php', $body);
        if ($response->failed()) {
            $this->logError('WHMCS DomainRegister request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'domain_id' => $domainId,
            ]);
            throw new \RuntimeException('Failed to connect to WHMCS API');
        }

        $result = $response->json();
        if (!isset($result['result']) || $result['result'] !== 'success') {
            $this->logError('WHMCS DomainRegister error', [
                'message' => $result['message'] ?? 'Unknown error',
                'data' => $result,
                'domain_id' => $domainId,
            ]);
        }

        return $result ?? ['result' => 'error', 'message' => 'No response'];
    }

    protected function logError(string $message, array $context = []): void
    {
        $redacted = $context;
        if (isset($redacted['request_data'])) {
            unset($redacted['request_data']['username'], $redacted['request_data']['password']);
        }
        Log::error($message, $redacted);
    }
}
