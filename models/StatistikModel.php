<?php
class StatistikModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ============================================================
    // 🔹 Bagian untuk Administrator & Petugas
    // ============================================================
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

    // ============================================================
    // 🔹 Bagian untuk Peminjam
    // ============================================================
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
}
