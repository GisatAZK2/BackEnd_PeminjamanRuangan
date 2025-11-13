<?php
// Auto-generated migration: 2025_11_02_090956_create_Ruangan_table.php
return [
    "table" => "ruangan",
    "up" => "
        CREATE TABLE IF NOT EXISTS ruangan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ruangan_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ruangan_name (ruangan_name)
        )
    ",
    "down" => "DROP TABLE IF EXISTS ruangan"
];
