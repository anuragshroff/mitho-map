<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    /**
     * @return array<string, mixed>
     */
    public function initiateEsewaPayment(Order $order): array
    {
        $merchantId = SystemSetting::getValue('payment_esewa_merchant_id');

        if (! $merchantId) {
            abort(400, 'eSewa is not configured.');
        }

        // eSewa ePay v2 implementation (simplified for demo)
        $amount = number_format($order->total_cents / 100, 2, '.', '');
        $tax = 0;
        $total = $amount;
        $transactionId = "ORDER-{$order->id}-".uniqid();
        $secret = SystemSetting::getValue('payment_esewa_secret', '8gBm/:&EnhH.1/q');

        $message = "total_amount=$total,transaction_uuid=$transactionId,product_code=$merchantId";
        $signature = base64_encode(hash_hmac('sha256', $message, $secret, true));

        return [
            'provider' => 'esewa',
            'url' => 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
            'method' => 'POST',
            'params' => [
                'amount' => $amount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'transaction_uuid' => $transactionId,
                'product_code' => $merchantId,
                'product_service_charge' => '0',
                'product_delivery_charge' => '0',
                'success_url' => url("/api/v1/payments/esewa/callback?order_id={$order->id}"),
                'failure_url' => url("/api/v1/payments/esewa/callback?order_id={$order->id}&status=failed"),
                'signed_field_names' => 'total_amount,transaction_uuid,product_code',
                'signature' => $signature,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function initiateKhaltiPayment(Order $order): array
    {
        $publicKey = SystemSetting::getValue('payment_khalti_public_key');
        $secretKey = SystemSetting::getValue('payment_khalti_secret_key');

        if (! $publicKey || ! $secretKey) {
            abort(400, 'Khalti is not configured.');
        }

        $payload = [
            'return_url' => url("/api/v1/payments/khalti/callback?order_id={$order->id}"),
            'website_url' => url('/'),
            'amount' => $order->total_cents, // Khalti expects paisa (cents)
            'purchase_order_id' => (string) $order->id,
            'purchase_order_name' => "Order #{$order->id} from Mitho Map",
            'customer_info' => [
                'name' => $order->customer->name ?? 'Customer',
                'email' => $order->customer->email ?? 'test@example.com',
                'phone' => $order->customer->phone ?? '9800000000',
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Key {$secretKey}",
        ])->post('https://a.khalti.com/api/v2/epayment/initiate/', $payload);

        if (! $response->successful()) {
            abort(400, 'Failed to initiate Khalti payment: '.$response->body());
        }

        return [
            'provider' => 'khalti',
            'url' => $response->json('payment_url'),
            'method' => 'GET',
            'params' => [],
        ];
    }
}
