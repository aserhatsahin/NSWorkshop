<?php
require "../includes/db.php";

// Increase execution time for this script
set_time_limit(300);

try {
    $today = new DateTime();
    //$today = new DateTime("2025-10-01"); // test
    $todayStr = $today->format("Y-m-d");

    // Tüm aktif öğrencileri çek
    $stmt = $db->query("SELECT id, course_start, active_since, monthly_fee, total_debt FROM students WHERE is_active = 1");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare statements outside the loop
    $stmtExistingFees = $db->prepare("SELECT date_start FROM course_fees WHERE student_id = ?");
    $insert = $db->prepare("INSERT INTO course_fees (student_id, date_start, date_end, price, status) VALUES (?, ?, ?, ?, 0)");
    $updateDebt = $db->prepare("UPDATE students SET total_debt = total_debt + ? WHERE id = ?");

    $db->beginTransaction();

    foreach ($students as $student) {
        $studentId = $student['id'];
        $startDate = new DateTime($student['course_start']);
        $activeSince = $student['active_since'] ? new DateTime($student['active_since']) : $startDate;
        $monthlyFee = (float)$student['monthly_fee'];

        if ($monthlyFee <= 0) continue;

        // Fetch all existing fee dates for this student at once
        $stmtExistingFees->execute([$studentId]);
        $existingDates = $stmtExistingFees->fetchAll(PDO::FETCH_COLUMN);
        
        // Optimizing lookup
        $existingDatesMap = array_flip($existingDates);

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

            // Check against in-memory map instead of DB query
            if (!isset($existingDatesMap[$monthStart])) {
                // course_fees kaydı oluştur
                $insert->execute([$studentId, $monthStart, $monthEnd, $monthlyFee]);

                // total_debt güncelle
                $updateDebt->execute([$monthlyFee, $studentId]);
            }

            $feeDate = new DateTime($monthStart);
            $feeDate->modify("+1 month");
        }
    }

    $db->commit();
    //echo "Ücretler başarıyla eklendi.";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Hata: " . $e->getMessage();
}
