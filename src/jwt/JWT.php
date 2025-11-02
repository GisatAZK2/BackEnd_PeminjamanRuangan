<?php
class JWT
{
    private static $secret = 'your-super-secret-jwt-key-2025'; // ubah ke env

    public static function encode($payload, $exp = 3600)
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['exp'] = time() + $exp;
        $payload = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "$header.$payload", self::$secret, true);
        $signature = base64_encode($signature);
        return "$header.$payload.$signature";
    }

    public static function decode($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        [$header, $payload, $signature] = $parts;
        $expected = base64_encode(hash_hmac('sha256', "$header.$payload", self::$secret, true));

        if (!hash_equals($expected, $signature)) return false;

        $payload = json_decode(base64_decode($payload), true);
        if (!isset($payload['exp']) || $payload['exp'] < time()) return false;

        return $payload;
    }
}
