<?php
return [
    "table" => "Pinjam_Ruangan",
    "up" => "
        CREATE TABLE IF NOT EXISTS Pinjam_Ruangan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            ruangan_id INT NOT NULL,
            kegiatan VARCHAR(255) NOT NULL,
            tanggal_mulai DATE NOT NULL,
            tanggal_selesai DATE NOT NULL,
            jam_mulai TIME NOT NULL,
            jam_selesai TIME NOT NULL,
            status ENUM('pending', 'disetujui', 'ditolak', 'selesai') DEFAULT 'pending',
            tanggal_selesai_rapat DATETIME NULL,
            keterangan TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES table_user(id_user) ON DELETE CASCADE,
            FOREIGN KEY (ruangan_id) REFERENCES Ruangan(id) ON DELETE CASCADE
        )
    ",
    "down" => "DROP TABLE IF EXISTS Pinjam_Ruangan"
];
