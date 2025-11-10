<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class UserController
{
    private $model;

    public function __construct(PDO $pdo, Psr\SimpleCache\CacheInterface $cache)
    {
        $this->model = new UserModel($pdo, $cache);
    }

    /**
     * 🔹 Helper untuk kirim respons JSON
     */
    private function sendResponse($status, $message, $data = null, $httpCode = 200)
    {
        http_response_code($httpCode);
        $res = ["status" => $status, "message" => $message];
        if ($data !== null) $res['data'] = $data;
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 🔹 Ambil data user dari cookie user_info dan verifikasi dengan database
     */
    private function getUserFromCookie()
    {
        if (!isset($_COOKIE['user_info'])) {
            $this->sendResponse("error", "Cookie user_info tidak ditemukan. Silakan login terlebih dahulu.", null, 401);
        }

        $decoded = urldecode($_COOKIE['user_info']);
        $user = json_decode($decoded, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($user['id_user'])) {
            $this->sendResponse("error", "Cookie user_info tidak valid.", null, 400);
        }

        // 🔍 Verifikasi dengan database
        $dbUser = $this->model->getUserById($user['id_user']);
        if (
            !$dbUser ||
            $dbUser['username'] !== $user['username'] ||
            !$dbUser['is_logged_in']
        ) {
            $this->sendResponse("error", "Sesi tidak valid atau telah logout. Silakan login kembali.", null, 401);
        }

        return $dbUser; // ✅ Data dari database (lebih akurat)
    }

    // ===============================
    // 🔹 GET ALL USERS
    // ===============================
    public function getAll()
    {
        $user = AuthMiddleware::requireRole(['administrator', 'petugas'], $this->model);

        if ($user['role'] === 'administrator') {
            $users = $this->model->getAllUsers();
            // Admin tidak bisa lihat dirinya sendiri di daftar
            $users = array_filter($users, fn($u) => $u['id_user'] !== $user['id_user']);
        } else {
            $users = $this->model->getAllUsers('peminjam');
        }

        $this->sendResponse("success", "Daftar user berhasil diambil.", array_values($users));
    }

    // ===============================
    // 🔹 GET DETAIL USER
    // ===============================
    public function getDetail()
    {
        $user = $this->getUserFromCookie();
        $id = isset($_GET['id_user']) ? (int)$_GET['id_user'] : $user['id_user'];

        // 🔐 Validasi akses
        if ($id !== $user['id_user']) {
            if ($user['role'] === 'administrator') {
                // Admin bisa lihat semua
            } elseif ($user['role'] === 'petugas') {
                $target = $this->model->getUserById($id);
                if (!$target || $target['role'] !== 'peminjam') {
                    return $this->sendResponse("error", "Petugas hanya bisa melihat detail user peminjam.", null, 403);
                }
            } else {
                return $this->sendResponse("error", "Akses ditolak.", null, 403);
            }
        }

        $targetUser = $this->model->getUserById($id);
        if (!$targetUser) {
            return $this->sendResponse("error", "User tidak ditemukan.", null, 404);
        }

        $this->sendResponse("success", "Data user ditemukan.", $targetUser);
    }

    // ===============================
    // 🔹 ADD USER
    // ===============================
    public function add()
{
    $user = AuthMiddleware::requireRole(['administrator', 'petugas'], $this->model);
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return $this->sendResponse("error", "Format JSON tidak valid.", null, 400);
    }

    $required = ['username', 'nama', 'email', 'password', 'role', 'nomor_telepon', 'id_divisi'];
    foreach ($required as $f) {
        if (empty($input[$f])) {
            return $this->sendResponse("error", "Field '$f' wajib diisi.", null, 400);
        }
    }

    // 🔒 Jika user yang login adalah petugas, paksa role user baru menjadi 'peminjam'
    if ($user['role'] === 'petugas') {
        $input['role'] = 'peminjam';
    }

    // 🔐 Hash password
    $input['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
    $input['password_plain'] = $input['password'];
    unset($input['password']);

    if ($this->model->insertUser($input)) {
        $this->sendResponse("success", "User berhasil ditambahkan.", null, 201);
    } else {
        $this->sendResponse("error", "Gagal menambahkan user.", null, 500);
    }
}

    // ===============================
    // 🔹 REQUEST EDIT (petugas → admin)
    // ===============================
    public function requestEdit()
    {
        $user = AuthMiddleware::requireRole(['petugas'], $this->model);
        $input = json_decode(file_get_contents('php://input'), true);
        $targetUser = $input['target_user'] ?? null;

        if (!$targetUser) {
            return $this->sendResponse("error", "ID user target wajib diisi.", null, 400);
        }

        $target = $this->model->getUserById($targetUser);
        if (!$target || $target['role'] !== 'peminjam') {
            return $this->sendResponse("error", "Petugas hanya bisa ajukan edit user peminjam.", null, 403);
        }

        require_once __DIR__ . '/../utils/Mailer.php';
        Mailer::sendAdminNotification(
            "Pengajuan Edit User",
            "<p style='font-size: 16px;'>Halo Admin,</p>
            <p>Petugas <strong>{$user['nama']}</strong> mengajukan pengeditan data untuk user <strong>{$target['nama']}</strong> (ID: {$target['id_user']}).</p>
            <p>Silakan tinjau pengajuan ini di sistem.</p>
            <br><p>Terima kasih,</p><p>Sistem Manajemen User</p>"
        );

        $this->model->markPendingEdit($targetUser);
        $this->sendResponse("success", "Pengajuan edit telah dikirim ke administrator.");
    }

    // ===============================
    // 🔹 CHANGE ROLE (admin only)
    // ===============================
    public function changeRole()
    {
        $user = AuthMiddleware::requireRole(['administrator'], $this->model);
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['target_user']) || empty($input['new_role'])) {
            return $this->sendResponse("error", "Field target_user dan new_role wajib diisi.", null, 400);
        }

        if ($input['target_user'] == $user['id_user']) {
            return $this->sendResponse("error", "Tidak dapat mengubah role sendiri.", null, 403);
        }

        if (!in_array($input['new_role'], ['administrator', 'petugas', 'peminjam'])) {
            return $this->sendResponse("error", "Role tidak valid.", null, 400);
        }

        if ($this->model->updateUser($input['target_user'], ['role' => $input['new_role']])) {
            $this->sendResponse("success", "Role user berhasil diubah.");
        } else {
            $this->sendResponse("error", "Gagal mengubah role.", null, 500);
        }
    }

    // ===============================
    // 🔹 UPDATE USER
    // ===============================
    public function update()
    {
        $currentUser = $this->getUserFromCookie();
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->sendResponse("error", "Format JSON tidak valid.", null, 400);
        }

        $id_user = $input['id_user'] ?? $currentUser['id_user'];
        unset($input['id_user']);

        if (empty($input)) {
            return $this->sendResponse("error", "Tidak ada data untuk diperbarui.", null, 400);
        }

        // 🔒 Validasi akses
        if ($id_user !== $currentUser['id_user']) {
            $target = $this->model->getUserById($id_user);
            if (!$target) {
                return $this->sendResponse("error", "User target tidak ditemukan.", null, 404);
            }
            if ($currentUser['role'] === 'administrator') {
                if ($id_user == $currentUser['id_user']) {
                    return $this->sendResponse("error", "Administrator tidak dapat mengedit diri sendiri melalui endpoint ini.", null, 403);
                }
            } elseif ($currentUser['role'] === 'petugas') {
                if ($target['role'] !== 'peminjam') {
                    return $this->sendResponse("error", "Petugas hanya bisa edit user peminjam.", null, 403);
                }
            } else {
                return $this->sendResponse("error", "Akses ditolak. Hanya bisa update diri sendiri.", null, 403);
            }
        }

        // 🔐 Password update
        if (isset($input['password'])) {
            $input['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
            $input['password_plain'] = $input['password'];
            unset($input['password']);
        }

        // Role hanya bisa diubah lewat /change-role
        if (isset($input['role'])) {
            return $this->sendResponse("error", "Gunakan endpoint /change-role untuk mengubah role.", null, 403);
        }

        if ($this->model->updateUser($id_user, $input)) {
            $this->sendResponse("success", "User berhasil diperbarui.");
        } else {
            $this->sendResponse("error", "Tidak ada perubahan atau gagal update.", null, 400);
        }
    }

    // ===============================
    // 🔹 DELETE USER
    // ===============================
    public function delete()
    {
        $currentUser = $this->getUserFromCookie();
        $input = json_decode(file_get_contents('php://input'), true);
        $id_user = $input['id_user'] ?? $currentUser['id_user'];

        if ($id_user !== $currentUser['id_user']) {
            if ($currentUser['role'] !== 'administrator') {
                return $this->sendResponse("error", "Hanya administrator yang bisa hapus user lain.", null, 403);
            }
            if ($id_user == $currentUser['id_user']) {
                return $this->sendResponse("error", "Tidak dapat menghapus diri sendiri.", null, 403);
            }
        }

        if ($this->model->deleteUser($id_user)) {
            $this->sendResponse("success", "User berhasil dihapus.");
        } else {
            $this->sendResponse("error", "Gagal menghapus user.", null, 500);
        }
    }
}
