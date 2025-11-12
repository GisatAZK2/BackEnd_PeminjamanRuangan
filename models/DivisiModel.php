<?php

class DivisiModel
{
    private $pdo;
    private $cache;
    private $tableDivisi = 'divisi';

    public function __construct(PDO $pdo, $cache)
    {
        $this->pdo = $pdo;
        $this->cache = $cache;
    }

    // ===============================
public function getAll()
{
    $cacheKey = 'divisi.all.with_user_count';
    $cached = $this->cache->get($cacheKey);

    if ($cached === null) {
        $sql = "
            SELECT 
                d.id_divisi,
                d.nama_divisi,
                COUNT(u.id_user) AS jumlah_anggota
            FROM {$this->tableDivisi} d
            LEFT JOIN user u ON d.id_divisi = u.id_divisi
            GROUP BY d.id_divisi
            ORDER BY d.nama_divisi ASC
        ";

        $stmt = $this->pdo->query($sql);
        $cached = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->cache->set($cacheKey, $cached, 3600); // cache 1 jam
    }

    return $cached;
}


    // ===============================
    // Ambil divisi berdasarkan ID (dari cache jika ada)
   public function getById($id)
{
    $cacheKey = "divisi.id.{$id}";
    $cached = $this->cache->get($cacheKey);

    if ($cached === null) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableDivisi} WHERE id_divisi = ?");
        $stmt->execute([$id]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($cached) {
            $this->cache->set($cacheKey, $cached, 3600);
        }
    }

    return $cached;
}

    // ===============================
    // Tambah divisi baru + invalidate cache
    public function insert($nama)
    {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->tableDivisi} (nama_divisi) VALUES (?)");
        $result = $stmt->execute([$nama]);

        if ($result) {
            $this->invalidateCache();
        }

        return $result;
    }

    // ===============================
    // Update data divisi + invalidate cache
    public function update($id, $nama)
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->tableDivisi} SET nama_divisi = ? WHERE id_divisi = ?");
        $result = $stmt->execute([$nama, $id]);

        if ($result) {
            $this->invalidateCache();
            // Hapus cache spesifik untuk ID ini
            $this->cache->delete("divisi.id.{$id}");
        }

        return $result;
    }

    // ===============================
    // Hapus divisi + invalidate cache
    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableDivisi} WHERE id_divisi = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            $this->invalidateCache();
            $this->cache->delete("divisi.id.{$id}");
        }

        return $result;
    }

    // ===============================
    // Invalidate semua cache terkait divisi
    private function invalidateCache()
    {
        $this->cache->delete('divisi.all');
        // Opsional: hapus semua cache per ID (jika ingin bersih total)
        // Tapi cukup dengan delete 'all' karena akan rebuild saat diakses
    }
}