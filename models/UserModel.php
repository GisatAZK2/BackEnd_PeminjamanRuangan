
<?php
use Psr\SimpleCache\CacheInterface;

class UserModel
{
    private $pdo;
    private $cache;
    private $tableName = 'user';
    private $tableDivisiName = 'divisi';
    private $tablePinjam = 'pinjam_ruangan';

    public function __construct(PDO $pdo, CacheInterface $cache)
    {
        $this->pdo = $pdo;
        $this->cache = $cache;
    }

    // ===============================
    // Cari user by username (cache)
    // ===============================
    public function findByUsername(string $username)
    {
        $cacheKey = "user_by_username_" . md5($username);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $stmt = $this->pdo->prepare("
            SELECT id_user, username, password_hash, role, nama, email, nomor_telepon, id_divisi, nama_divisi_snapshot, is_logged_in
            FROM {$this->tableName}
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->cache->set($cacheKey, $user, 300); // 5 menit
        }
        return $user;
    }

    // ===============================
    // Ambil user by ID (gunakan snapshot, tanpa JOIN)
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
                u.nama_divisi_snapshot AS nama_divisi,
                u.is_logged_in
            FROM {$this->tableName} u
            WHERE u.id_user = ?
        ");
        $stmt->execute([$id_user]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->cache->set($cacheKey, $user, 300);
        }
        return $user;
    }

    // ===============================
    // Ambil semua user (snapshot + cache)
    // ===============================
    public function getAllUsers(?string $roleFilter = null)
    {
        $cacheKey = "all_users_" . ($roleFilter ?? 'all');
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        if ($roleFilter) {
            $stmt = $this->pdo->prepare("
                SELECT u.*, u.nama_divisi_snapshot AS nama_divisi
                FROM {$this->tableName} u
                WHERE u.role = ?
                ORDER BY u.nama ASC
            ");
            $stmt->execute([$roleFilter]);
        } else {
            $stmt = $this->pdo->query("
                SELECT u.*, u.nama_divisi_snapshot AS nama_divisi
                FROM {$this->tableName} u
                ORDER BY u.nama ASC
            ");
        }

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->cache->set($cacheKey, $users, 120); // 2 menit
        return $users;
    }

    // ===============================
    // Update status login (race-safe)
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

            $this->cache->delete("user_by_id_" . $id_user);
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Login status update failed: " . $e->getMessage());
            return false;
        }
    }

    // ===============================
    // Tambah user baru + snapshot nama_divisi
    // ===============================
    public function insertUser(array $data)
    {
        $this->pdo->beginTransaction();
        try {
            $nama_divisi = '';
            if (!empty($data['id_divisi'])) {
                $divStmt = $this->pdo->prepare("SELECT nama_divisi FROM {$this->tableDivisiName} WHERE id_divisi = ?");
                $divStmt->execute([$data['id_divisi']]);
                $div = $divStmt->fetch(PDO::FETCH_ASSOC);
                $nama_divisi = $div['nama_divisi'] ?? '';
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tableName} 
                (username, nama, nomor_telepon, email, password_hash, password_plain, id_divisi, nama_divisi_snapshot, role, is_logged_in)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");

            $result = $stmt->execute([
                $data['username'],
                $data['nama'],
                $data['nomor_telepon'] ?? '',
                $data['email'],
                $data['password_hash'],
                $data['password_plain'],
                $data['id_divisi'] ?? null,
                $nama_divisi,
                $data['role'] ?? 'peminjam'
            ]);

            if ($result) {
                $this->cache->delete("all_users_all");
                $this->cache->delete("all_users_peminjam");
            }

            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Insert user failed: " . $e->getMessage());
            return false;
        }
    }

    // ===============================
    // Update user + sync snapshot ke pinjam_ruangan jika nama/divisi berubah
    // ===============================
    public function updateUser(int $id_user, array $data)
    {
        $this->pdo->beginTransaction();
        try {
            $fields = [];
            $params = [];

            foreach ($data as $key => $value) {
                if ($key === 'id_divisi' && $value) {
                    $divStmt = $this->pdo->prepare("SELECT nama_divisi FROM {$this->tableDivisiName} WHERE id_divisi = ?");
                    $divStmt->execute([$value]);
                    $div = $divStmt->fetch(PDO::FETCH_ASSOC);
                    $nama_divisi = $div['nama_divisi'] ?? '';
                    $fields[] = "nama_divisi_snapshot = ?";
                    $params[] = $nama_divisi;
                }
                $fields[] = "$key = ?";
                $params[] = $value;
            }

            if (empty($fields)) {
                $this->pdo->rollBack();
                return false;
            }

            $params[] = $id_user;
            $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . " WHERE id_user = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                // Sync nama_user_snapshot ke pinjam_ruangan jika nama berubah
                if (isset($data['nama'])) {
                    $syncStmt = $this->pdo->prepare("UPDATE {$this->tablePinjam} SET nama_user_snapshot = ? WHERE user_id = ?");
                    $syncStmt->execute([$data['nama'], $id_user]);
                    $this->cache->delete("booking_history_user_*");
                }

                $this->cache->delete("user_by_id_" . $id_user);
                $this->cache->delete("all_users_all");
                $this->cache->delete("all_users_peminjam");
            }

            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Update user failed: " . $e->getMessage());
            return false;
        }
    }

    // ===============================
    // Hapus user (snapshot tetap ada di pinjam_ruangan)
    // ===============================
    public function deleteUser(int $id_user)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE id_user = ?");
        $result = $stmt->execute([$id_user]);

        if ($result) {
            $this->cache->delete("user_by_id_" . $id_user);
            $this->cache->delete("all_users_all");
            $this->cache->delete("all_users_peminjam");
        }
        return $result;
    }

    // ===============================
    // Mark user as pending edit (untuk request edit dari petugas)
    // ===============================
    public function markPendingEdit(int $id_user)
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->tableName} SET is_pending_edit = 1 WHERE id_user = ?");
        return $stmt->execute([$id_user]);
    }
}