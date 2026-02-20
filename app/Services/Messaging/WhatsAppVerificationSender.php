<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppVerificationSender
{
    public function sendVerificationCode(string $phone, string $code): bool
    {
        $driver = (string) config('services.whatsapp.driver', 'log');

        if ($driver === 'log') {
            Log::info('WhatsApp verification code generated.', [
                'phone' => $phone,
                'code' => $code,
            ]);

            return true;
        }

        $webhookUrl = config('services.whatsapp.webhook_url');

        if (! is_string($webhookUrl) || trim($webhookUrl) === '') {
            return false;
        }

        $token = (string) config('services.whatsapp.token', '');
        $from = (string) config('services.whatsapp.from', 'Mitho Map');
        $timeout = (int) config('services.whatsapp.timeout', 10);

        $message = sprintf('%s verification code: %s', $from, $code);

        $request = Http::timeout($timeout);

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($webhookUrl, [
            'phone' => $phone,
            'message' => $message,
            'code' => $code,
        ]);

        return $response->successful();
    }
}
