<?php
include __DIR__ . '/../config/db.php';
$files = glob(__DIR__ . '/../migrations/*.php');

foreach (array_reverse($files) as $file) {
    $migration = include $file;
    echo "Rollback: {$migration['table']}...";
    $pdo->exec($migration['down']);
    echo " 🌀 Done\n";
}
