<?php
// Auto-generated migration: 2025_11_05_045834_create_User_table.php
return [
    "table" => "User",
    "up" => "
        CREATE TABLE IF NOT EXISTS User (
			id_user INT AUTO_INCREMENT PRIMARY KEY,
			username VARCHAR(50) NOT NULL UNIQUE,
			nama VARCHAR(100) NOT NULL,
			nomor_telepon VARCHAR(20),
			email VARCHAR(100) UNIQUE,
			password_hash varchar(255) NOT NULL,
			password_plain TEXT NOT NULL,
			id_divisi INT,
			is_logged_in bool,
			is_pending_edit bool,
			role ENUM('administrator', 'petugas', 'peminjam') NOT NULL DEFAULT 'peminjam',
			FOREIGN KEY (id_divisi) REFERENCES Divisi(id_divisi)
				ON UPDATE CASCADE
				ON DELETE SET NULL
        )
    ",
    "down" => "DROP TABLE IF EXISTS User"
];