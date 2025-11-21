<?php
require_once __DIR__ . '/../models/RuanganModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
class RuanganController
{
    private $model;
    private $pdo;
    private $cache;
    public function __construct(PDO $pdo, $cache)
    {
        $this->pdo = $pdo;
        $this->cache = $cache;
        $this->model = new RuanganModel($pdo, $cache);
    }
    private function getUser()
    {
        if (isset($_COOKIE['user_info'])) {
            $user = json_decode($_COOKIE['user_info'], true);
            if (is_array($user) && isset($user['id_user'])) return $user;
        }
        if (class_exists('JwtAuthMiddleware') && method_exists('JwtAuthMiddleware', 'getUser')) {
            $jwtUser = JwtAuthMiddleware::getUser();
            if ($jwtUser && isset($jwtUser['id_user'])) return $jwtUser;
        }
        return null;
    }
    private function sendResponse($status, $message, $data = null, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    // 🟦 Tambahkan ruangan
    public function addRoom()
    {
        $user = AuthMiddleware::requireRole(['administrator']);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['ruangan_name']) || empty(trim($input['ruangan_name']))) {
            return $this->sendResponse('error', 'Nama ruangan wajib diisi.', null, 400);
        }
        $ruangan_name = trim($input['ruangan_name']);
        try {
            $success = $this->model->addRoom($ruangan_name);
            if ($success) {
                return $this->sendResponse('success', 'Ruangan berhasil ditambahkan.', ['ruangan_name' => $ruangan_name]);
            } else {
                return $this->sendResponse('error', 'Gagal menambahkan ruangan.', null, 500);
            }
        } catch (PDOException $e) {
            return $this->sendResponse('error', 'Database error: ' . $e->getMessage(), null, 500);
        }
    }
    // 🟩 GET /api/ruangan
    public function getroomAll()
    {
        try {
            $data = $this->model->getAllRooms();
            $this->sendResponse('success', 'Daftar ruangan diambil.', $data);
        } catch (PDOException $e) {
            $this->sendResponse('error', 'Database error: ' . $e->getMessage(), null, 500);
        }
    }
    // 🟦 GET /api/ruangan/{id}
    public function getroomById($id)
    {
        try {
            $data = $this->model->getroomById($id);
            if ($data) {
                $this->sendResponse('success', 'Data ruangan ditemukan.', $data);
            } else {
                $this->sendResponse('error', 'Ruangan tidak ditemukan.', null, 404);
            }
        } catch (PDOException $e) {
            $this->sendResponse('error', 'Database error: ' . $e->getMessage(), null, 500);
        }
    }
    // 🟨 PUT /api/ruangan/{id}
    public function updateRoom($id)
    {
        $user = AuthMiddleware::requireRole(['administrator']);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['ruangan_name']) || empty(trim($input['ruangan_name']))) {
            return $this->sendResponse('error', 'Nama ruangan wajib diisi.', null, 400);
        }
        $ruangan_name = trim($input['ruangan_name']);
        try {
            $success = $this->model->updateRoom($id, $ruangan_name);
            if ($success) {
                $this->sendResponse('success', 'Ruangan berhasil diperbarui.', ['id' => $id, 'ruangan_name' => $ruangan_name]);
            } else {
                $this->sendResponse('error', 'Gagal memperbarui ruangan.', null, 500);
            }
        } catch (PDOException $e) {
            $this->sendResponse('error', 'Database error: ' . $e->getMessage(), null, 500);
        }
    }
    // 🟥 DELETE /api/ruangan/{id}
    public function deleteRoom($id)
    {
        $user = AuthMiddleware::requireRole(['administrator']);
        try {
            $success = $this->model->deleteRoom($id);
            if ($success) {
                $this->sendResponse('success', 'Ruangan berhasil dihapus.', ['id' => $id]);
            } else {
                $this->sendResponse('error', 'Gagal menghapus ruangan.', null, 500);
            }
        } catch (PDOException $e) {
            $this->sendResponse('error', 'Database error: ' . $e->getMessage(), null, 500);
        }
    }
    // create booking with transaction + lock
    public function createBooking(){
        $user = $this->getUser();
        if (!$user || ($user['role'] ?? '') !== 'peminjam') {
            return $this->sendResponse('error', 'Hanya peminjam yang bisa mengajukan.', null, 403);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) return $this->sendResponse('error', 'Data pengajuan tidak ditemukan.', null, 400);
        $required = ['ruangan_id','kegiatan','tanggal_mulai','tanggal_selesai','jam_mulai','jam_selesai'];
        foreach ($required as $f) if (empty($data[$f])) return $this->sendResponse('error', "Field {$f} wajib diisi.", null, 400);
        $data['user_id'] = $user['id_user'];
        try {
            $this->pdo->beginTransaction();
            $available = $this->model->isRoomAvailableForUpdate(
                $data['ruangan_id'],
                $data['tanggal_mulai'],
                $data['tanggal_selesai'],
                $data['jam_mulai'],
                $data['jam_selesai']
            );
            if (!$available) {
                $this->pdo->rollBack();
                return $this->sendResponse('error','Ruangan tidak tersedia di jadwal tersebut (konflik).', null, 409);
            }
            $ok = $this->model->createBooking($data);
            if (!$ok) {
                $this->pdo->rollBack();
                return $this->sendResponse('error','Gagal simpan booking.', null, 500);
            }
            $this->pdo->commit();
            return $this->sendResponse('success','Pengajuan ruangan berhasil dibuat. Menunggu persetujuan petugas.');
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('createBooking error: '.$e->getMessage());
            return $this->sendResponse('error','Terjadi kesalahan server.', null, 500);
        }
    }
    public function updateStatus($id)
    {
        $user = $this->getUser();
        if (!$user || ($user['role'] ?? '') !== 'petugas') {
            return $this->sendResponse('error','Hanya petugas yang bisa menyetujui atau menolak.', null, 403);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'] ?? '';
        $keterangan = $data['keterangan'] ?? null;
        if (!in_array($status, ['disetujui','ditolak'])) {
            return $this->sendResponse('error','Status tidak valid.', null, 400);
        }
        try {
            $this->pdo->beginTransaction();
            $booking = $this->model->getBookingByIdForUpdate($id);
            if (!$booking) {
                $this->pdo->rollBack();
                return $this->sendResponse('error','Booking tidak ditemukan.', null, 404);
            }
            if (in_array($booking['status'], ['disetujui','ditolak'])) {
                $this->pdo->rollBack();
                return $this->sendResponse('error',"Booking sudah berstatus {$booking['status']}.", null, 409);
            }
            $this->model->updateStatus($id, $status, $keterangan);
            $this->pdo->commit();
            return $this->sendResponse('success',"Pengajuan telah diperbarui menjadi {$status}.");
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('updateStatus error: '.$e->getMessage());
            return $this->sendResponse('error','Terjadi kesalahan server.', null, 500);
        }
    }
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
            // 🔹 File menjadi opsional
            if (!empty($_FILES)) {
                $fileField = null;
                if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
                    $fileField = $_FILES['files'];
                } elseif (isset($_FILES['file'])) {
                    $fileField = $_FILES['file'];
                } else {
                    $fileField = reset($_FILES);
                }
                $ok = $this->model->uploadNotulenMulti($pinjam_id, $fileField);
                if (!$ok)
                    return $this->sendResponse('error', 'Gagal mengunggah file. Pastikan ukuran < 16MB.', null, 500);
            }
            $this->model->markAsFinished($pinjam_id);
            $this->sendResponse('success', 'Rapat selesai' . (!empty($_FILES) ? ' & file notulen berhasil diunggah.' : '.'));
        }
    public function getBookingHistory()
{
    $user = $this->getUser();
    if (!$user) return $this->sendResponse('error', 'Unauthorized', null, 401);

    $filter = $_GET['filter'] ?? 'semua';
    $allowed = ['semua', 'pending', 'disetujui', 'ditolak', 'selesai'];
    if (!in_array($filter, $allowed)) $filter = 'semua';

    // Tambahkan user_id jika peminjam
    if ($user['role'] === 'peminjam') {
        $_GET['user_id'] = $user['id_user']; // opsional, tapi aman
    }

    $data = $this->model->getBookingHistory($user, $filter);
    return $this->sendResponse('success', 'Histori peminjaman berhasil diambil.', $data);
}
    public function downloadNotulen($file_id)
    {
        $user = $this->getUser();
        if (!$user) return $this->sendResponse('error','Unauthorized', null, 401);
        $file = $this->model->getNotulenFileById($file_id);
        if (!$file) return $this->sendResponse('error','File tidak ditemukan.', null, 404);
        $booking = $this->model->getBookingById($file['pinjam_id']);
        if (($user['role'] ?? '') === 'peminjam' && $booking['user_id'] != $user['id_user']) {
            return $this->sendResponse('error','Akses ditolak.', null, 403);
        }
        $payload = [
            'id' => $file['id'],
            'name' => $file['file_name'],
            'type' => $file['file_type'],
            'size' => (int)$file['file_size'],
            'base64' => $file['data_base64']
        ];
        return $this->sendResponse('success','File ditemukan.', $payload);
    }
    public function getRoomAvailability()
    {
        $ruangan_id = isset($_GET['ruangan_id']) ? intval($_GET['ruangan_id']) : 0;
        if (!$ruangan_id) return $this->sendResponse('error','ruangan_id wajib.', null, 400);
        $data = $this->model->getListBookingsByRoom($ruangan_id);
        return $this->sendResponse('success','Availabilities fetched.', $data);
    }
    public function autoMarkFinished()
    {
        $expired = $this->model->getExpiredBookings();
        foreach ($expired as $booking) {
            $this->model->updateStatus($booking['id'], 'selesai', 'Otomatis diselesaikan oleh sistem.');
        }
        $this->sendResponse('success', count($expired) . ' pinjaman otomatis diselesaikan.');
    }
}