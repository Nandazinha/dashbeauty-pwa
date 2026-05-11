<?php

namespace Config;

class Auth
{
    private static $secret_key = 'DashBeautySecretKey2026';

    public static function generateToken($user_id, $email, $user_type)
    {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'user_id' => $user_id,
            'email' => $email,
            'user_type' => $user_type,
            'exp' => time() + 86400
        ]));
        $signature = hash_hmac('sha256', "$header.$payload", self::$secret_key, true);
        $signature = base64_encode($signature);
        return "$header.$payload.$signature";
    }

    public static function validateToken($token)
    {
        $parts = explode('.', $token);
        if (count($parts) != 3) return null;

        $signature = hash_hmac('sha256', "$parts[0].$parts[1]", self::$secret_key, true);
        $signature = base64_encode($signature);

        if ($signature !== $parts[2]) return null;

        $payload = json_decode(base64_decode($parts[1]), true);
        if ($payload['exp'] < time()) return null;

        return [
            'user_id' => $payload['user_id'],
            'email' => $payload['email'],
            'user_type' => $payload['user_type']
        ];
    }

    public static function getTokenFromHeader()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) return null;
        return str_replace('Bearer ', '', $headers['Authorization']);
    }
}
