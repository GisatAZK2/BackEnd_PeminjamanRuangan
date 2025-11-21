<?php
include __DIR__ . '/../config/db.php';
$files = glob(__DIR__ . '/../migrations/*.php');

foreach ($files as $file) {
    $migration = include $file;
    echo "Migrating: {$migration['table']}...";
    $pdo->exec($migration['up']);
    echo " ✅ Done\n";
}
