<?php
class DivisiModel
{
    private $pdo;

    // 🔹 Nama tabel disimpan di variabel agar mudah diubah nanti
    private $tableDivisi = 'divisi';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ===============================
    // Ambil semua data divisi
    public function getAll()
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->tableDivisi}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===============================
    // Ambil divisi berdasarkan ID
    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableDivisi} WHERE id_divisi = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ===============================
    // Tambah divisi baru
    public function insert($nama)
    {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->tableDivisi} (nama_divisi) VALUES (?)");
        return $stmt->execute([$nama]);
    }

    // ===============================
    // Update data divisi
    public function update($id, $nama)
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->tableDivisi} SET nama_divisi = ? WHERE id_divisi = ?");
        return $stmt->execute([$nama, $id]);
    }

    // ===============================
    // Hapus divisi
    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableDivisi} WHERE id_divisi = ?");
        return $stmt->execute([$id]);
    }
}
