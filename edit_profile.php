<?php
session_start();
require "db.php";

if (!isset($_GET['student_id'])) {
    die("Öğrenci ID belirtilmedi.");
}

$student_id = (int) $_GET['student_id'];

// Mevcut öğrenci bilgisi
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Öğrenci bulunamadı.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $guardian_name = $_POST['guardian_name'] ?? '';
    $contact_info = $_POST['contact_info'] ?? '';
    $course_start = $_POST['course_start'] ?? '';
    $monthly_fee = $_POST['monthly_fee'] ?? 0;
    $group_id = $_POST['group_id'] ?? null;
    $is_active = (int) ($_POST['is_active'] ?? 0);

    // Eğer pasiften aktife geçiyorsa, active_since tarihini bugünün tarihi olarak ayarla
    if ($student['is_active'] == 0 && $is_active == 1) {
        $active_since = date('Y-m-d');
    } else {
        $active_since = $student['active_since'];
    }

    $stmt = $db->prepare("UPDATE students SET name = ?, guardian_name = ?, contact_info = ?, course_start = ?, monthly_fee = ?, group_id = ?, is_active = ?, active_since = ? WHERE id = ?");
    $stmt->execute([$name, $guardian_name, $contact_info, $course_start, $monthly_fee, $group_id, $is_active, $active_since, $student_id]);

    header("Location: student.php?student_id=" . $student_id);
    exit;
}

// Grup seçeneklerini al
$stmt = $db->query("SELECT id, course_day, group_name FROM course_groups ORDER BY course_day, group_name");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Profili Düzenle - <?= htmlspecialchars($student['name']) ?></title>
    <link rel="stylesheet" href="style.css?v=3" />
</head>
<body>

<div class="container edit-profile-container">
    <h1>Profili Düzenle - <?= htmlspecialchars($student['name']) ?></h1>

    <form method="post" action="edit_profile.php?student_id=<?= $student_id ?>" class="edit-profile-form">
        <label for="name">İsim:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($student['name']) ?>" required />

        <label for="guardian_name">Veli Adı:</label>
        <input type="text" id="guardian_name" name="guardian_name" value="<?= htmlspecialchars($student['guardian_name']) ?>" required />

        <label for="contact_info">İletişim:</label>
        <input type="text" id="contact_info" name="contact_info" value="<?= htmlspecialchars($student['contact_info']) ?>" required />

        <label for="course_start">Kurs Başlangıç Tarihi:</label>
        <input type="date" id="course_start" name="course_start" value="<?= htmlspecialchars($student['course_start']) ?>" required />

        <label for="monthly_fee">Kurs Ücreti (₺):</label>
        <input type="number" id="monthly_fee" name="monthly_fee" min="0" value="<?= htmlspecialchars($student['monthly_fee']) ?>" required />

        <label for="group_id">Çalışma Günü ve Grup:</label>
        <select id="group_id" name="group_id" required>
            <option value="" disabled <?= empty($student['group_id']) ? 'selected' : '' ?>>Gün ve Grup Seçiniz</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?= $group['id'] ?>" <?= $student['group_id'] == $group['id'] ? 'selected' : '' ?>>
                    <?= $group['course_day'] ?> - <?= $group['group_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="is_active">Aktiflik Durumu:</label>
        <select id="is_active" name="is_active">
            <option value="1" <?= $student['is_active'] ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= !$student['is_active'] ? 'selected' : '' ?>>Pasif</option>
        </select>

        <button type="submit" class="btn-save">Kaydet</button>
        <a href="student.php?student_id=<?= $student_id ?>" class="back-link">← Profil Sayfasına Dön</a>
    </form>
</div>

</body>
</html>
