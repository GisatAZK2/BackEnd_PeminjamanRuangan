<?php
require_once __DIR__ . '/../models/StatistikModel.php';

class StatistikController
{
    private $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new StatistikModel($pdo);
    }

    public function index()
    {
        header('Content-Type: application/json');

        $user = $this->getUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            exit;
        }

        $role     = $user['role'] ?? null;
        $id_user  = $user['id_user'] ?? null;

        if (!$role || !$id_user) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Data user tidak lengkap"]);
            exit;
        }

        // ============================================================
        // DATA GLOBAL — Selalu ada, tapi today_bookings disesuaikan per role
        // ============================================================
        $global = [
            'total_ruangan'    => $this->model->countRuangan(),
            'today_bookings'   => ($role === 'peminjam')
                ? $this->model->countTodayBookingsByUser($id_user)   // hanya milik dia
                : $this->model->countTodayBookings(),               // semua user (admin & petugas)
            'ongoing'          => $this->model->countOngoing(),
            'finished_today'   => $this->model->countFinishedToday(),
        ];

        switch ($role) {
            case 'administrator':
                $data = array_merge([
                    'total_user'          => $this->model->countUsers(),
                    'total_divisi'        => $this->model->countDivisi(),
                    'total_ruangan'       => $this->model->countRuangan(),
                    'total_peminjaman'    => $this->model->countAllPeminjaman(),
                    'total_petugas'       => $this->model->countUsersByRole('petugas'),
                    'total_peminjam'      => $this->model->countUsersByRole('peminjam'),
                    'peminjaman_per_hari' => $this->model->countPeminjamanPerHari(),
                    'peminjaman_per_status' => $this->model->countPeminjamanPerStatus(),
                ], $global);
                break;

            case 'petugas':
                $data = array_merge([
                    'total_peminjaman'      => $this->model->countAllPeminjaman(),
                    'peminjaman_per_status' => $this->model->countPeminjamanPerStatus(),
                    'peminjaman_per_hari'   => $this->model->countPeminjamanPerHari(),
                    'total_peminjam'        => $this->model->countUsersByRole('peminjam'),
                ], $global);
                break;

            case 'peminjam':
                $data = array_merge([
                    'total_pengajuan'   => $this->model->countUserPeminjaman($id_user),
                    'total_disetujui'   => $this->model->countUserPeminjamanByStatus($id_user, 'disetujui'),
                    'total_ditolak'     => $this->model->countUserPeminjamanByStatus($id_user, 'ditolak'),
                ], $global);
                break;

            default:
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Role tidak dikenali"]);
                exit;
        }

        echo json_encode([
            "status" => "success",
            "role"   => $role,
            "data"   => $data
        ]);
        exit;
    }

    private function getUser()
    {
        if (!isset($_COOKIE['user_info'])) {
            return null;
        }

        $decoded = urldecode($_COOKIE['user_info']);
        $user    = json_decode($decoded, true);

        return is_array($user) ? $user : null;
    }
}