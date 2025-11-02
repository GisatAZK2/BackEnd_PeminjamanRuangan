<?php
require_once __DIR__ . '/../src/jwt/JWT.php';

class JwtAuthMiddleware
{
    public static function handle()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $cookieToken = $_COOKIE['jwt_token'] ?? null;

        $token = null;

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } elseif ($cookieToken) {
            $token = $cookieToken;
        }

        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token tidak ditemukan"]);
            exit;
        }

        $payload = JWT::decode($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Token tidak valid atau kadaluarsa"]);
            exit;
        }

        $GLOBALS['current_user'] = $payload;
    }

    public static function getUser()
    {
        return $GLOBALS['current_user'] ?? null;
    }
}
