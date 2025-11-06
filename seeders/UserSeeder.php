<?php
class UserSeeder {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function run() {
        $sql = "INSERT INTO User (username, nama, nomor_telepon, email, password, id_divisi, role) VALUES
			('admin01', 'Administrator Utama', '081234567890', 'admin@example.com', MD5('admin123'), 1, 'administrator'),
			('petugas01', 'Petugas Gudang', '082345678901', 'petugas@example.com', MD5('petugas123'), 2, 'petugas'),
			('anggota01', 'Anggota Perpustakaan', '083456789012', 'anggota@example.com', MD5('anggota123'), 3, 'peminjam');";
        $this->pdo->exec($sql);
        echo "🌱 UserSeeder done!\n";
    }
}
