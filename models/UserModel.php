<?php
class UserModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // 🔹 Cari user berdasarkan username
    public function findByUsername(string $username)
    {
        $stmt = $this->pdo->prepare("
            SELECT id_user, username, password, role, nama, email, nomor_telepon, id_divisi, is_logged_in
            FROM table_user 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔹 Update status login
    public function setLoginStatus(int $id_user, int $status)
    {
        $stmt = $this->pdo->prepare("
            UPDATE table_user SET is_logged_in = ? WHERE id_user = ?
        ");
        return $stmt->execute([$status, $id_user]);
    }

    // 🔹 Logout paksa user
    public function setLogoutStatus(int $id_user)
    {
        $stmt = $this->pdo->prepare("
            UPDATE table_user SET is_logged_in = 0 WHERE id_user = ?
        ");
        return $stmt->execute([$id_user]);
    }

    // 🔹 Ambil semua user (filter sesuai role)
    public function getAllUsers(?string $roleFilter = null)
    {
        if ($roleFilter) {
            $stmt = $this->pdo->prepare("SELECT * FROM table_user WHERE role = ?");
            $stmt->execute([$roleFilter]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM table_user");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Ambil detail user berdasarkan ID
    public function getUserById(int $id_user)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM table_user WHERE id_user = ?");
        $stmt->execute([$id_user]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔹 Tambah user baru
    public function insertUser(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO table_user (username, nama, nomor_telepon, email, password, id_divisi, role, is_logged_in)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");
        return $stmt->execute([
            $data['username'],
            $data['nama'],
            $data['nomor_telepon'] ?? '',
            $data['email'],
            $data['password'],
            $data['id_divisi'] ?? null,
            $data['role'] ?? 'peminjam'
        ]);
    }

    // 🔹 Update data user
    public function updateUser(int $id_user, array $data)
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }

        if (empty($fields)) return false;

        $params[] = $id_user;
        $sql = "UPDATE table_user SET " . implode(', ', $fields) . " WHERE id_user = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // 🔹 Hapus user
    public function deleteUser(int $id_user)
    {
        $stmt = $this->pdo->prepare("DELETE FROM table_user WHERE id_user = ?");
        return $stmt->execute([$id_user]);
    }
}
