<?php

namespace App\Services\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SocialIdentityResolver
{
    /**
     * @return array{provider: string, provider_user_id: string, email: string|null, name: string|null, data: array<string, mixed>}
     *
     * @throws ValidationException
     */
    public function resolve(string $provider, string $providerToken): array
    {
        return match ($provider) {
            'google' => $this->resolveGoogleIdentity($providerToken),
            'facebook' => $this->resolveFacebookIdentity($providerToken),
            'apple' => $this->resolveAppleIdentity($providerToken),
            default => throw ValidationException::withMessages([
                'provider' => 'Unsupported social provider.',
            ]),
        };
    }

    /**
     * @return array{provider: string, provider_user_id: string, email: string|null, name: string|null, data: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function resolveGoogleIdentity(string $providerToken): array
    {
        $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $providerToken,
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'provider_token' => 'Unable to verify Google token.',
            ]);
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $subject = $payload['sub'] ?? null;

        if (! is_string($subject) || trim($subject) === '') {
            throw ValidationException::withMessages([
                'provider_token' => 'Google token did not include a valid subject.',
            ]);
        }

        return [
            'provider' => 'google',
            'provider_user_id' => $subject,
            'email' => is_string($payload['email'] ?? null) ? $payload['email'] : null,
            'name' => is_string($payload['name'] ?? null) ? $payload['name'] : null,
            'data' => $payload,
        ];
    }

    /**
     * @return array{provider: string, provider_user_id: string, email: string|null, name: string|null, data: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function resolveFacebookIdentity(string $providerToken): array
    {
        $response = Http::timeout(10)->get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email',
            'access_token' => $providerToken,
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'provider_token' => 'Unable to verify Facebook token.',
            ]);
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $id = $payload['id'] ?? null;

        if (! is_string($id) || trim($id) === '') {
            throw ValidationException::withMessages([
                'provider_token' => 'Facebook token did not include a valid identity.',
            ]);
        }

        return [
            'provider' => 'facebook',
            'provider_user_id' => $id,
            'email' => is_string($payload['email'] ?? null) ? $payload['email'] : null,
            'name' => is_string($payload['name'] ?? null) ? $payload['name'] : null,
            'data' => $payload,
        ];
    }

    /**
     * @return array{provider: string, provider_user_id: string, email: string|null, name: string|null, data: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function resolveAppleIdentity(string $providerToken): array
    {
        $parts = explode('.', $providerToken);

        if (count($parts) !== 3) {
            throw ValidationException::withMessages([
                'provider_token' => 'Apple token format is invalid.',
            ]);
        }

        $header = $this->decodeJwtSection($parts[0], 'header');
        $payload = $this->decodeJwtSection($parts[1], 'payload');
        $signature = $this->decodeBase64Url($parts[2], 'signature');

        $kid = $header['kid'] ?? null;
        $alg = $header['alg'] ?? null;

        if (! is_string($kid) || $kid === '' || ! is_string($alg) || $alg !== 'RS256') {
            throw ValidationException::withMessages([
                'provider_token' => 'Apple token header is invalid.',
            ]);
        }

        $appleKey = $this->fetchAppleKey($kid);
        $publicKey = $this->buildRsaPublicKeyFromJwk($appleKey);

        $verified = openssl_verify(
            $parts[0].'.'.$parts[1],
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256,
        );

        if ($verified !== 1) {
            throw ValidationException::withMessages([
                'provider_token' => 'Unable to verify Apple token signature.',
            ]);
        }

        $issuer = $payload['iss'] ?? null;
        $subject = $payload['sub'] ?? null;
        $audience = $payload['aud'] ?? null;
        $expiresAt = $payload['exp'] ?? null;

        if ($issuer !== 'https://appleid.apple.com') {
            throw ValidationException::withMessages([
                'provider_token' => 'Apple token issuer is invalid.',
            ]);
        }

        if (! is_string($subject) || trim($subject) === '') {
            throw ValidationException::withMessages([
                'provider_token' => 'Apple token did not include a valid subject.',
            ]);
        }

        if (! is_numeric($expiresAt) || CarbonImmutable::createFromTimestamp((int) $expiresAt)->isPast()) {
            throw ValidationException::withMessages([
                'provider_token' => 'Apple token is expired.',
            ]);
        }

        /** @var array<int, string> $clientIds */
        $clientIds = config('services.apple.client_ids', []);

        if ($clientIds !== [] && (! is_string($audience) || ! in_array($audience, $clientIds, true))) {
            throw ValidationException::withMessages([
                'provider_token' => 'Apple token audience is invalid.',
            ]);
        }

        return [
            'provider' => 'apple',
            'provider_user_id' => $subject,
            'email' => is_string($payload['email'] ?? null) ? $payload['email'] : null,
            'name' => is_string($payload['name'] ?? null) ? $payload['name'] : null,
            'data' => $payload,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function decodeJwtSection(string $encoded, string $section): array
    {
        $decoded = $this->decodeBase64Url($encoded, $section);
        $decodedArray = json_decode($decoded, true);

        if (! is_array($decodedArray)) {
            throw ValidationException::withMessages([
                'provider_token' => sprintf('Apple token %s is invalid.', $section),
            ]);
        }

        /** @var array<string, mixed> $decodedArray */
        return $decodedArray;
    }

    /**
     * @throws ValidationException
     */
    private function decodeBase64Url(string $value, string $section): string
    {
        $remainder = strlen($value) % 4;
        $padded = $remainder === 0 ? $value : $value.str_repeat('=', 4 - $remainder);
        $normalized = strtr($padded, '-_', '+/');
        $decoded = base64_decode($normalized, true);

        if (! is_string($decoded)) {
            throw ValidationException::withMessages([
                'provider_token' => sprintf('Apple token %s could not be decoded.', $section),
            ]);
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function fetchAppleKey(string $kid): array
    {
        $response = Http::timeout(10)->get('https://appleid.apple.com/auth/keys');

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'provider_token' => 'Unable to fetch Apple public keys.',
            ]);
        }

        /** @var array{keys?: array<int, array<string, mixed>>} $payload */
        $payload = $response->json();
        $keys = $payload['keys'] ?? [];

        foreach ($keys as $key) {
            if (($key['kid'] ?? null) === $kid) {
                return $key;
            }
        }

        throw ValidationException::withMessages([
            'provider_token' => 'No matching Apple public key found.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $jwk
     *
     * @throws ValidationException
     */
    private function buildRsaPublicKeyFromJwk(array $jwk): string
    {
        $modulus = $jwk['n'] ?? null;
        $exponent = $jwk['e'] ?? null;

        if (! is_string($modulus) || ! is_string($exponent)) {
            throw ValidationException::withMessages([
                'provider_token' => 'Apple public key payload is invalid.',
            ]);
        }

        $modulusBinary = $this->decodeBase64Url($modulus, 'modulus');
        $exponentBinary = $this->decodeBase64Url($exponent, 'exponent');

        $sequence = $this->asn1EncodeSequence([
            $this->asn1EncodeInteger($modulusBinary),
            $this->asn1EncodeInteger($exponentBinary),
        ]);

        $bitString = "\x00".$sequence;
        $algorithmIdentifier = $this->asn1EncodeSequence([
            "\x06\x09\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01",
            "\x05\x00",
        ]);

        $subjectPublicKeyInfo = $this->asn1EncodeSequence([
            $algorithmIdentifier,
            "\x03".$this->asn1EncodeLength(strlen($bitString)).$bitString,
        ]);

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    private function asn1EncodeSequence(array $items): string
    {
        $payload = implode('', $items);

        return "\x30".$this->asn1EncodeLength(strlen($payload)).$payload;
    }

    private function asn1EncodeInteger(string $value): string
    {
        if (ord($value[0]) > 0x7F) {
            $value = "\x00".$value;
        }

        return "\x02".$this->asn1EncodeLength(strlen($value)).$value;
    }

    private function asn1EncodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $binary = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($binary)).$binary;
    }
}
