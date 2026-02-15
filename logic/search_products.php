<?php
header('Content-Type: application/json; charset=utf-8');
require "../includes/db.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if ($q !== '') {
        $stmt = $db->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY id DESC LIMIT 5");
        $stmt->execute(['%' . $q . '%']);
    } else {
        $stmt = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 5");
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($products);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
