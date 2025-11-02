<?php
// commands/GenerateMiddleware.php

$name = $argv[2] ?? null;
if (!$name) {
    echo "❌ Nama middleware wajib. Contoh: php console make:middleware AuthMiddleware\n";
    exit;
}

$middlewareName = ucfirst($name);
if (!str_ends_with($middlewareName, 'Middleware')) {
    $middlewareName .= 'Middleware';
}

$dir = __DIR__ . '/../middleware';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$filePath = "$dir/{$middlewareName}.php";

if (file_exists($filePath)) {
    echo "⚠️  Middleware {$middlewareName} sudah ada!\n";
    exit;
}

$template = <<<PHP
<?php
// middleware/{$middlewareName}.php

class {$middlewareName}
{
    public static function handle()
    {
        // Tambahkan logika middleware di sini
    }
}
PHP;

file_put_contents($filePath, $template);
echo "✅ Middleware {$middlewareName} berhasil dibuat di middleware/{$middlewareName}.php\n";
