<?php
include __DIR__ . '/../config/db.php';
include __DIR__ . '/../seeders/UserSeeder.php';

$seeder = new UserSeeder($pdo);
$seeder->run();
