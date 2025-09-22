<?php
include 'config.php'; // config.php файлыг оруулж байна.

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Асуудал гарсан үед алдаа мэдээлэл харах.
} catch (PDOException $e) {
    die("❌ DATABASE do not connected " . $e->getMessage());
}
?>
