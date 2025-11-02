<?php
require_once __DIR__ . '/../models/RuanganModel.php';

class RuanganController
{
    private $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new RuanganModel($pdo);
    }

    // ===== Helper =====
    private function getUser()
    {
        if (!isset($_COOKIE['user_info'])) return null;
        return json_decode($_COOKIE['user_info'], true);
    }

    private function sendResponse($status, $message, $data = null, $code = 200)
    {
        http_response_code($code);
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    // ===== ADMIN: Tambah Ruangan =====
    public function addRoom()
    {
        $user = $this->getUser();
        if (!$user || $user['role'] !== 'administrator')
            return $this->sendResponse('error', 'Hanya administrator yang dapat menambah ruangan.', null, 403);

        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['ruangan_name'] ?? '');

        if (empty($name))
            return $this->sendResponse('error', 'Nama ruangan wajib diisi.', null, 400);

        $this->model->addRoom($name);
        return $this->sendResponse('success', 'Ruangan berhasil ditambahkan.');
    }

    // ===== PEMINJAM: Ajukan Pinjaman =====
    public function createBooking()
    {
        $user = $this->getUser();
        if (!$user || $user['role'] !== 'peminjam')
            return $this->sendResponse('error', 'Hanya peminjam yang bisa mengajukan.', null, 403);

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data)
            return $this->sendResponse('error', 'Data pengajuan tidak ditemukan.', null, 400);

        // Tambahkan user_id dari cookie
        $data['user_id'] = $user['id_user'];

        // Validasi input wajib
        $required = ['ruangan_id', 'kegiatan', 'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->sendResponse('error', "Field '{$field}' wajib diisi.", null, 400);
            }
        }

        // Cek ketersediaan ruangan
        $available = $this->model->isRoomAvailable(
            $data['ruangan_id'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['jam_mulai'],
            $data['jam_selesai']
        );

        if (!$available) {
            return $this->sendResponse('error', 'Ruangan tidak tersedia di jadwal tersebut.', null, 409);
        }

        // Simpan data booking
        $this->model->createBooking($data);
        $this->sendResponse('success', 'Pengajuan ruangan berhasil dibuat. Menunggu persetujuan petugas.');
    }

    // ===== PETUGAS: Setujui / Tolak Pengajuan =====
    public function updateStatus($id)
    {
        $user = $this->getUser();
        if (!$user || $user['role'] !== 'petugas')
            return $this->sendResponse('error', 'Hanya petugas yang bisa menyetujui atau menolak.', null, 403);

        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'] ?? 'pending';
        $keterangan = $data['keterangan'] ?? null;

        if (!in_array($status, ['disetujui', 'ditolak']))
            return $this->sendResponse('error', 'Status tidak valid.', null, 400);

        $this->model->updateStatus($id, $status, $keterangan);
        $this->sendResponse('success', "Pengajuan telah diperbarui menjadi {$status}.");
    }

    // ===== PEMINJAM: Tandai Rapat Selesai + Upload Notulen =====
    public function markFinished($pinjam_id)
    {
        $user = $this->getUser();
        if (!$user || $user['role'] !== 'peminjam')
            return $this->sendResponse('error', 'Hanya peminjam yang dapat menyelesaikan rapat.', null, 403);

        $booking = $this->model->getBookingById($pinjam_id);
        if (!$booking)
            return $this->sendResponse('error', 'Data pinjaman tidak ditemukan.', null, 404);

        if ($booking['status'] !== 'disetujui')
            return $this->sendResponse('error', 'Hanya rapat dengan status disetujui yang dapat diselesaikan.', null, 400);

        if (!isset($_FILES['file']))
            return $this->sendResponse('error', 'File notulen wajib diunggah.', null, 400);

        // Simpan file ke DB (bukan lokal)
        $this->model->uploadNotulen($pinjam_id, $_FILES['file']);

        // Update status
        $this->model->markAsFinished($pinjam_id);

        $this->sendResponse('success', 'Rapat telah ditandai selesai dan notulen berhasil diunggah.');
    }

    // ===== SISTEM: Auto Tandai Selesai =====
    public function autoMarkFinished()
    {
        $expired = $this->model->getExpiredBookings();
        foreach ($expired as $booking) {
            $this->model->updateStatus($booking['id'], 'selesai', 'Otomatis diselesaikan oleh sistem.');
            // TODO: kirim notifikasi email
        }

        $this->sendResponse('success', count($expired) . ' pinjaman otomatis diselesaikan.');
    }
}
