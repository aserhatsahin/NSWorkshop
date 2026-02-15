<?php
include 'db.php';

$q = trim($_GET['q'] ?? '');

try {
    if ($q === '') {
        // Boş arama ise varsayılan 6 öğrenci döndür
        $stmt = $db->prepare("
            SELECT s.*, sp.photo_path
            FROM students s
            LEFT JOIN student_photos sp ON s.id = sp.student_id
            ORDER BY s.name
            LIMIT 6
        ");
        $stmt->execute();
    } else {
        // Arama varsa filtreli sonuç döndür
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
    echo json_encode([]);
}
