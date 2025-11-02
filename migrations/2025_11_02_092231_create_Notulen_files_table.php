<?php
return [
    "table" => "Notulen_files",
    "up" => "
        CREATE TABLE IF NOT EXISTS Notulen_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pinjam_id INT NOT NULL,
            files_json JSON NOT NULL, -- metadata JSON (nama, type, size)
            files_blob LONGBLOB NOT NULL, -- data biner base64 gabungan
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pinjam_id) REFERENCES Pinjam_Ruangan(id) ON DELETE CASCADE
        )
    ",
    "down" => "DROP TABLE IF EXISTS Notulen_files"
];
