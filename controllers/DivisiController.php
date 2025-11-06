<?php
require_once __DIR__ . '/../models/DivisiModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class DivisiController
{
    private $model;
    public function __construct(PDO $pdo) { $this->model = new DivisiModel($pdo); }

    private function send($status, $msg, $data = null, $code = 200) {
        http_response_code($code);
        echo json_encode(["status"=>$status,"message"=>$msg,"data"=>$data]);
        exit;
    }

    public function getAll() {
        $this->send("success", "Daftar divisi diambil.", $this->model->getAll());
    }

    public function add() {
        AuthMiddleware::requireRole(['administrator']);
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['nama_divisi'])) return $this->send("error", "Nama wajib diisi.", null, 400);
        $this->model->insert($input['nama_divisi']);
        $this->send("success", "Divisi ditambahkan.");
    }

    public function update($id) {
        AuthMiddleware::requireRole(['administrator']);
        $input = json_decode(file_get_contents('php://input'), true);
        $this->model->update($id, $input['nama_divisi']);
        $this->send("success", "Divisi diperbarui.");
    }

    public function delete($id) {
        AuthMiddleware::requireRole(['administrator']);
        $this->model->delete($id);
        $this->send("success", "Divisi dihapus.");
    }
}
