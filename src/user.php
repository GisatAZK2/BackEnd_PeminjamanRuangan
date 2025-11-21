<?php
// user.php
header('Content-Type: application/json');

// Fungsi respons standar
function sendResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    $response = ["status" => $status, "message" => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Pastikan koneksi ada
if (!isset($pdo)) {
    sendResponse("error", "Koneksi database tidak tersedia.", null, 500);
}

// Pastikan login
if (!isset($_COOKIE['user_info'])) {
    sendResponse("error", "Akses ditolak! Anda belum login.", null, 401);
}

$user_info = json_decode($_COOKIE['user_info'], true);
if (!$user_info || !isset($user_info['role'], $user_info['id_user'])) {
    sendResponse("error", "Data login tidak valid.", null, 401);
}

$current_role = $user_info['role'];
$current_id   = $user_info['id_user'];

$method = $_SERVER['REQUEST_METHOD'];
$id_user = $_GET['id_user'] ?? null;
$action = $_GET['action'] ?? '';

// ==============================
// 🔹 FORCE LOGOUT (Admin only)
// ==============================
if ($method === 'POST' && $action === 'force_logout') {
    if ($current_role !== 'administrator') {
        sendResponse("error", "Hanya administrator yang bisa logout paksa.", null, 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $target_id = $input['id_user'] ?? '';

    if (empty($target_id)) {
        sendResponse("error", "ID user wajib disertakan.", null, 400);
    }

    $stmt = $pdo->prepare("UPDATE table_user SET is_logged_in = 0 WHERE id_user = ?");
    if ($stmt->execute([$target_id])) {
        sendResponse("success", "User berhasil di-logout paksa.", null, 200);
    } else {
        sendResponse("error", "Gagal logout paksa.", null, 500);
    }
}

// ==============================
// 🔹 GET USERS
// ==============================
if ($method === 'GET') {
    // Detail user login sendiri
    if ($action === 'detail_user') {
        $stmt = $pdo->prepare("SELECT * FROM table_user WHERE id_user = ?");
        $stmt->execute([$current_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) sendResponse("error", "Data user tidak ditemukan.", null, 404);
        sendResponse("success", "Data user ditemukan.", $data, 200);
    }

    // Cek izin edit role
    if ($action === 'can_edit_role') {
        $can_edit = ($current_role === 'administrator');
        sendResponse("success", "Izin edit role dicek.", ["can_edit_role" => $can_edit], 200);
    }

    // Ambil data user
    if ($id_user) {
        if ($current_role === 'administrator') {
            $stmt = $pdo->prepare("SELECT * FROM table_user WHERE id_user = ?");
            $stmt->execute([$id_user]);
        } elseif ($current_role === 'petugas') {
            $stmt = $pdo->prepare("SELECT * FROM table_user WHERE id_user = ? AND role = 'peminjam'");
            $stmt->execute([$id_user]);
        } else {
            if ($id_user != $current_id) {
                sendResponse("error", "Akses ditolak! Anda hanya dapat melihat data milik Anda sendiri.", null, 403);
            }
            $stmt = $pdo->prepare("SELECT * FROM table_user WHERE id_user = ?");
            $stmt->execute([$id_user]);
        }
    } else {
        if ($current_role === 'administrator') {
            $stmt = $pdo->query("SELECT * FROM table_user");
        } elseif ($current_role === 'petugas') {
            $stmt = $pdo->query("SELECT * FROM table_user WHERE role = 'peminjam'");
        } else {
            sendResponse("error", "Akses ditolak! Peminjam tidak dapat melihat semua user.", null, 403);
        }
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse("success", $id_user ? "User ditemukan." : "Daftar user berhasil diambil.", $data, 200);
}

// ==============================
// 🔹 INSERT USER
// ==============================
if ($method === 'POST' && $action !== 'force_logout') {
    if (!in_array($current_role, ['administrator', 'petugas'])) {
        sendResponse("error", "Akses ditolak! Anda tidak memiliki izin menambah user.", null, 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse("error", "Format JSON tidak valid.", null, 400);
    }

    $required = ['username', 'nama', 'email', 'password'];
    $missing = array_filter($required, fn($f) => empty($input[$f]));
    if (!empty($missing)) {
        sendResponse("error", "Field wajib: " . implode(', ', $missing), null, 400);
    }

    $username = trim($input['username']);
    $nama = trim($input['nama']);
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    $nomor_telepon = $input['nomor_telepon'] ?? '';
    $password = password_hash($input['password'], PASSWORD_DEFAULT);
    $id_divisi = $input['id_divisi'] ?? null;
    $role = $input['role'] ?? 'peminjam';

    if (!$email) sendResponse("error", "Email tidak valid.", null, 400);
    if ($current_role === 'petugas' && $role !== 'peminjam') {
        sendResponse("error", "Petugas hanya boleh menambah user dengan role 'peminjam'.", null, 403);
    }

    // Cek username/email unik
    $check = $pdo->prepare("SELECT id_user FROM table_user WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);
    if ($check->fetch()) {
        sendResponse("error", "Username atau email sudah digunakan.", null, 409);
    }

    $stmt = $pdo->prepare("INSERT INTO table_user (username, nama, nomor_telepon, email, password, id_divisi, role, is_logged_in) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    $success = $stmt->execute([$username, $nama, $nomor_telepon, $email, $password, $id_divisi, $role]);

    if ($success) sendResponse("success", "User berhasil ditambahkan.", null, 201);
    sendResponse("error", "Gagal menambahkan user.", null, 500);
}

// ==============================
// 🔹 UPDATE USER
// ==============================
if ($method === 'PUT') {
    if (!in_array($current_role, ['administrator', 'petugas'])) {
        sendResponse("error", "Akses ditolak! Anda tidak memiliki izin mengedit user.", null, 403);
    }

    parse_str(file_get_contents("php://input"), $_PUT);
    $target_id = $_PUT['id_user'] ?? '';
    if (empty($target_id)) sendResponse("error", "ID user wajib disertakan.", null, 400);

    // Ambil data target user
    $check = $pdo->prepare("SELECT role, is_logged_in FROM table_user WHERE id_user = ?");
    $check->execute([$target_id]);
    $target_user = $check->fetch(PDO::FETCH_ASSOC);
    if (!$target_user) sendResponse("error", "User tidak ditemukan.", null, 404);

    // Petugas restrictions
    if ($current_role === 'petugas') {
        if ($target_user['role'] !== 'peminjam') {
            sendResponse("error", "Petugas hanya boleh mengedit user peminjam.", null, 403);
        }
        if ($target_user['is_logged_in'] == 1) {
            sendResponse("error", "User sedang login. Tidak dapat diedit.", null, 403);
        }
        if (isset($_PUT['role'])) {
            sendResponse("error", "Petugas tidak boleh mengubah role.", null, 403);
        }
    }

    // Admin tidak boleh turunkan jabatan sendiri
    if ($current_role === 'administrator') {
        $new_role = $_PUT['role'] ?? $target_user['role'];
        if ($current_id === $target_id && $new_role !== 'administrator') {
            sendResponse("error", "Administrator tidak boleh menurunkan jabatannya sendiri.", null, 403);
        }
    }

    // Handle update fields
    $fields = [];
    $params = [];
    if (!empty($_PUT['nama'])) { $fields[] = "nama = ?"; $params[] = trim($_PUT['nama']); }
    if (!empty($_PUT['email'])) { 
        $email = filter_var($_PUT['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) sendResponse("error", "Email tidak valid.", null, 400);
        $fields[] = "email = ?"; $params[] = $email;
    }
    if (!empty($_PUT['nomor_telepon'])) { $fields[] = "nomor_telepon = ?"; $params[] = $_PUT['nomor_telepon']; }
    if (isset($_PUT['role']) && $current_role === 'administrator') { $fields[] = "role = ?"; $params[] = $_PUT['role']; }
    if (!empty($_PUT['id_divisi'])) { $fields[] = "id_divisi = ?"; $params[] = $_PUT['id_divisi']; }

    if (empty($fields)) sendResponse("error", "Tidak ada data yang diubah.", null, 400);

    $sql = "UPDATE table_user SET " . implode(", ", $fields) . " WHERE id_user = ?";
    $params[] = $target_id;

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        sendResponse("success", "User berhasil diperbarui.", null, 200);
    } else {
        sendResponse("error", "Gagal update user.", null, 500);
    }
}

// ==============================
// 🔹 DELETE USER
// ==============================
if ($method === 'DELETE') {
    if (!in_array($current_role, ['administrator', 'petugas'])) {
        sendResponse("error", "Akses ditolak! Anda tidak memiliki izin menghapus user.", null, 403);
    }

    parse_str(file_get_contents("php://input"), $_DELETE);
    $target_id = $_DELETE['id_user'] ?? '';
    if (empty($target_id)) sendResponse("error", "ID user wajib disertakan.", null, 400);

    $check = $pdo->prepare("SELECT role, is_logged_in FROM table_user WHERE id_user = ?");
    $check->execute([$target_id]);
    $target = $check->fetch(PDO::FETCH_ASSOC);
    if (!$target) sendResponse("error", "User tidak ditemukan.", null, 404);

    if ($current_role === 'petugas') {
        if ($target['role'] !== 'peminjam') {
            sendResponse("error", "Petugas hanya boleh menghapus user peminjam.", null, 403);
        }
        if ($target['is_logged_in'] == 1) {
            sendResponse("error", "User sedang login. Tidak dapat dihapus.", null, 403);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM table_user WHERE id_user = ?");
    if ($stmt->execute([$target_id])) {
        sendResponse("success", "User berhasil dihapus.", null, 200);
    } else {
        sendResponse("error", "Gagal menghapus user.", null, 500);
    }
}

// Metode tidak diizinkan
sendResponse("error", "Metode HTTP tidak diizinkan.", null, 405);
?>
