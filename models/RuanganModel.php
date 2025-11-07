<?php
class RuanganModel
{
    private $pdo;

    // 🔹 Nama-nama tabel disimpan dalam variabel agar mudah diubah
    private $tableRuangan = 'ruangan';
    private $tablePinjam = 'pinjam_ruangan';
    private $tableUser = 'user';
    private $tableNotulen = 'notulen_files';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ===============================
    // Ambil semua ruangan
    public function getAllRooms()
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->tableRuangan} ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRoom($name)
    {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->tableRuangan} (ruangan_name) VALUES (?)");
        return $stmt->execute([$name]);
    }

    // ===============================
    // Create booking
    public function createBooking($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->tablePinjam}
                (user_id, ruangan_id, kegiatan, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['ruangan_id'],
            $data['kegiatan'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['jam_mulai'],
            $data['jam_selesai'],
        ]);
    }

    // ===============================
    // Cek ketersediaan ruangan
    public function isRoomAvailable($ruangan_id, $tanggal_mulai, $tanggal_selesai, $jam_mulai, $jam_selesai)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM {$this->tablePinjam}
            WHERE ruangan_id = ?
              AND status IN ('pending','disetujui')
              AND NOT (
                (tanggal_selesai < ?)
                OR (tanggal_mulai > ?)
                OR (jam_selesai <= ?)
                OR (jam_mulai >= ?)
              )
        ");
        $stmt->execute([
            $ruangan_id,
            $tanggal_mulai,
            $tanggal_selesai,
            $jam_mulai,
            $jam_selesai
        ]);
        return $stmt->fetchColumn() == 0;
    }

    public function isRoomAvailableForUpdate($ruangan_id, $tanggal_mulai, $tanggal_selesai, $jam_mulai, $jam_selesai)
    {
        $sql = "
            SELECT id FROM {$this->tablePinjam}
            WHERE ruangan_id = ?
              AND status IN ('pending','disetujui')
              AND NOT (
                (tanggal_selesai < ?)
                OR (tanggal_mulai > ?)
                OR (jam_selesai <= ?)
                OR (jam_mulai >= ?)
              )
            FOR UPDATE
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $ruangan_id,
            $tanggal_mulai,
            $tanggal_selesai,
            $jam_mulai,
            $jam_selesai
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($rows) === 0;
    }

    // ===============================
    // Get booking
    public function getBookingById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT pr.*, u.nama AS nama_user, r.ruangan_name
            FROM {$this->tablePinjam} pr
            JOIN {$this->tableUser} u ON pr.user_id = u.id_user
            JOIN {$this->tableRuangan} r ON pr.ruangan_id = r.id
            WHERE pr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBookingByIdForUpdate($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->tablePinjam}
            WHERE id = ?
            FOR UPDATE
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status, $keterangan = null)
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->tablePinjam} SET status = ?, keterangan = ? WHERE id = ?");
        return $stmt->execute([$status, $keterangan, $id]);
    }

    // ===============================
    // Upload notulen multi
    public function uploadNotulenMulti($pinjam_id, $files)
    {
        if (!isset($files['name'])) return false;

        $count = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
            $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($error !== UPLOAD_ERR_OK) continue;

            $dataBase64 = base64_encode(file_get_contents($tmp));
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tableNotulen} (pinjam_id, file_name, file_type, file_size, data_base64, uploaded_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$pinjam_id, $name, $type, $size, $dataBase64]);
        }
        return true;
    }

    public function getNotulenFilesByPinjam($pinjam_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableNotulen} WHERE pinjam_id = ?");
        $stmt->execute([$pinjam_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotulenFileById($file_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableNotulen} WHERE id = ?");
        $stmt->execute([$file_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markAsFinished($pinjam_id)
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->tablePinjam}
            SET status = 'selesai', keterangan = 'Rapat selesai dan notulen telah diunggah.', tanggal_selesai_rapat = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$pinjam_id]);
    }

    // ===============================
    // Get booking history
    public function getBookingHistory($user, $filter = 'semua')
    {
        $sql = "
            SELECT pr.*, u.nama AS nama_user, r.ruangan_name
            FROM {$this->tablePinjam} pr
            JOIN {$this->tableUser} u ON pr.user_id = u.id_user
            JOIN {$this->tableRuangan} r ON pr.ruangan_id = r.id
        ";
        $params = [];

        if (($user['role'] ?? '') === 'peminjam') {
            $sql .= " WHERE pr.user_id = ?";
            $params[] = $user['id_user'];
        } else {
            $sql .= " WHERE 1=1";
        }

        if ($filter !== 'semua') {
            $sql .= " AND pr.status = ?";
            $params[] = $filter;
        }

        $sql .= " ORDER BY pr.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $stmt2 = $this->pdo->prepare("SELECT * FROM {$this->tableNotulen} WHERE pinjam_id = ?");
            $stmt2->execute([$row['id']]);
            $files = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            if ($files) {
                $row['notulen'] = array_map(function ($f) {
                    return [
                        'id' => $f['id'],
                        'name' => $f['file_name'],
                        'type' => $f['file_type'],
                        'size' => $f['file_size'],
                        'preview_url' => 'data:' . $f['file_type'] . ';base64,' . $f['data_base64'],
                        'download_url' => "/api/downloadNotulen/{$f['id']}"
                    ];
                }, $files);
            } else {
                $row['notulen'] = [];
            }
        }
        return $rows;
    }

    // ===============================
    // Get approved bookings
    public function getApprovedBookingsByRoom($ruangan_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, user_id
            FROM {$this->tablePinjam}
            WHERE ruangan_id = ?
              AND status = 'disetujui'
            ORDER BY tanggal_mulai ASC, jam_mulai ASC
        ");
        $stmt->execute([$ruangan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===============================
    // Get expired bookings
    public function getExpiredBookings()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM {$this->tablePinjam}
            WHERE status = 'disetujui'
              AND tanggal_selesai < (NOW() - INTERVAL 1 DAY)
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
