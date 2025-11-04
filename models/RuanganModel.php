<?php
class RuanganModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ===============================
    // RUANGAN
    // ===============================
    public function getAllRooms()
    {
        $stmt = $this->pdo->query("SELECT * FROM Ruangan ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRoom($name)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Ruangan (ruangan_name) VALUES (?)");
        return $stmt->execute([$name]);
    }

    // ===============================
    // PEMINJAMAN
    // ===============================
    public function isRoomAvailable($ruangan_id, $tanggal_mulai, $tanggal_selesai, $jam_mulai, $jam_selesai)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM Pinjam_Ruangan 
            WHERE ruangan_id = ? 
              AND status IN ('pending','disetujui')
              AND (
                (tanggal_mulai <= ? AND tanggal_selesai >= ?) 
                OR (tanggal_mulai <= ? AND tanggal_selesai >= ?)
              )
              AND (
                (jam_mulai <= ? AND jam_selesai >= ?) 
                OR (jam_mulai <= ? AND jam_selesai >= ?)
              )
        ");
        $stmt->execute([
            $ruangan_id,
            $tanggal_selesai, $tanggal_mulai,
            $tanggal_selesai, $tanggal_mulai,
            $jam_selesai, $jam_mulai,
            $jam_selesai, $jam_mulai
        ]);
        return $stmt->fetchColumn() == 0;
    }

    public function createBooking($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO Pinjam_Ruangan 
                (user_id, ruangan_id, kegiatan, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['ruangan_id'],
            $data['kegiatan'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['jam_mulai'],
            $data['jam_selesai']
        ]);
    }

    public function updateStatus($id, $status, $keterangan = null)
    {
        $stmt = $this->pdo->prepare("UPDATE Pinjam_Ruangan SET status = ?, keterangan = ? WHERE id = ?");
        return $stmt->execute([$status, $keterangan, $id]);
    }

    public function getBookingById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT pr.*, u.nama AS nama_user, r.ruangan_name 
            FROM Pinjam_Ruangan pr
            JOIN table_user u ON pr.user_id = u.id_user
            JOIN Ruangan r ON pr.ruangan_id = r.id
            WHERE pr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ===============================
    // NOTULEN MULTI-FILE (AUTO WEBP with safe fallbacks)
    // - stores files_json (array meta) and files_blob (array base64)
    // ===============================
    public function uploadNotulenMulti($pinjam_id, $files)
{
    $fileMetas = [];
    $fileContents = [];

    // normalisasi array file
    if (!is_array($files['name'])) {
        $names = [$files['name']];
        $tmp_names = [$files['tmp_name']];
        $types = [$files['type']];
        $sizes = [$files['size']];
        $errors = [$files['error']];
    } else {
        $names = $files['name'];
        $tmp_names = $files['tmp_name'];
        $types = $files['type'];
        $sizes = $files['size'];
        $errors = $files['error'];
    }

    for ($i = 0; $i < count($names); $i++) {
        if ($errors[$i] !== UPLOAD_ERR_OK) continue;

        $tmp = $tmp_names[$i];
        $name = $names[$i];
        $type = $types[$i] ?? mime_content_type($tmp);
        $size = $sizes[$i] ?? filesize($tmp);

        $meta = [
            'name' => $name,
            'type' => $type,
            'size' => $size
        ];
        $fileMetas[] = $meta;
        $fileContents[] = base64_encode(file_get_contents($tmp));
    }

    if (empty($fileMetas)) return false;

    // ✅ Ambil data lama (kalau ada)
    $stmt = $this->pdo->prepare("SELECT files_json, files_blob FROM Notulen_files WHERE pinjam_id = ?");
    $stmt->execute([$pinjam_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $oldMetas = json_decode($existing['files_json'], true) ?: [];
        $oldBlobs = json_decode($existing['files_blob'], true) ?: [];

        // gabungkan data lama + baru
        $fileMetas = array_merge($oldMetas, $fileMetas);
        $fileContents = array_merge($oldBlobs, $fileContents);

        $json = json_encode($fileMetas);
        $blob = json_encode($fileContents);

        $stmt = $this->pdo->prepare("
            UPDATE Notulen_files 
            SET files_json = ?, files_blob = ?
            WHERE pinjam_id = ?
        ");
        return $stmt->execute([$json, $blob, $pinjam_id]);
    } else {
        // belum ada data, buat baru
        $json = json_encode($fileMetas);
        $blob = json_encode($fileContents);

        $stmt = $this->pdo->prepare("
            INSERT INTO Notulen_files (pinjam_id, files_json, files_blob)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$pinjam_id, $json, $blob]);
    }
}

    public function getNotulenFileByIndex($file_id, $index)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Notulen_files WHERE id = ?");
        $stmt->execute([$file_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;

        $meta = json_decode($data['files_json'], true);
        $blobArray = json_decode($data['files_blob'], true);
        if (!isset($meta[$index]) || !isset($blobArray[$index])) return null;

        return [
            'pinjam_id' => $data['pinjam_id'],
            'meta' => $meta[$index],
            'data' => base64_decode($blobArray[$index])
        ];
    }

    public function markAsFinished($pinjam_id)
    {
        $stmt = $this->pdo->prepare("
            UPDATE Pinjam_Ruangan 
            SET status = 'selesai', keterangan = 'Rapat selesai dan notulen telah diunggah.'
            WHERE id = ?
        ");
        return $stmt->execute([$pinjam_id]);
    }

    // ===============================
    // HISTORI (multi-file)
    // ===============================
    public function getBookingHistory($user, $filter = 'semua')
    {
        $sql = "
            SELECT 
                pr.*, 
                u.nama AS nama_user, 
                r.ruangan_name,
                nf.id AS notulen_id,
                nf.files_json,
                nf.files_blob
            FROM Pinjam_Ruangan pr
            JOIN table_user u ON pr.user_id = u.id_user
            JOIN Ruangan r ON pr.ruangan_id = r.id
            LEFT JOIN Notulen_files nf ON nf.pinjam_id = pr.id
        ";

        $params = [];
        if ($user['role'] === 'peminjam') {
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
            // Di dalam loop
            if (!empty($row['files_json'])) {
                $meta = json_decode($row['files_json'], true) ?: [];
                $blobs = json_decode($row['files_blob'], true) ?: [];
                $files = [];
                for ($i = 0; $i < count($meta); $i++) {
                    $files[] = [
                        'name' => $meta[$i]['name'],
                        'type' => $meta[$i]['type'],
                        'size' => $meta[$i]['size'],
                        'preview_url' => 'data:' . $meta[$i]['type'] . ';base64,' . ($blobs[$i] ?? ''),
                        'download_url' => "/api/downloadNotulen/{$row['notulen_id']}?index={$i}"
                    ];
                }
                $row['notulen'] = $files;
            } else {
                $row['notulen'] = null;
            }
            unset($row['files_json'], $row['files_blob']);
        }

        return $rows;
    }

    // ===============================
    // AUTO-SELESAI
    // ===============================
    public function getExpiredBookings()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM Pinjam_Ruangan 
            WHERE status = 'disetujui' 
              AND tanggal_selesai < (NOW() - INTERVAL 1 DAY)
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
