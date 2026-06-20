<?php
// utils/Jwt.php — implementasi JWT (HS256) sederhana, pengganti library jsonwebtoken
// Tidak butuh composer, murni PHP native.

class Jwt
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Buat token JWT.
     * @param array $payload  data yang ingin disimpan (mis. id, nama, role)
     * @param string $secret  JWT secret
     * @param int $expiresInSeconds masa berlaku token (default 8 jam, sama seperti aslinya)
     */
    public static function sign(array $payload, string $secret, int $expiresInSeconds = 8 * 3600): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $expiresInSeconds;

        $segments = [
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($payload)),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Verifikasi & decode token JWT.
     * Mengembalikan payload (array) jika valid, atau null jika tidak valid / kedaluwarsa.
     */
    public static function verify(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $signingInput = $headerB64 . '.' . $payloadB64;
        $expectedSignature = self::base64UrlEncode(hash_hmac('sha256', $signingInput, $secret, true));

        if (!hash_equals($expectedSignature, $signatureB64)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($payload)) return null;

        if (isset($payload['exp']) && time() > $payload['exp']) {
            return null; // token kedaluwarsa
        }

        return $payload;
    }
}
