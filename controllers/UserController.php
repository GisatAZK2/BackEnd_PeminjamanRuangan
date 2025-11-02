<?php
require_once __DIR__ . '/../models/UserModel.php';

class UserController
{
    private $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new UserModel($pdo);
    }

    private function sendResponse($status, $message, $data = null, $httpCode = 200)
    {
        http_response_code($httpCode);
        $res = ["status" => $status, "message" => $message];
        if ($data !== null) $res['data'] = $data;
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =====================
    // 🔹 GET ALL USERS
    // =====================
    public function getAll()
    {
        $users = $this->model->getAllUsers();
        $this->sendResponse("success", "Daftar user berhasil diambil.", $users);
    }

    // =====================
    // 🔹 GET DETAIL USER
    // =====================
    public function getDetail()
    {
        $id = $_GET['id_user'] ?? null;
        if (!$id) {
            return $this->sendResponse("error", "ID user wajib disertakan.", null, 400);
        }

        $user = $this->model->getUserById($id);
        if (!$user) {
            return $this->sendResponse("error", "User tidak ditemukan.", null, 404);
        }

        $this->sendResponse("success", "Data user ditemukan.", $user);
    }

    // =====================
    // 🔹 ADD USER
    // =====================
    public function add()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->sendResponse("error", "Format JSON tidak valid.", null, 400);
        }

        $required = ['username', 'nama', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                return $this->sendResponse("error", "Field '$field' wajib diisi.", null, 400);
            }
        }

        $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);

        if ($this->model->insertUser($input)) {
            $this->sendResponse("success", "User berhasil ditambahkan.", null, 201);
        } else {
            $this->sendResponse("error", "Gagal menambahkan user.", null, 500);
        }
    }

    // =====================
    // 🔹 UPDATE USER
    // =====================
    public function update()
    {
        parse_str(file_get_contents('php://input'), $_PUT);
        $id_user = $_PUT['id_user'] ?? null;
        if (!$id_user) {
            return $this->sendResponse("error", "ID user wajib disertakan.", null, 400);
        }

        unset($_PUT['id_user']);
        if ($this->model->updateUser($id_user, $_PUT)) {
            $this->sendResponse("success", "User berhasil diperbarui.");
        } else {
            $this->sendResponse("error", "Tidak ada perubahan atau gagal update.", null, 400);
        }
    }

    // =====================
    // 🔹 DELETE USER
    // =====================
    public function delete()
    {
        parse_str(file_get_contents('php://input'), $_DELETE);
        $id_user = $_DELETE['id_user'] ?? null;
        if (!$id_user) {
            return $this->sendResponse("error", "ID user wajib disertakan.", null, 400);
        }

        if ($this->model->deleteUser($id_user)) {
            $this->sendResponse("success", "User berhasil dihapus.");
        } else {
            $this->sendResponse("error", "Gagal menghapus user.", null, 500);
        }
    }
}
