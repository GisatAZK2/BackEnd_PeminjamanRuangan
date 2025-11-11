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

    // ========================
    // 🔹 LOGIN
    // ========================
    public function login()
    {
        header('Content-Type: application/json');
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->response(400, "Username dan password wajib diisi!");
        }

        // 🔍 Cari user di database
        $user = $this->model->findByUsername($username);
        if (!$user) {
            return $this->response(403, "Akun tidak ditemukan atau belum diverifikasi.");
        }

        // 🔒 Validasi password
        if (!password_verify($password, $user['password_hash'])) {
            return $this->response(401, "Password salah.");
        }

        // 🔎 Cek apakah user juga punya role "seller"
        $seller = $this->model->getSellerByEmail($user['email'] ?? '');

        // 🔄 Update status login user
        $this->model->setLoginStatus($user['id_user'], 1);

        // ========================
        // 🍪 Set cookie user_info
        // ========================
        $user_info = [
            "id_user"   => $user['id_user'],
            "username"  => $user['username'],
            "email"     => $user['email'] ?? null,
            "avatar"    => $user['avatar'] ?? null,
            "role"      => $user['role'] ?? 'peminjam',
            "seller_id" => $seller['id'] ?? null
        ];

        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        // Cookie 7 hari
        setcookie(
            "user_info",
            json_encode($user_info),
            [
                'expires'  => time() + (7 * 24 * 60 * 60),
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'None'
            ]
        );

        // ========================
        // ✅ Kirim respon sukses
        // ========================
        return $this->response(200, "Login sukses.", [
            "user_info" => $user_info
        ]);
    }

    // ========================
    // 🔹 LOGOUT
    // ========================
    public function logout()
    {
        header('Content-Type: application/json');
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        // Hapus cookie
        setcookie(
            "user_info",
            "",
            [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'None'
            ]
        );

        return $this->response(200, "Logout berhasil!");
    }

    // ========================
    // 🔹 Helper Response
    // ========================
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
