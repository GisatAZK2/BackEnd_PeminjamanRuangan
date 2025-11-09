<?php
require_once __DIR__ . '/../models/UserModel.php';

class AuthController
{
    private $model;
    private $cache;

    
    public function __construct(PDO $pdo, $cache)
    {
        $this->cache = $cache;
        $this->model = new UserModel($pdo, $cache);
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
        if (!$user || !password_verify($password, $user['password_hash'])) {
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

        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

      
        setcookie("user_info", json_encode($user_info), [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => false,
            'samesite' => 'None'
        ]);

        return $this->response(200, "Login berhasil!", [
            "user_info" => $user_info
        ]);
    }

    public function logout()
    {
        header('Content-Type: application/json');
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        foreach (['user_info'] as $cookie) {
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
