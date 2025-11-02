<?php
return [
    "table" => "Notulen_files",
    "up" => "
        CREATE TABLE IF NOT EXISTS notulen_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pinjam_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            file_size INT UNSIGNED NOT NULL,
            file_data LONGBLOB NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pinjam_id) REFERENCES Pinjam_Ruangan(id) ON DELETE CASCADE
        )
    ",
    "down" => "DROP TABLE IF EXISTS Notulen_files"
];
