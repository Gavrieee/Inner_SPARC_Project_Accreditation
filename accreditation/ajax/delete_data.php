<?php
$host = 'localhost';
$dbname = 'inner_sparc_accreditation';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    if (!isset($_POST['id'])) {
        exit("❌ Missing ID.");
    }

    $stmt = $pdo->prepare("DELETE FROM manual_data WHERE id = :id");
    $stmt->execute([':id' => $_POST['id']]);

    echo "🗑️ Deleted ID {$_POST['id']}";
} catch (PDOException $e) {
    echo "❌ DB Error: " . $e->getMessage();
}