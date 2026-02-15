<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

$q = trim($_GET['q'] ?? '');

try {
    if ($q === '') {
        $stmt = $db->prepare("
            SELECT s.*, sp.photo_path
            FROM students s
            LEFT JOIN student_photos sp ON s.id = sp.student_id
            ORDER BY s.name
            LIMIT 6
        ");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT s.*, sp.photo_path
            FROM students s
            LEFT JOIN student_photos sp ON s.id = sp.student_id
            WHERE LOWER(s.name) LIKE :search
            ORDER BY s.name
            LIMIT 10
        ");
        $stmt->execute(['search' => '%' . strtolower($q) . '%']);
    }

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($students);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
