<?php
session_start();
require "db.php";

// Hata raporlamayı aç (geliştirme ortamı için)
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

// Tüm grupları ve günlerini al
$groupStmt = $db->query("SELECT id, course_day, group_name FROM course_groups ORDER BY 
    FIELD(course_day, 'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'),
    group_name");
$groups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

// Grupları günlere göre ayır
$groupsByDay = [];
foreach ($groups as $group) {
    $groupsByDay[$group['course_day']][] = $group;
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['name', 'guardian_name', 'contact_info', 'course_start', 'monthly_fee', 'group_id'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $message = "Lütfen tüm alanları doldurunuz.";
            break;
        }
    }

    if (!$message) {
        try {
            $stmt = $db->prepare("INSERT INTO students (name, guardian_name, contact_info, course_start, monthly_fee, total_debt, group_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

            $result = $stmt->execute([
                $_POST['name'],
                $_POST['guardian_name'],
                $_POST['contact_info'],
                $_POST['course_start'],
                $_POST['monthly_fee'],
                0, // Varsayılan borç
                $_POST['group_id']
            ]);

            if ($result) {
                $_SESSION['message'] = "Öğrenci başarıyla eklendi.";
                header("Location: index.php");
                exit;
            } else {
                $message = "Kayıt başarısız: " . implode(", ", $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            $message = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Yeni Öğrenci Kaydı</title>
    <link rel="stylesheet" href="style.css?v=4" />
</head>
<body>

<div class="container">
    <h1>Yeni Öğrenci Kaydı</h1>

    <?php if ($message): ?>
        <p style="color: red; text-align: center;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post" class="student-form">
        <label for="name">Öğrenci Adı:</label>
        <input type="text" id="name" name="name" required>

        <label for="guardian_name">Veli Adı:</label>
        <input type="text" id="guardian_name" name="guardian_name" required>

        <label for="contact_info">İletişim Bilgisi:</label>
        <input type="text" id="contact_info" name="contact_info" required>

        <label for="course_start">Kurs Başlangıç Tarihi:</label>
        <input type="date" id="course_start" name="course_start" required>

        <label for="monthly_fee">Aylık Ücret (₺):</label>
        <input type="number" id="monthly_fee" name="monthly_fee" min="0" step="0.01" required>

        <label for="group_id">Kurs Günü ve Grubu:</label>
        <select id="group_id" name="group_id" required>
            <option value="">-- Gün ve Grup Seçiniz --</option>
            <?php foreach ($groupsByDay as $day => $groupList): ?>
                <optgroup label="<?= $day ?>">
                    <?php foreach ($groupList as $group): ?>
                        <option value="<?= $group['id'] ?>"><?= $group['group_name'] ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-submit">Kaydet</button>
    </form>

    <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
</div>

</body>
</html>
