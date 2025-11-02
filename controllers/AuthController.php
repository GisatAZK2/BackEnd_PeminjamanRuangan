<?php
require_once __DIR__ . '/../models/UserModel.php';

class AuthController
{
    private $model;

    public function __construct($pdo)
    {
        $this->model = new UserModel($pdo);
    }

    // ======================================
    // 🔹 LOGIN
    // ======================================
    public function login()
    {
        header('Content-Type: application/json');

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->response(400, "Username dan password wajib diisi!");
        }

        $user = $this->model->findByUsername($username);
        if (!$user || md5($password) !== $user['password']) {
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

        // Simpan cookie login
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie("user_info", json_encode($user_info), [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return $this->response(200, "Login berhasil!", ["user_info" => $user_info]);
    }

    // ======================================
    // 🔹 LOGOUT
    // ======================================
    public function logout()
    {
        header('Content-Type: application/json');

        $user_info_cookie = $_COOKIE['user_info'] ?? null;
        if (!$user_info_cookie) {
            return $this->response(400, "Tidak ada sesi login yang aktif.");
        }

        $user_info = json_decode($user_info_cookie, true);
        if (!isset($user_info['id_user'])) {
            return $this->response(400, "Data sesi tidak valid.");
        }

        $id_user = (int)$user_info['id_user'];

        // Update status logout di DB
        try {
            $this->model->setLogoutStatus($id_user);
        } catch (Exception $e) {
            return $this->response(500, "Gagal logout: " . $e->getMessage());
        }

        // Hapus cookie
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie("user_info", "", [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return $this->response(200, "Logout berhasil!");
    }

    // ======================================
    // 🔹 HELPER RESPONSE
    // ======================================
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
