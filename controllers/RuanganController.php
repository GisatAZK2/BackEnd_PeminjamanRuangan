<?php
require_once __DIR__ . '/../models/RuanganModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

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
        echo json_encode(['status'=>$status, 'message'=>$message, 'data'=>$data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }

     // 🟦 Tambahkan ruangan — hanya admin yang boleh
    public function addRoom() {
    $user = AuthMiddleware::requireRole(['administrator']);
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['ruangan_name']) || empty(trim($input['ruangan_name']))) {
        return $this->sendResponse('error', 'Nama ruangan wajib diisi.', null, 400);
    }

    $ruangan_name = trim($input['ruangan_name']);

    try {
        // ✅ Panggil model, jangan query langsung
        $success = $this->model->addRoom($ruangan_name);

        if ($success) {
            return $this->sendResponse('success', 'Ruangan berhasil ditambahkan.', [
                'ruangan_name' => $ruangan_name
            ]);
        } else {
            return $this->sendResponse('error', 'Gagal menambahkan ruangan.', null, 500);
        }
    } catch (PDOException $e) {
        return $this->sendResponse('error', 'Database error: ' . $e->getMessage(), null, 500);
    }
}

    // create booking with DB transaction + row lock to avoid race conditions
    public function createBooking()
    {
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
            // mulai transaction
            $this->pdo->beginTransaction();

            // cek ketersediaan secara eksklusif (SELECT ... FOR UPDATE)
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

            // simpan booking
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

    // update status with transaction and optional checks
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

            // ambil booking dan lock row
            $booking = $this->model->getBookingByIdForUpdate($id);
            if (!$booking) {
                $this->pdo->rollBack();
                return $this->sendResponse('error','Booking tidak ditemukan.', null, 404);
            }

            // jika sudah disetujui/ditolak oleh orang lain, tolak update (prevent race)
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

    // mark finished + upload notulen (sudah ada, sedikit penyesuaian)
    public function markFinished($pinjam_id)
    {
        $user = $this->getUser();
        if (!$user || ($user['role'] ?? '') !== 'peminjam')
            return $this->sendResponse('error', 'Hanya peminjam yang dapat menyelesaikan rapat.', null, 403);

        $booking = $this->model->getBookingById($pinjam_id);
        if (!$booking) return $this->sendResponse('error','Data pinjaman tidak ditemukan.', null, 404);
        if (($booking['status'] ?? '') !== 'disetujui') return $this->sendResponse('error','Hanya rapat dengan status disetujui yang dapat diselesaikan.', null, 400);

        if (empty($_FILES)) return $this->sendResponse('error','File notulen wajib diunggah.', null, 400);

        $fileField = null;
        if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) $fileField = $_FILES['files'];
        elseif (isset($_FILES['file'])) $fileField = $_FILES['file'];
        else $fileField = reset($_FILES);

        $ok = $this->model->uploadNotulenMulti($pinjam_id, $fileField);
        if (!$ok) return $this->sendResponse('error','Gagal mengunggah file. Pastikan ukuran < 16MB.', null, 500);

        $this->model->markAsFinished($pinjam_id);
        $this->sendResponse('success','Rapat selesai & semua file notulen berhasil diunggah.');
    }

    // get booking history (sama seperti sebelumnya)
    public function getBookingHistory()
    {
        $user = $this->getUser();
        if (!$user) return $this->sendResponse('error','Unauthorized', null, 401);

        $filter = $_GET['filter'] ?? 'semua';
        $allowed = ['semua','pending','disetujui','ditolak','selesai'];
        if (!in_array($filter,$allowed)) $filter = 'semua';

        $data = $this->model->getBookingHistory($user, $filter);
        $this->sendResponse('success','Histori peminjaman berhasil diambil.', $data);
    }

    // DOWNLOAD NOTULEN: kembaliin JSON { base64, type, name } agar FE bisa render
    public function downloadNotulen($file_id)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->sendResponse('error','Unauthorized', null, 401);
        }

        $index = isset($_GET['index']) ? intval($_GET['index']) : null;

        // if file_id is actually pinjam_id + index (old behavior), we support both:
        // but model.getNotulenFileById expects file id. We'll assume client sends file id (as implemented in model).
        $file = $this->model->getNotulenFileById($file_id);
        if (!$file) {
            return $this->sendResponse('error','File tidak ditemukan.', null, 404);
        }

        // akses check: peminjam hanya boleh akses miliknya
        $booking = $this->model->getBookingById($file['pinjam_id']);
        if (($user['role'] ?? '') === 'peminjam' && $booking['user_id'] != $user['id_user']) {
            return $this->sendResponse('error','Akses ditolak.', null, 403);
        }

        // kirim JSON
        $payload = [
            'id' => $file['id'],
            'name' => $file['file_name'],
            'type' => $file['file_type'],
            'size' => (int)$file['file_size'],
            'base64' => $file['data_base64']
        ];
        return $this->sendResponse('success','File ditemukan.', $payload);
    }

    // NEW: endpoint yang menampilkan rentang waktu ruangan yg disetujui (berguna untuk calendar/cek availability)
    public function getRoomAvailability()
    {
        $user = $this->getUser();
        if (!$user) {
            // endpoint ini boleh publik juga, tapi kita cek API key di middleware, jadi izinkan user tanpa login.
            // untuk konsistensi kita retur data tanpa perlu login
        }
        $ruangan_id = isset($_GET['ruangan_id']) ? intval($_GET['ruangan_id']) : 0;
        if (!$ruangan_id) return $this->sendResponse('error','ruangan_id wajib.', null, 400);

        $data = $this->model->getApprovedBookingsByRoom($ruangan_id);
        // data: array of {id, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, user_id}
        return $this->sendResponse('success','Availabilities fetched.', $data);
    }

    // auto mark finished (sama)
    public function autoMarkFinished()
    {
        $expired = $this->model->getExpiredBookings();
        foreach ($expired as $booking) {
            $this->model->updateStatus($booking['id'], 'selesai', 'Otomatis diselesaikan oleh sistem.');
        }
        $this->sendResponse('success', count($expired) . ' pinjaman otomatis diselesaikan.');
    }
}
