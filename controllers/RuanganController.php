<?php
require_once __DIR__ . '/../models/RuanganModel.php';
// Jika Anda punya middleware JWT, pastikan file ini di-include/auto-load
// require_once __DIR__ . '/../middleware/JwtAuthMiddleware.php';

class RuanganController
{
    private $model;
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->model = new RuanganModel($pdo);
    }

    private function getUser()
    {
        // 1️⃣ Cek user dari cookie biasa (misalnya diset frontend)
        if (isset($_COOKIE['user_info'])) {
            $user = json_decode($_COOKIE['user_info'], true);
            if (is_array($user) && isset($user['id_user'])) {
                return $user;
            }
        }

        // 2️⃣ Cek user dari JWT (via middleware) — jika Anda punya middleware implementasinya
        if (class_exists('JwtAuthMiddleware') && method_exists('JwtAuthMiddleware', 'getUser')) {
            $jwtUser = JwtAuthMiddleware::getUser();
            if ($jwtUser && isset($jwtUser['id_user'])) {
                return $jwtUser;
            }
        }

        // 3️⃣ Jika tidak ada dua-duanya, kembalikan null
        return null;
    }

    private function sendResponse($status, $message, $data = null, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===============================
    // ADMIN: Tambah Ruangan
    // ===============================
    public function addRoom()
    {
        $user = $this->getUser();
        if (!$user || ($user['role'] ?? '') !== 'administrator') {
            return $this->sendResponse('error', 'Hanya administrator yang dapat menambah ruangan.', null, 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['ruangan_name'] ?? '');

        if (empty($name)) {
            return $this->sendResponse('error', 'Nama ruangan wajib diisi.', null, 400);
        }

        $this->model->addRoom($name);
        return $this->sendResponse('success', 'Ruangan berhasil ditambahkan.');
    }

    // ===============================
    // PEMINJAM: Ajukan Pinjaman
    // ===============================
    public function createBooking()
    {
        $user = $this->getUser();
        if (!$user || ($user['role'] ?? '') !== 'peminjam') {
            return $this->sendResponse('error', 'Hanya peminjam yang bisa mengajukan.', null, 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return $this->sendResponse('error', 'Data pengajuan tidak ditemukan.', null, 400);
        }

        $data['user_id'] = $user['id_user'];
        $required = ['ruangan_id', 'kegiatan', 'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->sendResponse('error', "Field '{$field}' wajib diisi.", null, 400);
            }
        }

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

        $this->model->createBooking($data);
        $this->sendResponse('success', 'Pengajuan ruangan berhasil dibuat. Menunggu persetujuan petugas.');
    }

    // ===============================
    // PETUGAS: Update Status
    // ===============================
    public function updateStatus($id)
    {
        $user = $this->getUser();
        if (!$user || ($user['role'] ?? '') !== 'petugas')
            return $this->sendResponse('error', 'Hanya petugas yang bisa menyetujui atau menolak.', null, 403);

        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'] ?? 'pending';
        $keterangan = $data['keterangan'] ?? null;

        if (!in_array($status, ['disetujui', 'ditolak']))
            return $this->sendResponse('error', 'Status tidak valid.', null, 400);

        $this->model->updateStatus($id, $status, $keterangan);
        $this->sendResponse('success', "Pengajuan telah diperbarui menjadi {$status}.");
    }

    // ===============================
    // PEMINJAM: Tandai Rapat Selesai + Upload Notulen
    // ===============================
    public function markFinished($pinjam_id)
    {
        $user = $this->getUser();
        if (!$user || ($user['role'] ?? '') !== 'peminjam')
            return $this->sendResponse('error', 'Hanya peminjam yang dapat menyelesaikan rapat.', null, 403);

        $booking = $this->model->getBookingById($pinjam_id);
        if (!$booking)
            return $this->sendResponse('error', 'Data pinjaman tidak ditemukan.', null, 404);

        if (($booking['status'] ?? '') !== 'disetujui')
            return $this->sendResponse('error', 'Hanya rapat dengan status disetujui yang dapat diselesaikan.', null, 400);

        // Pastikan ada file; bisa single atau multiple input name 'file' atau 'files[]'
        if (empty($_FILES)) {
            return $this->sendResponse('error', 'File notulen wajib diunggah.', null, 400);
        }

        // Cari parameter file. Jika frontend mengirim dengan name 'file' atau 'files'
        $fileField = null;
        if (isset($_FILES['file'])) $fileField = $_FILES['file'];
        elseif (isset($_FILES['files'])) $fileField = $_FILES['files'];
        else {
            // ambil first element di $_FILES
            $first = reset($_FILES);
            $fileField = $first;
        }

        $ok = $this->model->uploadNotulenMulti($pinjam_id, $fileField);
        if (!$ok) {
            return $this->sendResponse('error', 'Gagal mengunggah file notulen. Periksa konfigurasi server/permission.', null, 500);
        }

        $this->model->markAsFinished($pinjam_id);
        $this->sendResponse('success', 'Rapat selesai & semua file notulen berhasil diunggah.');
    }

    // ===============================
    // GET HISTORY + NOTULEN (FIXED & COMPLETE)
    // ===============================
    public function getBookingHistory()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->sendResponse('error', 'Unauthorized.', null, 401);
        }

        $filter = $_GET['filter'] ?? 'semua';
        $allowed = ['semua', 'pending', 'disetujui', 'ditolak', 'selesai'];
        if (!in_array($filter, $allowed)) {
            $filter = 'semua';
        }

        $data = $this->model->getBookingHistory($user, $filter);
        $this->sendResponse('success', 'Histori peminjaman berhasil diambil.', $data);
    }

    // ===============================
    // DOWNLOAD NOTULEN
    // ===============================
    public function downloadNotulen($file_id)
    {
        $user = $this->getUser();
        if (!$user) {
            http_response_code(401);
            exit('Unauthorized');
        }

        $index = isset($_GET['index']) ? intval($_GET['index']) : 0;
        $file = $this->model->getNotulenFileByIndex($file_id, $index);
        if (!$file) {
            http_response_code(404);
            echo "File tidak ditemukan.";
            exit;
        }

        // Optional: cek akses (hanya pemilik atau admin/petugas)
        $booking = $this->model->getBookingById($file['pinjam_id']);
        if (($user['role'] ?? '') === 'peminjam' && $booking['user_id'] != $user['id_user']) {
            http_response_code(403);
            echo "Akses ditolak.";
            exit;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $file['meta']['type']);
        header('Content-Disposition: attachment; filename="' . $file['meta']['name'] . '"');
        header('Content-Length: ' . strlen($file['data']));
        echo $file['data'];
        exit;
    }

    // ===============================
    // AUTO-TANDAI SELESAI
    // ===============================
    public function autoMarkFinished()
    {
        $expired = $this->model->getExpiredBookings();
        foreach ($expired as $booking) {
            $this->model->updateStatus($booking['id'], 'selesai', 'Otomatis diselesaikan oleh sistem.');
        }

        $this->sendResponse('success', count($expired) . ' pinjaman otomatis diselesaikan.');
    }
}
