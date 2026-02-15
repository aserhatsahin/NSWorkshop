<?php
require 'vendor/autoload.php'; // Composer autoload
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
require "../includes/db.php";

$days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

// Grup verileri al
$groupStmt = $db->prepare("
    SELECT cg.id as group_id, cg.group_name, cg.course_day, COUNT(s.id) as student_count
    FROM course_groups cg
    LEFT JOIN students s ON s.group_id = cg.id
    GROUP BY cg.id
    ORDER BY FIELD(cg.course_day, 'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'), cg.group_name
");
$groupStmt->execute();
$allGroups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

$groupsByDay = [];
foreach ($allGroups as $group) {
    $groupsByDay[$group['course_day']][] = $group;
}

// HTML çıktı oluştur
ob_start();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Ders Programı PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1, h2 { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #eee; }
        .day-section { page-break-after: always; }
    </style>
</head>
<body>
    <h1>Ders Programı</h1>

    <?php foreach ($days as $day): ?>
        <div class="day-section">
            <h2><?= $day ?></h2>

            <?php
            $dayGroups = $groupsByDay[$day] ?? [];
            $dayTotal = array_sum(array_column($dayGroups, 'student_count'));
            ?>
            <p><strong>Toplam Öğrenci Sayısı: <?= $dayTotal ?></strong></p>

            <?php if (empty($dayGroups)): ?>
                <p>Bu güne ait grup bulunmamaktadır.</p>
            <?php else: ?>
                <?php foreach ($dayGroups as $group): ?>
                    <h3><?= htmlspecialchars($group['group_name']) ?> (<?= $group['student_count'] ?> öğrenci)</h3>
                    <?php
                    $stmt = $db->prepare("SELECT s.name FROM students s WHERE s.group_id = ?");
                    $stmt->execute([$group['group_id']]);
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (empty($students)): ?>
                        <p>Bu grupta öğrenci yok.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Öğrenci Adı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</body>
</html>

<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("ders_programi.pdf", ["Attachment" => true]);
exit;
