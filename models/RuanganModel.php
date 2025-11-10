<?php
class RuanganModel
{
    private $pdo;
    private $cache;
    private $tableRuangan = 'ruangan';
    private $tablePinjam = 'pinjam_ruangan';
    private $tableUser = 'user';
    private $tableNotulen = 'notulen_files';

    public function __construct(PDO $pdo, $cache)
    {
        $this->pdo = $pdo;
        $this->cache = $cache;
    }

    // ===============================
    // Ambil semua ruangan (cache 5 menit)
    public function getAllRooms()
    {
        $cacheKey = 'ruangan_all';
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $stmt = $this->pdo->query("SELECT * FROM {$this->tableRuangan} ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->cache->set($cacheKey, $data, 300); // 5 menit
        return $data;
    }

    // 🔹 Ambil ruangan berdasarkan ID
    public function getroomById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableRuangan} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔹 Update ruangan
    public function updateRoom($id, $name)
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->tableRuangan} SET ruangan_name = ? WHERE id = ?");
        $success = $stmt->execute([$name, $id]);

        if ($success && $this->cache) $this->cache->delete('ruangan_all');
        return $success;
    }

    // 🔹 Hapus ruangan
    public function deleteRoom($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableRuangan} WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success && $this->cache) $this->cache->delete('ruangan_all');
        return $success;
    }

    public function addRoom($name)
    {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->tableRuangan} (ruangan_name) VALUES (?)");
        $success = $stmt->execute([$name]);

        if ($success) {
            $this->cache->delete('ruangan_all');
        }
        return $success;
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
        $success = $stmt->execute([
            $data['user_id'],
            $data['ruangan_id'],
            $data['kegiatan'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['jam_mulai'],
            $data['jam_selesai'],
        ]);

        if ($success) {
            $this->cache->delete("booking_history_user_{$data['user_id']}");
            $this->cache->delete("approved_bookings_room_{$data['ruangan_id']}");
        }
        return $success;
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
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tablePinjam} WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status, $keterangan = null)
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->tablePinjam} SET status = ?, keterangan = ? WHERE id = ?");
        $success = $stmt->execute([$status, $keterangan, $id]);

        if ($success) {
            $this->cache->delete("booking_history_all");
            $this->cache->delete("booking_history_user_*");
            $this->cache->delete("approved_bookings_room_*");
        }
        return $success;
    }

    // ===============================
    // Upload notulen multi
    public function uploadNotulenMulti($pinjam_id, $files)
    {
        if (!isset($files['name'])) return false;
        $count = is_array($files['name']) ? count($files['name']) : 1;
        $uploaded = false;

        for ($i = 0; $i < $count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
            $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($error !== UPLOAD_ERR_OK || $size > 16 * 1024 * 1024) continue;

            $dataBase64 = base64_encode(file_get_contents($tmp));
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tableNotulen} (pinjam_id, file_name, file_type, file_size, data_base64, uploaded_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$pinjam_id, $name, $type, $size, $dataBase64]);
            $uploaded = true;
        }

        if ($uploaded) {
            $this->cache->delete("booking_history_user_*");
            $this->cache->delete("booking_notulen_{$pinjam_id}");
        }
        return $uploaded;
    }

    public function getNotulenFilesByPinjam($pinjam_id)
    {
        $cacheKey = "booking_notulen_{$pinjam_id}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableNotulen} WHERE pinjam_id = ?");
        $stmt->execute([$pinjam_id]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->cache->set($cacheKey, $files, 600); // 10 menit
        return $files;
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
        $success = $stmt->execute([$pinjam_id]);

        if ($success) {
            $this->cache->delete("booking_history_user_*");
            $this->cache->delete("approved_bookings_room_*");
        }
        return $success;
    }

    // ===============================
    // Get booking history — FIX N+1 dengan JOIN
    public function getBookingHistory($user, $filter = 'semua')
    {
        $cacheKey = "booking_history_user_{$user['id_user']}_{$filter}";
        if (($user['role'] ?? '') !== 'peminjam') {
            $cacheKey = "booking_history_all_{$filter}";
        }

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = "
            SELECT 
                pr.*, 
                u.nama AS nama_user, 
                r.ruangan_name,
                GROUP_CONCAT(
                    CONCAT(nf.id, '|', nf.file_name, '|', nf.file_type, '|', nf.file_size, '|', nf.data_base64)
                    SEPARATOR '||'
                ) AS notulen_raw
            FROM {$this->tablePinjam} pr
            JOIN {$this->tableUser} u ON pr.user_id = u.id_user
            JOIN {$this->tableRuangan} r ON pr.ruangan_id = r.id
            LEFT JOIN {$this->tableNotulen} nf ON nf.pinjam_id = pr.id
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

        $sql .= " GROUP BY pr.id ORDER BY pr.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $notulen = [];
            if ($row['notulen_raw']) {
                $items = explode('||', $row['notulen_raw']);
                foreach ($items as $item) {
                    $parts = explode('|', $item, 5);
                    if (count($parts) === 5) {
                        $notulen[] = [
                            'id' => (int)$parts[0],
                            'name' => $parts[1],
                            'type' => $parts[2],
                            'size' => (int)$parts[3],
                            'preview_url' => 'data:' . $parts[2] . ';base64,' . $parts[4],
                            'download_url' => "/api/downloadNotulen/{$parts[0]}"
                        ];
                    }
                }
            }
            $row['notulen'] = $notulen;
            unset($row['notulen_raw']);
        }

        $this->cache->set($cacheKey, $rows, 120); // 2 menit
        return $rows;
    }

    // ===============================
    // Get approved bookings by room (cached)
    public function getApprovedBookingsByRoom($ruangan_id)
    {
        $cacheKey = "approved_bookings_room_{$ruangan_id}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $stmt = $this->pdo->prepare("
            SELECT id, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, user_id
            FROM {$this->tablePinjam}
            WHERE ruangan_id = ? AND status = 'disetujui'
            ORDER BY tanggal_mulai ASC, jam_mulai ASC
        ");
        $stmt->execute([$ruangan_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->cache->set($cacheKey, $data, 300); // 5 menit
        return $data;
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