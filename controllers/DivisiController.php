<?php
require_once __DIR__ . '/../models/DivisiModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class DivisiController
{
    private $model;
    private $pdo;
    private $cache;

     public function __construct(PDO $pdo, $cache)
    {
        $this->pdo = $pdo;
        $this->cache = $cache;
        $this->model = new DivisiModel($pdo, $cache);
    }

    private function send($status, $msg, $data = null, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            "status" => $status,
            "message" => $msg,
            "data" => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // GET /divisi
    public function getAll()
    {
        
        $data = $this->model->getAll();
        $this->send("success", "Daftar divisi diambil.", $data);
    }

    // GET /divisi/{id}
    public function getById($id)
    {
        $data = $this->model->getById($id);
        if (!$data) {
            return $this->send("error", "Divisi tidak ditemukan.", null, 404);
        }
        $this->send("success", "Divisi ditemukan.", $data);
    }

    // POST /divisi
    public function add()
    {
        AuthMiddleware::requireRole(['administrator']);
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty(trim($input['nama_divisi']))) {
            return $this->send("error", "Nama divisi wajib diisi.", null, 400);
        }

        $success = $this->model->insert($input['nama_divisi']);
        if ($success) {
            $this->send("success", "Divisi berhasil ditambahkan.");
        } else {
            $this->send("error", "Gagal menambahkan divisi.", null, 500);
        }
    }

    // PUT /divisi/{id}
    public function update($id)
    {
        AuthMiddleware::requireRole(['administrator']);
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty(trim($input['nama_divisi']))) {
            return $this->send("error", "Nama divisi wajib diisi.", null, 400);
        }

        $success = $this->model->update($id, $input['nama_divisi']);
        if ($success) {
            $this->send("success", "Divisi berhasil diperbarui.");
        } else {
            $this->send("error", "Gagal memperbarui divisi atau tidak ditemukan.", null, 500);
        }
    }

    // DELETE /divisi/{id}
    public function delete($id)
    {
        AuthMiddleware::requireRole(['administrator']);
        $success = $this->model->delete($id);
        if ($success) {
            $this->send("success", "Divisi berhasil dihapus.");
        } else {
            $this->send("error", "Gagal menghapus divisi atau tidak ditemukan.", null, 500);
        }
    }
}