<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../src/jwt/JWT.php';

class AuthController
{
    private $model;

    public function __construct($pdo)
    {
        $this->model = new UserModel($pdo);
    }

    public function login()
    {
        header('Content-Type: application/json');

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->response(400, "Username dan password wajib diisi!");
        }

        $user = $this->model->findByUsername($username);
        if (!$user || $user['password'] !== md5($password)) {
            return $this->response(401, "Username atau password salah!");
        }

        // Update status login
        $this->model->setLoginStatus($user['id_user'], 1);

        $user_info = [
            "id_user"  => $user['id_user'],
            "username" => $user['username'],
            "role"     => $user['role'],
            "nama"     => $user['nama'] ?? ''
        ];

        $token = JWT::encode($user_info, 3600);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        // ✅ Cookie JWT
        setcookie("token", $token, [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => false, // biar bisa diakses JS
            'samesite' => 'None'
        ]);

        // ✅ Cookie user_info
        setcookie("user_info", json_encode($user_info), [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => false,
            'samesite' => 'None'
        ]);

        return $this->response(200, "Login berhasil!", [
            "token" => $token,
            "user_info" => $user_info
        ]);
    }

    public function logout()
    {
        header('Content-Type: application/json');
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        foreach (['token', 'user_info'] as $cookie) {
            setcookie($cookie, "", [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $secure,
                'httponly' => false,
                'samesite' => 'None'
            ]);
        }

        return $this->response(200, "Logout berhasil!");
    }

    private function response($status, $message, $data = [])
    {
        http_response_code($status);
        echo json_encode([
            "status" => $status === 200 ? "success" : "error",
            "message" => $message,
            ...$data
        ]);
        exit;
    }
}
