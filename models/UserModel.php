<?php
use Psr\SimpleCache\CacheInterface;

class UserModel
{
    private $pdo;
    private $cache;
    private $tableName = 'user';
    private $tableDivisiName = 'divisi';

    public function __construct(PDO $pdo, CacheInterface $cache)
    {
        $this->pdo = $pdo;
        $this->cache = $cache;
    }

    // ===============================
    // 🔹 Cari user by username (cache)
    // ===============================
    public function findByUsername(string $username)
    {
        $cacheKey = "user_by_username_" . md5($username);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $stmt = $this->pdo->prepare("
            SELECT id_user, username, password_hash, role, nama, email, nomor_telepon, id_divisi, is_logged_in
            FROM {$this->tableName}
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->cache->set($cacheKey, $user, 300); // cache 5 menit
        }

        return $user;
    }

            // ===============================
        // 🔹 Ambil user by ID (JOIN + cache)
        // ===============================
        public function getUserById(int $id_user)
        {
            $cacheKey = "user_by_id_" . $id_user;
            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id_user,
                    u.username,
                    u.password_hash,
                    u.role,
                    u.nama,
                    u.email,
                    u.nomor_telepon,
                    d.nama_divisi,
                    u.is_logged_in
                FROM {$this->tableName} u
                LEFT JOIN divisi d ON u.id_divisi = d.id_divisi
                WHERE u.id_user = ?
            ");
            $stmt->execute([$id_user]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $this->cache->set($cacheKey, $user, 300); // cache 5 menit
            }

            return $user;
        }



    // ===============================
    // 🔹 Ambil semua user (cache + hindari N+1)
    // ===============================
    public function getAllUsers(?string $roleFilter = null)
    {
        $cacheKey = "all_users_" . ($roleFilter ?? 'all');
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        if ($roleFilter) {
            $stmt = $this->pdo->prepare("
                SELECT u.*, d.nama_divisi 
                FROM {$this->tableName} u
                LEFT JOIN divisi d ON u.id_divisi = d.id_divisi
                WHERE u.role = ?
            ");
            $stmt->execute([$roleFilter]);
        } else {
            $stmt = $this->pdo->query("
                SELECT u.*, d.nama_divisi 
                FROM {$this->tableName} u
                LEFT JOIN divisi d ON u.id_divisi = d.id_divisi
            ");
        }

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->cache->set($cacheKey, $users, 120); // cache 2 menit
        return $users;
    }

    // ===============================
    // 🔹 Update status login (race-safe)
    // ===============================
    public function setLoginStatus(int $id_user, int $status)
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->tableName}
                SET is_logged_in = ?
                WHERE id_user = ?
            ");
            $stmt->execute([$status, $id_user]);
            $this->pdo->commit();

            // Invalidate cache
            $this->cache->delete("user_by_id_" . $id_user);
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Race condition prevented: " . $e->getMessage());
            return false;
        }
    }

    // 🔹 Tambah user baru (hash + simpan plaintext)
    public function insertUser(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO User (username, nama, nomor_telepon, email, password_hash, password_plain, id_divisi, role, is_logged_in)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        return $stmt->execute([
            $data['username'],
            $data['nama'],
            $data['nomor_telepon'] ?? '',
            $data['email'],
            $data['password_hash'],
            $data['password_plain'], // disimpan juga versi asli
            $data['id_divisi'] ?? null,
            $data['role'] ?? 'peminjam'
        ]);
    }


    // ===============================
    // 🔹 Update user umum (invalidate cache)
    // ===============================
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
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . " WHERE id_user = ?";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            $this->cache->delete("user_by_id_" . $id_user);
            $this->cache->delete("all_users_all");
        }

        return $result;
    }

    // ===============================
    // 🔹 Hapus user (invalidate cache)
    // ===============================
    public function deleteUser(int $id_user)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE id_user = ?");
        $result = $stmt->execute([$id_user]);

        if ($result) {
            $this->cache->delete("user_by_id_" . $id_user);
            $this->cache->delete("all_users_all");
        }

        return $result;
    }
}
