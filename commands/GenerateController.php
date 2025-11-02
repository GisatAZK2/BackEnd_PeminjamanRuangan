<?php
// commands/GenerateController.php

// Ambil argumen nama controller dari CLI
$name = $argv[2] ?? null;
if (!$name) {
    echo "❌ Nama controller wajib. Contoh: php console make:controller AuthController\n";
    exit;
}

// Normalisasi nama controller
$controllerName = ucfirst($name);
if (!str_ends_with($controllerName, 'Controller')) {
    $controllerName .= 'Controller';
}

// Path folder dan file
$dirPath = __DIR__ . "/../controllers";
$filePath = "{$dirPath}/{$controllerName}.php";

// Cek dan buat folder controllers jika belum ada
if (!is_dir($dirPath)) {
    mkdir($dirPath, 0777, true);
    echo "📁 Folder 'controllers' berhasil dibuat.\n";
}

// Cek apakah controller sudah ada
if (file_exists($filePath)) {
    echo "⚠️  Controller {$controllerName} sudah ada!\n";
    exit;
}

// Template isi file controller
$template = <<<PHP
<?php
// controllers/{$controllerName}.php

require_once __DIR__ . '/../models/ExampleModel.php'; // Ganti sesuai model yang digunakan

class {$controllerName}
{
    private \$model;

    public function __construct(\$pdo)
    {
        \$this->model = new ExampleModel(\$pdo);
    }

    public function index()
    {
        \$data = \$this->model->all();
        header('Content-Type: application/json');
        echo json_encode(\$data);
    }

    public function show(\$id)
    {
        \$data = \$this->model->find(\$id);
        header('Content-Type: application/json');
        echo json_encode(\$data);
    }
}
PHP;

// Simpan file controller
file_put_contents($filePath, $template);

echo "✅ Controller {$controllerName} berhasil dibuat di controllers/{$controllerName}.php\n";
