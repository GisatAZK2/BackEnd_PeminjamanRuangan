<?php
require_once __DIR__ . '/../controllers/UserController.php';

class AuthMiddleware
{
    public static function requireRole(array $allowedRoles)
    {
        if (!isset($_COOKIE['user_info'])) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Login diperlukan."]);
            exit;
        }

        $user = json_decode(urldecode($_COOKIE['user_info']), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Cookie tidak valid."]);
            exit;
        }

        if (!in_array($user['role'], $allowedRoles)) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Akses ditolak untuk role ini."]);
            exit;
        }

        return $user; // return user biar bisa dipakai controller
    }
}
