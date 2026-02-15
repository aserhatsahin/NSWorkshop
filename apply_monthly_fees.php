<?php
require "db.php";

try {
    $today = new DateTime();
    //$today = new DateTime("2025-10-01"); // test
    $todayStr = $today->format("Y-m-d");

    // Tüm aktif öğrencileri çek
    $stmt = $db->query("SELECT id, course_start, active_since, monthly_fee, total_debt FROM students WHERE is_active = 1");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as $student) {
        $studentId = $student['id'];
        $startDate = new DateTime($student['course_start']);
        $activeSince = $student['active_since'] ? new DateTime($student['active_since']) : $startDate;
        $monthlyFee = (float)$student['monthly_fee'];

        if ($monthlyFee <= 0) continue;

        // Her ayı kontrol et
        $feeDate = clone $startDate;
        while ($feeDate <= $today) {
            // Aktif olmadan önceki ayları atla
            if ($feeDate < $activeSince) {
                $feeDate->modify("+1 month");
                continue;
            }

            $monthStart = $feeDate->format("Y-m-d");
            $monthEnd = $feeDate->modify("+1 month -1 day")->format("Y-m-d");

            // Bu ay için course_fees tablosunda kayıt var mı?
            $check = $db->prepare("SELECT COUNT(*) FROM course_fees WHERE student_id = ? AND date_start = ?");
            $check->execute([$studentId, $monthStart]);
            $exists = $check->fetchColumn();

            if (!$exists) {
                // course_fees kaydı oluştur
                $insert = $db->prepare("INSERT INTO course_fees (student_id, date_start, date_end, price, status) VALUES (?, ?, ?, ?, 0)");
                $insert->execute([$studentId, $monthStart, $monthEnd, $monthlyFee]);

                // total_debt güncelle
                $updateDebt = $db->prepare("UPDATE students SET total_debt = total_debt + ? WHERE id = ?");
                $updateDebt->execute([$monthlyFee, $studentId]);
            }

            $feeDate = new DateTime($monthStart);
            $feeDate->modify("+1 month");
        }
    }

    //echo "Ücretler başarıyla eklendi.";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
