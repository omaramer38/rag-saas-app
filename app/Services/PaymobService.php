<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobService
{
    private string $apiKey;
    private string $integrationId;
    private string $hmacSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.paymob.api_key');
        $this->integrationId = config('services.paymob.integration_id');
        $this->hmacSecret = config('services.paymob.hmac_secret');
        $this->baseUrl = config('services.paymob.base_url', 'https://accept.paymob.com/api');
    }

    /**
     * Get authentication token from Paymob
     */
    public function getAuthToken(): string
    {
        $response = Http::withoutVerifying()
            ->post("{$this->baseUrl}/auth/tokens", [
                'api_key' => $this->apiKey,
            ]);

        $response->throw();

        return $response->json('token');
    }

    /**
     * Create an order on Paymob
     */
    public function createOrder(float $amount, string $currency = 'EGP'): int
    {
        $authToken = $this->getAuthToken();

        $response = Http::withoutVerifying()
            ->withHeaders(['Authorization' => "Bearer {$authToken}"])
            ->post("{$this->baseUrl}/ecommerce/orders", [
                'auth_token' => $authToken,
                'delivery_needed' => false,
                'amount' => round($amount * 100), // Paymob expects amount in piasters
                'currency' => $currency,
                'items' => [],
            ]);

        $response->throw();

        return $response->json('id');
    }

    /**
     * Get payment key for iframe checkout
     */
    public function getPaymentKey(
        int $orderId,
        User $user,
        float $amount,
        string $currency = 'EGP'
    ): string {
        $authToken = $this->getAuthToken();

        $response = Http::withoutVerifying()
            ->withHeaders(['Authorization' => "Bearer {$authToken}"])
            ->post("{$this->baseUrl}/acceptance/payment_keys", [
                'auth_token' => $authToken,
                'amount' => round($amount * 100),
                'expiration' => 3600,
                'order_id' => $orderId,
                'billing_data' => [
                    'apartment' => 'NA',
                    'email' => $user->email,
                    'floor' => 'NA',
                    'first_name' => explode(' ', $user->name)[0],
                    'last_name' => explode(' ', $user->name)[1] ?? '',
                    'phone_number' => $user->phone ?? '+201000000000',
                    'street' => 'NA',
                    'building' => 'NA',
                    'city' => 'Cairo',
                    'country' => 'EG',
                    'state' => 'Cairo',
                ],
                'currency' => $currency,
                'integration_id' => (int) $this->integrationId,
            ]);

        $response->throw();

        return $response->json('token');
    }

    /**
     * Verify webhook HMAC signature
     */
    public function verifyWebhookHmac(array $data, string $receivedHmac): bool
    {
        $fields = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_refunded',
            'is_standalone_payment',
            'method',
            'name',
            'order',
            'owner',
            'pending',
            'source_data',
            'success',
        ];

        $filteredData = array_intersect_key($data, array_flip($fields));

        // Sort the keys
        ksort($filteredData);

        // Flatten nested arrays
        $flattened = $this->flattenArray($filteredData);

        // HMAC the concatenated values
        $hmac = hash_hmac('sha512', implode('', $flattened), $this->hmacSecret);

        return hash_equals($hmac, $receivedHmac);
    }

    /**
     * Create a payment transaction record
     */
    public function createTransaction(
        User $user,
        UserSubscription $subscription,
        float $amount,
        int $orderId
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'currency' => 'EGP',
            'payment_method' => 'paymob',
            'paymob_order_id' => $orderId,
            'status' => 'pending',
        ]);
    }

    /**
     * Get iframe URL for payment
     */
    public function getIframeUrl(string $paymentKey): string
    {
        $iframeId = config('services.paymob.iframe_id');

        return "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentKey}";
    }

    /**
     * Flatten nested array for HMAC verification
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
