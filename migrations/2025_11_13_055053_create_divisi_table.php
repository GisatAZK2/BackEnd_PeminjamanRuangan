<?php
// Auto-generated migration: 2025_11_05_045840_create_Divisi_table.php
return [
    "table" => "divisi",
    "up" => "
        CREATE TABLE IF NOT EXISTS divisi (
            id_divisi INT AUTO_INCREMENT PRIMARY KEY,
            nama_divisi VARCHAR(100) NOT NULL,
            INDEX idx_nama_divisi (nama_divisi)
        )
    ",
    "down" => "DROP TABLE IF EXISTS divisi"
];