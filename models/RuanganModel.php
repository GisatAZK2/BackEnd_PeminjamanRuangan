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
    // NOTULEN
    // ===============================

    public function uploadNotulen($pinjam_id, $file)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO notulen_files (pinjam_id, file_name, file_type, file_size, file_data)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $pinjam_id,
            $file['name'],
            $file['type'],
            $file['size'],
            file_get_contents($file['tmp_name'])
        ]);
    }

    // ✅ Tambahan: Tandai selesai manual
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
