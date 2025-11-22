<?php
class StatistikModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================== GLOBAL / ADMIN / PETUGAS ====================
    public function countUsers()
    {
        return $this->pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
    }

    public function countDivisi()
    {
        return $this->pdo->query("SELECT COUNT(*) FROM divisi")->fetchColumn();
    }

    public function countRuangan()
    {
        return $this->pdo->query("SELECT COUNT(*) FROM ruangan")->fetchColumn();
    }

    public function countAllPeminjaman()
    {
        return $this->pdo->query("SELECT COUNT(*) FROM pinjam_ruangan")->fetchColumn();
    }

    public function countUsersByRole($role)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user WHERE role = ?");
        $stmt->execute([$role]);
        return $stmt->fetchColumn();
    }

    public function countPeminjamanPerHari()
    {
        $sql = "
            SELECT DATE(created_at) AS tanggal, COUNT(*) AS total
            FROM pinjam_ruangan
            GROUP BY DATE(created_at)
            ORDER BY tanggal DESC
            LIMIT 30
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPeminjamanPerStatus()
    {
        $sql = "
            SELECT status, COUNT(*) AS total
            FROM pinjam_ruangan
            GROUP BY status
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== UNTUK PEMINJAM (hanya milik sendiri) ====================
    public function countUserPeminjaman($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pinjam_ruangan WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function countUserPeminjamanByStatus($user_id, $status)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pinjam_ruangan WHERE user_id = ? AND status = ?");
        $stmt->execute([$user_id, $status]);
        return $stmt->fetchColumn();
    }

    // ==================== TODAY BOOKINGS (GLOBAL) ====================
    public function countTodayBookings()
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pinjam_ruangan WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        return $stmt->fetchColumn();
    }

    // ==================== TODAY BOOKINGS KHUSUS USER (untuk peminjam) ====================
    public function countTodayBookingsByUser($user_id)
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pinjam_ruangan WHERE user_id = ? AND DATE(created_at) = ?");
        $stmt->execute([$user_id, $today]);
        return $stmt->fetchColumn();
    }

    // ==================== SEDANG BERLANGSUNG (GLOBAL) ====================
    public function countOngoing()
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT COUNT(*) FROM pinjam_ruangan
                WHERE status = 'disetujui'
                  AND CONCAT(tanggal_mulai, ' ', jam_mulai) <= ?
                  AND CONCAT(tanggal_selesai, ' ', jam_selesai) >= ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$now, $now]);
        return $stmt->fetchColumn();
    }

    // ==================== SELESAI HARI INI (GLOBAL) ====================
    public function countFinishedToday()
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pinjam_ruangan WHERE status = 'selesai' AND DATE(tanggal_selesai_rapat) = ?");
        $stmt->execute([$today]);
        return $stmt->fetchColumn();
    }
}