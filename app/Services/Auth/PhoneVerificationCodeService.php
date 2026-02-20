<?php

namespace App\Services\Auth;

use App\Models\PhoneVerificationCode;
use Illuminate\Support\Facades\Hash;

class PhoneVerificationCodeService
{
    public function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/(?!^\+)[^\d]/', '', trim($phone));

        return is_string($normalized) ? $normalized : trim($phone);
    }

    /**
     * @return array{phone: string, code: string, expires_at: string}
     */
    public function issueCode(string $phone): array
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes((int) config('services.whatsapp.verification_ttl_minutes', 10));

        PhoneVerificationCode::query()->updateOrCreate(
            ['phone' => $normalizedPhone],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'sent_at' => now(),
                'expires_at' => $expiresAt,
                'verified_at' => null,
                'consumed_at' => null,
            ],
        );

        return [
            'phone' => $normalizedPhone,
            'code' => $code,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function verifyCode(string $phone, string $code): bool
    {
        $normalizedPhone = $this->normalizePhone($phone);

        $verificationCode = PhoneVerificationCode::query()
            ->where('phone', $normalizedPhone)
            ->first();

        if ($verificationCode === null || $verificationCode->expires_at->isPast()) {
            return false;
        }

        $maxAttempts = (int) config('services.whatsapp.verification_max_attempts', 5);

        if ($verificationCode->attempts >= $maxAttempts) {
            return false;
        }

        if (! Hash::check($code, $verificationCode->code_hash)) {
            $verificationCode->increment('attempts');

            return false;
        }

        $verificationCode->forceFill([
            'verified_at' => now(),
        ])->save();

        return true;
    }

    public function consumeVerifiedCode(string $phone, string $code): bool
    {
        $normalizedPhone = $this->normalizePhone($phone);

        $verificationCode = PhoneVerificationCode::query()
            ->where('phone', $normalizedPhone)
            ->first();

        if (
            $verificationCode === null
            || $verificationCode->expires_at->isPast()
            || $verificationCode->consumed_at !== null
            || $verificationCode->verified_at === null
            || ! Hash::check($code, $verificationCode->code_hash)
        ) {
            return false;
        }

        $verificationCode->forceFill([
            'consumed_at' => now(),
        ])->save();

        return true;
    }
}
