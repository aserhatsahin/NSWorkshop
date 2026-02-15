<?php
session_start();
require "../includes/db.php";

if (!isset($_GET['student_id'])) {
    die("Öğrenci ID belirtilmedi.");
}

$student_id = (int) $_GET['student_id'];

// Öğrenciyi grup bilgisiyle birlikte çek
$stmt = $db->prepare("
    SELECT s.*, cg.course_day, cg.group_name, sp.photo_path
    FROM students s
    LEFT JOIN course_groups cg ON s.group_id = cg.id
    LEFT JOIN student_photos sp ON s.id = sp.student_id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Öğrenci bulunamadı.");
}

// Borçları çek
$stmt = $db->prepare("SELECT d.*, p.name as product_name FROM debts d LEFT JOIN products p ON d.product_id = p.id WHERE d.student_id = ?");
$stmt->execute([$student_id]);
$debts = $stmt->fetchAll();

// Borçları gruplandır
$paid = array_filter($debts, fn($d) => $d['status'] == 1);
$unpaid = array_filter($debts, fn($d) => $d['status'] == 0);

$photo = $student['photo_path'] ? "../photos/" . htmlspecialchars($student['photo_path']) : "../assets/default-avatar.png";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($student['name']) ?> - Hesap</title>
    <link rel="stylesheet" href="../assets/style.css?v=3.1">
</head>
<body>

<div class="student-container">
    <!-- Fotoğraf -->
    <img src="<?= $photo ?>" alt="Öğrenci Fotoğrafı" class="student-photo" />

    <h1><?= htmlspecialchars($student['name']) ?></h1>
    <a href="edit_profile.php?student_id=<?= $student_id ?>" class="button-group edit-profile-btn">Profili Düzenle</a>

    <p><strong>Veli:</strong> <?= htmlspecialchars($student['guardian_name']) ?></p>
    <p><strong>İletişim:</strong> <?= htmlspecialchars($student['contact_info']) ?></p>
    <p><strong>Kurs Başlangıç Tarihi:</strong> <?= $student['course_start'] ?></p>
    <p><strong>Çalışma Günü:</strong> <?= htmlspecialchars($student['course_day']) ?> - <?= htmlspecialchars($student['group_name']) ?></p>
    <p><strong>Toplam Borç:</strong> <?= $student['total_debt'] ?> ₺</p>

    <h2>Ödenen Borçlar</h2>
    <ul>
        <?php if (count($paid) === 0): ?>
            <li class="paid">Ödenmiş borç bulunmamaktadır.</li>
        <?php else: ?>
            <?php foreach ($paid as $d): ?>
                <li class="paid"><?= htmlspecialchars($d['description'] ?? $d['product_name']) ?> - <?= $d['price'] ?> ₺</li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <h2>Ödenecek Borçlar</h2>
    <ul>
        <?php if (count($unpaid) === 0): ?>
            <li class="unpaid">Ödenecek borç bulunmamaktadır.</li>
        <?php else: ?>
            <?php foreach ($unpaid as $d): ?>
                <li class="unpaid"><?= htmlspecialchars($d['description'] ?? $d['product_name']) ?> - <?= $d['price'] ?> ₺</li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <a class="back-link" href="index.php">← Ana Sayfaya Dön</a>
</div>

</body>
</html>
