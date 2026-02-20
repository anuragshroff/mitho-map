<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendPhoneVerificationCodeRequest;
use App\Http\Requests\Api\V1\VerifyPhoneVerificationCodeRequest;
use App\Services\Auth\PhoneVerificationCodeService;
use App\Services\Messaging\WhatsAppVerificationSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    public function __construct(
        public PhoneVerificationCodeService $verificationCodeService,
        public WhatsAppVerificationSender $whatsAppVerificationSender,
    ) {}

    public function sendCode(SendPhoneVerificationCodeRequest $request): JsonResponse
    {
        $payload = $this->verificationCodeService->issueCode($request->validated('phone'));

        $messageSent = $this->whatsAppVerificationSender->sendVerificationCode(
            $payload['phone'],
            $payload['code'],
        );

        if (! $messageSent) {
            return response()->json([
                'message' => 'Unable to send verification code right now.',
            ], 503);
        }

        return response()->json([
            'message' => 'Verification code sent.',
            'phone' => $payload['phone'],
            'expires_at' => $payload['expires_at'],
        ], 202);
    }

    /**
     * @throws ValidationException
     */
    public function verifyCode(VerifyPhoneVerificationCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $verified = $this->verificationCodeService->verifyCode(
            $validated['phone'],
            $validated['code'],
        );

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => 'Verification code is invalid or expired.',
            ]);
        }

        return response()->json([
            'message' => 'Phone number verified.',
        ]);
    }
}
