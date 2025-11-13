<?php
class UserSeeder {
    private $pdo;
    public function __construct($pdo) { 
        $this->pdo = $pdo; 
    }

    public function run() {
        // Gunakan password_hash (bcrypt)
        $plainPassword = 'admin123';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $sql = "
            INSERT INTO user (
                username, 
                nama, 
                nomor_telepon, 
                email, 
                password_hash, 
                password_plain,
                id_divisi, 
                nama_divisi_snapshot, 
                is_logged_in, 
                is_pending_edit, 
                role
            ) VALUES (
                'admin01',
                'Administrator Utama',
                '081234567890',
                'admin@example.com',
                :password_hash,
                :password_plain,
                1,
                'Divisi Utama',
                0,
                0,
                'administrator'
            );
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':password_hash' => $hashedPassword,
            ':password_plain' => $plainPassword
        ]);

        echo "🌱 UserSeeder (administrator) done!\n";
    }
}
