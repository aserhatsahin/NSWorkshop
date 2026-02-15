<?php
$host = 'localhost';
$dbname = 'resim_atolyesi';
$user = 'root';
$pass = 'root';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    echo "Veritabanı bağlantı hatası: " . $e->getMessage();
    exit;
}
?>
