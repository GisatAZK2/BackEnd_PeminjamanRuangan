<?php
require_once __DIR__ . '/../models/StatistikModel.php';

class StatistikController
{
    private $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new StatistikModel($pdo);
    }

    // ============================================================
    // 🔹 Statistik utama berdasarkan role user
    // ============================================================
    public function index()
    {
        header('Content-Type: application/json');

        // Ambil user dari session / cookie
        $user = $this->getUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            return;
        }

        $role = $user['role'];
        $id_user = $user['id_user'];

        switch ($role) {
            case 'administrator':
                $data = [
                    'total_user' => $this->model->countUsers(),
                    'total_divisi' => $this->model->countDivisi(),
                    'total_ruangan' => $this->model->countRuangan(),
                    'total_peminjaman' => $this->model->countAllPeminjaman(),
                    'total_petugas' => $this->model->countUsersByRole('petugas'),
                    'total_peminjam' => $this->model->countUsersByRole('peminjam'),
                    'peminjaman_per_hari' => $this->model->countPeminjamanPerHari(),
                    'peminjaman_per_status' => $this->model->countPeminjamanPerStatus(),
                ];
                break;

            case 'petugas':
                $data = [
                    'total_peminjaman' => $this->model->countAllPeminjaman(),
                    'peminjaman_per_status' => $this->model->countPeminjamanPerStatus(),
                    'peminjaman_per_hari' => $this->model->countPeminjamanPerHari(),
                    'total_peminjam' => $this->model->countUsersByRole('peminjam'),
                    'total_ruangan' => $this->model->countRuangan(),
                ];
                break;

            case 'peminjam':
                $data = [
                    'total_pengajuan' => $this->model->countUserPeminjaman($id_user),
                    'total_disetujui' => $this->model->countUserPeminjamanByStatus($id_user, 'disetujui'),
                    'total_ditolak' => $this->model->countUserPeminjamanByStatus($id_user, 'ditolak'),
                ];
                break;

            default:
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Role tidak dikenali"]);
                return;
        }

        echo json_encode([
            "status" => "success",
            "role" => $role,
            "data" => $data
        ]);
    }

    // ============================================================
    // 🔹 Statistik jumlah anggota per divisi
    // ============================================================
    public function statistikDivisi()
    {
        header('Content-Type: application/json');
        $user = $this->getUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            return;
        }

        // Hanya administrator yang boleh
        if ($user['role'] !== 'administrator') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Akses ditolak"]);
            return;
        }

        $data = $this->model->countUsersPerDivisi();
        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    }

    // ============================================================
    // 🔹 Helper: ambil user dari cookie (misal: user_info)
    // ============================================================
    private function getUser()
    {
        if (!isset($_COOKIE['user_info'])) return null;
        $decoded = urldecode($_COOKIE['user_info']);
        return json_decode($decoded, true);
    }
}
