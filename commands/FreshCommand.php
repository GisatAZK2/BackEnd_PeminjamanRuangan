<?php
include __DIR__ . '/../config/db.php';
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $pdo->exec("DROP TABLE IF EXISTS `$t`");
    echo "🗑️ Drop table: $t\n";
}
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

include __DIR__ . '/MigrateCommand.php';
echo "🔁 Fresh migration complete!\n";
