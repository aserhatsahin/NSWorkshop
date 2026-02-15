<?php
session_start();
require "../includes/db.php";

// CSRF token oluştur (varsa kullan, yoksa oluştur)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gün listesi
$days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

// Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['message'] = "Geçersiz istek (CSRF doğrulaması başarısız).";
        $_SESSION['message_type'] = "error";
        header("Location: schedule.php");
        exit;
    }

    if (isset($_POST['student_id'])) {
        $studentId = (int)$_POST['student_id'];

        // Öğrencinin varlığını kontrol et
        $studentCheckStmt = $db->prepare("SELECT s.id, s.group_id, cg.course_day FROM students s JOIN course_groups cg ON s.group_id = cg.id WHERE s.id = ?");
        $studentCheckStmt->execute([$studentId]);
        $studentData = $studentCheckStmt->fetch(PDO::FETCH_ASSOC);

        if (!$studentData) {
            $_SESSION['message'] = "Öğrenci bulunamadı.";
            $_SESSION['message_type'] = "error";
            header("Location: schedule.php");
            exit;
        }

        // Grup değişimi
        if (isset($_POST['new_group_id'])) {
            $newGroupId = (int)$_POST['new_group_id'];

            // Yeni grup var mı kontrolü
            $groupCheckStmt = $db->prepare("SELECT id FROM course_groups WHERE id = ?");
            $groupCheckStmt->execute([$newGroupId]);
            if ($groupCheckStmt->fetch()) {
                $stmt = $db->prepare("UPDATE students SET group_id = ? WHERE id = ?");
                $stmt->execute([$newGroupId, $studentId]);
                $_SESSION['message'] = "Grup güncellemesi başarılı!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Geçersiz grup seçimi!";
                $_SESSION['message_type'] = "error";
            }
        }
        // Gün değişimi
        elseif (isset($_POST['new_day'])) {
            $newDay = $_POST['new_day'];

            if (in_array($newDay, $days)) {
                // Günün Grup 1'ini bul
                $stmt = $db->prepare("SELECT id FROM course_groups WHERE course_day = ? AND group_name = 'Grup 1' LIMIT 1");
                $stmt->execute([$newDay]);
                $group = $stmt->fetch();

                if ($group) {
                    $stmt = $db->prepare("UPDATE students SET group_id = ? WHERE id = ?");
                    $stmt->execute([$group['id'], $studentId]);
                    $_SESSION['message'] = "Gün güncellemesi başarılı (Grup 1 olarak ayarlandı)!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Seçilen gün için Grup 1 bulunamadı!";
                    $_SESSION['message_type'] = "error";
                }
            } else {
                $_SESSION['message'] = "Geçersiz gün seçimi!";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Geçersiz veri!";
            $_SESSION['message_type'] = "error";
        }

        header("Location: schedule.php");
        exit;
    }
}

// Tüm grup verilerini al
$groupStmt = $db->prepare("
    SELECT cg.id as group_id, cg.group_name, cg.course_day, COUNT(s.id) as student_count
    FROM course_groups cg
    LEFT JOIN students s ON s.group_id = cg.id
    GROUP BY cg.id
    ORDER BY FIELD(cg.course_day, 'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'), cg.group_name
");
$groupStmt->execute();
$allGroups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

// Grupları günlere göre ayır
$groupsByDay = [];
foreach ($allGroups as $group) {
    $groupsByDay[$group['course_day']][] = $group;
}

// Tüm öğrencileri tek seferde çek ve gruplandır
$studentStmt = $db->prepare("SELECT s.*, cg.course_day FROM students s JOIN course_groups cg ON s.group_id = cg.id");
$studentStmt->execute();
$allStudents = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

$studentsByGroup = [];
foreach ($allStudents as $student) {
    $studentsByGroup[$student['group_id']][] = $student;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Ders Programı</title>
    <link rel="stylesheet" href="../assets/style.css?v=3" />
    <style>
        .success-msg { color: green; text-align: center; }
        .error-msg { color: red; text-align: center; }
        .container { max-width: 900px; margin: auto; padding: 20px; }
        .group-box { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .student-list { list-style: none; padding-left: 0; }
        .student-item { margin-bottom: 8px; }
        .btn-download-pdf { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background: #007BFF; color: white; text-decoration: none; border-radius: 4px; }
        .back-link { display: inline-block; margin-top: 30px; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h1>Ders Programı</h1>

    <?php if (!empty($_SESSION['message'])): ?>
        <p class="<?= ($_SESSION['message_type'] ?? 'success') === 'error' ? 'error-msg' : 'success-msg' ?>">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </p>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <a href="download_schedule_pdf.php" target="_blank" class="btn-download-pdf">Ders Programını PDF Olarak İndir</a>

    <?php foreach ($days as $day): ?>
        <section class="day-section">
            <h2><?= htmlspecialchars($day) ?></h2>

            <?php
            $dayGroups = $groupsByDay[$day] ?? [];
            $dayTotal = array_sum(array_column($dayGroups, 'student_count'));
            ?>
            <p><strong>Toplam Öğrenci Sayısı: <?= $dayTotal ?></strong></p>

            <?php if ($dayTotal > 0): ?>
                <?php foreach ($dayGroups as $group): ?>
                    <?php
                    $students = $studentsByGroup[$group['group_id']] ?? [];
                    // Sadece öğrenci varsa göster
                    if (count($students) === 0) continue;
                    ?>
                    <div class="group-box">
                        <h3><?= htmlspecialchars($group['group_name']) ?> - <?= $group['student_count'] ?> öğrenci</h3>

                        <ul class="student-list">
                            <?php foreach ($students as $student): ?>
                                <li class="student-item">
                                    <span><?= htmlspecialchars($student['name']) ?></span>

                                    <!-- Grup değiştirme -->
                                    <form method="post" style="display:inline-block; margin-left:10px;">
                                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                        <select name="new_group_id" onchange="this.form.submit()">
                                            <?php foreach ($groupsByDay[$student['course_day']] ?? [] as $optionGroup): ?>
                                                <option value="<?= $optionGroup['group_id'] ?>"
                                                    <?= $optionGroup['group_id'] == $student['group_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($optionGroup['group_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>

                                    <!-- Gün değiştirme -->
                                    <form method="post" style="display:inline-block; margin-left:10px;">
                                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                        <select name="new_day" onchange="this.form.submit()">
                                            <?php foreach ($days as $d): ?>
                                                <option value="<?= htmlspecialchars($d) ?>" <?= $d === $student['course_day'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($d) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
</div>

</body>
</html>
