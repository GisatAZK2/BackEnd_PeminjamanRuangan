<?php
class DivisiModel
{
    private $pdo;
    private $tableDivisi = 'divisi';
    private $tableUser = 'user';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ===============================
    public function getAll()
    {
        $sql = "
            SELECT
                d.id_divisi,
                d.nama_divisi,
                COUNT(u.id_user) AS jumlah_anggota
            FROM {$this->tableDivisi} d
            LEFT JOIN {$this->tableUser} u ON d.id_divisi = u.id_divisi
            GROUP BY d.id_divisi
            ORDER BY d.nama_divisi ASC
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===============================
    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableDivisi} WHERE id_divisi = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ===============================
    public function insert($nama)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->tableDivisi} (nama_divisi)
            VALUES (?)
        ");
        return $stmt->execute([$nama]);
    }

    // ===============================
    public function update($id, $nama)
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->tableDivisi}
                SET nama_divisi = ?
                WHERE id_divisi = ?
            ");
            $result = $stmt->execute([$nama, $id]);

            if ($result) {
                $syncStmt = $this->pdo->prepare("
                    UPDATE {$this->tableUser}
                    SET nama_divisi_snapshot = ?
                    WHERE id_divisi = ?
                ");
                $syncStmt->execute([$nama, $id]);
            }

            $this->pdo->commit();
            return $result;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // ===============================
    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableDivisi} WHERE id_divisi = ?");
        return $stmt->execute([$id]);
    }
}
