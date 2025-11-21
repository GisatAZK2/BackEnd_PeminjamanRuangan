<?php
include __DIR__ . '/../config/db.php';

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $cols = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $createSQL = $cols['Create Table'] ?? '';
    
    $timestamp = date('Y_m_d_His');
    $file = __DIR__ . "/../migrations/{$timestamp}_create_{$table}_table.php";
    
    $template = "<?php\nreturn [\n" .
        '    "table" => "' . $table . '",' . "\n" .
        '    "up" => ' . var_export($createSQL . ";", true) . ",\n" .
        '    "down" => "DROP TABLE IF EXISTS ' . $table . ';"' . "\n" .
        "];";
    
    file_put_contents($file, $template);
    echo "📦 Migration generated for table: $table\n";
}
