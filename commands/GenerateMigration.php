<?php
// === GenerateMigration.php ===
// CLI: php console make:migration nama_tabel

$name = $argv[2] ?? null;
if (!$name) {
    echo "❌ Nama tabel wajib. Contoh: php console make:migration users\n";
    exit(1);
}

// Gunakan DIRECTORY_SEPARATOR agar aman di semua OS
$ds = DIRECTORY_SEPARATOR;
$timestamp = date('Y_m_d_His');
$baseDir = __DIR__ . "{$ds}..{$ds}migrations";

// Pastikan folder migrations ada
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
        echo "❌ Gagal membuat folder migrations di: $baseDir\n";
        exit(1);
    }
}

$filename = "{$timestamp}_create_{$name}_table.php";
$file = "{$baseDir}{$ds}{$filename}";

$template = <<<PHP
<?php
// Auto-generated migration: {$filename}
return [
    "table" => "$name",
    "up" => "
        CREATE TABLE IF NOT EXISTS $name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    "down" => "DROP TABLE IF EXISTS $name"
];
PHP;

// Simpan file migration
if (file_put_contents($file, $template) !== false) {
    echo "✅ Migration created: migrations/{$filename}\n";
} else {
    echo "❌ Gagal membuat migration file di {$file}\n";
    exit(1);
}
