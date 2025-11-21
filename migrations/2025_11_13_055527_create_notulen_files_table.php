<?php
return [
    "table" => "notulen_files",
    "up" => "
        CREATE TABLE IF NOT EXISTS notulen_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pinjam_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            data_base64 LONGTEXT NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pinjam_id (pinjam_id)
        )
    ",
    "down" => "DROP TABLE IF EXISTS Notulen_files"
];
