<?php
class CorsMiddleware
{
    public static function handle()
    {
        $allowed_origins = [
            'http://127.0.0.1:5500',
            'http://localhost:5500',
            'http://localhost:5173',
            'http://php-peminjamanruangrapat.test',
            'https://tfvf36m1-80.asse.devtunnels.ms',
            'https://dentists-receptors-thought-manufacturers.trycloudflare.com'
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
