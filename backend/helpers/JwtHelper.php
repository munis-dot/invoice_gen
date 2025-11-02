<?php
class JwtHelper {
    // 🔐 Secret key (you can move this to an .env file later)
    private static string $secret = 'invoice_gen_secret_key_2025';

    // ✅ Encode JWT
    public static function encode(array $payload, int $expiryMinutes = 60): string {
        // Add expiration (default: 60 minutes)
        $payload['exp'] = time() + ($expiryMinutes * 60 * 24);

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $headerEnc = self::base64UrlEncode(json_encode($header));
        $payloadEnc = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$headerEnc.$payloadEnc", self::$secret, true);
        $signatureEnc = self::base64UrlEncode($signature);

        return "$headerEnc.$payloadEnc.$signatureEnc";
    }

    // ✅ Decode JWT (returns payload or null if invalid/expired)
    public static function decode(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEnc, $payloadEnc, $signatureEnc] = $parts;

        // Recreate signature
        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "$headerEnc.$payloadEnc", self::$secret, true)
        );

        // Validate signature
        if (!hash_equals($expectedSig, $signatureEnc)) {
            return null;
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEnc), true);
        if (!$payload) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && time() > $payload['exp']) {
            return null; // Token expired
        }

        return $payload;
    }

    // 🧰 Helpers for URL-safe base64
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
