<?php
return [
    "table" => "pinjam_ruangan",
    "up" => "
        CREATE TABLE IF NOT EXISTS pinjam_ruangan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            nama_user_snapshot VARCHAR(100) NOT NULL,
            ruangan_id INT NOT NULL,
            nama_ruangan_snapshot VARCHAR(100) NOT NULL,
            kegiatan VARCHAR(255) NOT NULL,
            tanggal_mulai DATE NOT NULL,
            tanggal_selesai DATE NOT NULL,
            jam_mulai TIME NOT NULL,
            jam_selesai TIME NOT NULL,
            status ENUM('pending', 'disetujui', 'ditolak', 'selesai') DEFAULT 'pending',
            tanggal_selesai_rapat DATETIME NULL,
            keterangan TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_ruangan_id (ruangan_id),
            INDEX idx_status (status),
            INDEX idx_tanggal_mulai (tanggal_mulai)
        )
    ",
    "down" => "DROP TABLE IF EXISTS pinjam_ruangan"
];
