<?php

namespace App\Services\Payments;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class PaystackService
{
    public function stkPush(array $data): mixed
    {
        $paystackUrl = config('services.paystack.base_url').'/charge';

        $campaign = $data['campaign'];

        $paymentData = [
            'amount' => $data['amount'] * 100,
            'email' => $campaign->user->email,
            'first_name' => $campaign->user->name,
            'phone' => $campaign->user->phone_number,
            'currency' => 'KES',
            'mobile_money' => [
                'phone' => $data['phone_number'],
                'provider' => 'mpesa',
            ],
        ];

        return $this->paystackApiRequest($paystackUrl, $paymentData);
    }

    /**
     * @throws RequestException
     * @throws Exception
     */
    public function cardPayment(array $data): mixed
    {
        $paystackUrl = config('services.paystack.base_url').'/charge';
        $campaign = $data['campaign'];

        $paymentData = [
            'amount' => $data['amount'] * 100,
            'email' => $campaign->user->email,
            'currency' => 'KES',
            'card' => [
                'number' => $data['card_number'],
                'cvv' => $data['cvv'],
                'expiry_month' => $data['expiry_month'],
                'expiry_year' => $data['expiry_year'],
            ],
        ];

        return $this->paystackApiRequest($paystackUrl, $paymentData);
    }

    /**
     * @throws RequestException
     */
    public function chargeWithAuthorization(array $data): mixed
    {
        $paystackUrl = config('services.paystack.base_url').'/charge';

        $campaign = $data['campaign'];

        $paymentData = [
            'amount' => $data['amount'] * 100,
            'email' => $campaign->user->email,
            'authorization_code' => 'AUTH_ivr2qhuzcg',
            'currency' => 'KES',
        ];

        return $this->paystackApiRequest($paystackUrl, $paymentData);
    }

    /**
     * @throws RequestException
     * @throws Exception
     */
    public function queryTransaction(string $transactionRef): mixed
    {
        $paystackUrl = config('services.paystack.base_url');
        $paystackSecretKey = config('services.paystack.secret_key');

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$paystackSecretKey,
                ])
                ->get($paystackUrl.'/transaction/verify/'.$transactionRef);

            return $response->json();
        } catch (RequestException $e) {
            $error = $e->response->json();
            throw new Exception($error['data']['message'] ?? $error['message'] ?? 'Payment processing failed');
        }
    }

    /**
     * @throws RequestException
     * @throws Exception
     */
    private function paystackApiRequest(string $paystackUrl, array $paymentData): mixed
    {
        $paystackSecretKey = config('services.paystack.secret_key');

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$paystackSecretKey,
                ])
                ->post($paystackUrl, $paymentData);

            return $response->json();
        } catch (RequestException $e) {
            $error = $e->response->json();
            throw new Exception($error['data']['message'] ?? $error['message'] ?? 'Payment processing failed');
        }
    }
}
