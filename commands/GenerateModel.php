<?php
// commands/GenerateModel.php

// Ambil argumen nama model dari CLI
$name = $argv[2] ?? null;
if (!$name) {
    echo "❌ Nama model wajib. Contoh: php console make:model User\n";
    exit;
}

// Tentukan nama dan path file
$modelName = ucfirst($name) . "Model";
$dirPath = __DIR__ . "/../models";
$filePath = "{$dirPath}/{$modelName}.php";

// Cek dan buat folder models jika belum ada
if (!is_dir($dirPath)) {
    mkdir($dirPath, 0777, true);
    echo "📁 Folder 'models' berhasil dibuat.\n";
}

// Cek apakah model sudah ada
if (file_exists($filePath)) {
    echo "⚠️  Model {$modelName} sudah ada!\n";
    exit;
}

// Template isi file model
$template = <<<PHP
<?php
// models/{$modelName}.php

class {$modelName}
{
    private \$pdo;

    public function __construct(\$pdo)
    {
        \$this->pdo = \$pdo;
    }

    // Contoh method:
    public function all()
    {
        \$stmt = \$this->pdo->query("SELECT * FROM table_name");
        return \$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(\$id)
    {
        \$stmt = \$this->pdo->prepare("SELECT * FROM table_name WHERE id = ?");
        \$stmt->execute([\$id]);
        return \$stmt->fetch(PDO::FETCH_ASSOC);
    }
}
PHP;

// Tulis file model
file_put_contents($filePath, $template);

echo "✅ Model {$modelName} berhasil dibuat di models/{$modelName}.php\n";
