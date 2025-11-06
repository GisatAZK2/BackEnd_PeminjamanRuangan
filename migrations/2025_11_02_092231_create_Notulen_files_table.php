<?php
return [
    "table" => "Notulen_files",
    "up" => "
        CREATE TABLE IF NOT EXISTS Notulen_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pinjam_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            data_base64 LONGTEXT NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pinjam_id) REFERENCES Pinjam_Ruangan(id) ON DELETE CASCADE
        )
    ",
    "down" => "DROP TABLE IF EXISTS Notulen_files"
];
