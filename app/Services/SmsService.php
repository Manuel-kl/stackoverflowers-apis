<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class SmsService
{
    public function sendSmsCode(array $data): bool
    {
        $apiKey = config('services.sms.api_key');
        $endpoint = config('services.sms.endpoint');
        $from = config('services.sms.from');

        if (!$apiKey || !$endpoint || !$from) {
            logger()->error('SMS configuration missing');
            throw new Exception('Server error');
        }

        $requestBody = [
            'to' => $data['phone_number'] ?? null,
            'from' => $from,
            'message' => $data['message'] ?? null,
        ];

        if (!$requestBody['to'] || !$requestBody['message']) {
            throw new Exception('Invalid SMS data');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $requestBody);

        if ($response->successful()) {
            return true;
        }

        $errorBody = $response->json() ?? ['error' => 'An error occurred'];
        throw new Exception(json_encode($errorBody), $response->status());
    }
}
