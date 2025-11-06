<?php
class DivisiModel
{
    private $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll() {
        return $this->pdo->query("SELECT * FROM Divisi")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM Divisi WHERE id_divisi = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($nama) {
        $stmt = $this->pdo->prepare("INSERT INTO Divisi (nama_divisi) VALUES (?)");
        return $stmt->execute([$nama]);
    }

    public function update($id, $nama) {
        $stmt = $this->pdo->prepare("UPDATE Divisi SET nama_divisi = ? WHERE id_divisi = ?");
        return $stmt->execute([$nama, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM Divisi WHERE id_divisi = ?");
        return $stmt->execute([$id]);
    }
}
