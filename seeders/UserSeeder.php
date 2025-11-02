<?php
class UserSeeder {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function run() {
        $sql = "INSERT INTO users (name) VALUES ('Admin'), ('User Demo')";
        $this->pdo->exec($sql);
        echo "🌱 UserSeeder done!\n";
    }
}
