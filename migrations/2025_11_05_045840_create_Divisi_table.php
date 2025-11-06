<?php
// Auto-generated migration: 2025_11_05_045840_create_Divisi_table.php
return [
    "table" => "Divisi",
    "up" => "
        CREATE TABLE IF NOT EXISTS Divisi (
            id_divisi INT AUTO_INCREMENT PRIMARY KEY,
			nama_divisi VARCHAR(100) NOT NULL
        )
    ",
    "down" => "DROP TABLE IF EXISTS Divisi"
];