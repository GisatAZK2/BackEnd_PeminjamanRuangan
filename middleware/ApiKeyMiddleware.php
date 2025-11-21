<?php
// middleware/ApiKeyMiddleware.php

class ApiKeyMiddleware
{
    public static function validate()
    {
        $headers = getallheaders();
        $api_key = $headers['api_key'] ?? ($headers['x-api-key'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? ''));

        if (!self::isValid($api_key)) {
            http_response_code(401);
            echo json_encode([
                "status" => "error",
                "message" => "API Key tidak valid atau tidak ditemukan."
            ]);
            exit;
        }
    }

    private static function isValid($key)
    {
        $file = __DIR__ . '/../keys/api_keys.txt';
        if (!file_exists($file)) return false;

        $keys = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return in_array(trim($key), array_map('trim', $keys));
    }
}
