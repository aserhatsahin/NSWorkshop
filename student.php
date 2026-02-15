<?php
session_start();
require "db.php";
require "apply_monthly_fees.php";

if (!isset($_GET['student_id'])) { 
    die("ID yok"); 
}
$student_id = (int)$_GET['student_id'];

// Ödeme işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_amount'])) {
    $pay = (float)$_POST['payment_amount'];
    if ($pay > 0) {
        try {
            $db->beginTransaction();

            // Kurs ücretleri ve ürün borçlarını tek bir fonksiyon ile işleme alalım
            function payDebts($db, $student_id, &$pay, $table, $orderBy) {
                $stmt = $db->prepare("SELECT * FROM $table WHERE student_id = ? AND status = 0 ORDER BY $orderBy");
                $stmt->execute([$student_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $item) {
                    if ($pay <= 0) break;
                    $due = $item['price'];
                    if ($pay >= $due) {
                        $db->prepare("UPDATE $table SET price = 0, status = 1 WHERE id = ?")->execute([$item['id']]);
                        $pay -= $due;
                    } else {
                        $newPrice = $due - $pay;
                        $db->prepare("UPDATE $table SET price = ?, status = 0 WHERE id = ?")->execute([$newPrice, $item['id']]);
                        $pay = 0;
                    }
                }
            }

            payDebts($db, $student_id, $pay, "course_fees", "date_start");
            if ($pay > 0) {
                payDebts($db, $student_id, $pay, "purchased_products", "purchase_date");
            }

            $paidAmount = (float)$_POST['payment_amount'] - $pay;
            $db->prepare("UPDATE students SET total_debt = total_debt - ? WHERE id = ?")->execute([$paidAmount, $student_id]);

            $db->commit();

            $_SESSION['message'] = "Ödeme başarıyla işlendi.";
            header("Location: student.php?student_id=$student_id");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['message'] = "Ödeme sırasında hata oluştu: " . $e->getMessage();
        }
    }
}

// Öğrenci bilgisi + fotoğraf
$stmt = $db->prepare("
    SELECT s.*, cg.course_day, cg.group_name, sp.photo_path
    FROM students s
    LEFT JOIN course_groups cg ON s.group_id = cg.id
    LEFT JOIN student_photos sp ON s.id = sp.student_id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
if (!$student) die("Öğrenci bulunamadı.");

// Kurs ücretleri
$feesStmt = $db->prepare("SELECT * FROM course_fees WHERE student_id=? ORDER BY date_start");
$feesStmt->execute([$student_id]);
$fees = $feesStmt->fetchAll(PDO::FETCH_ASSOC);

// Ürünler
$prodStmt = $db->prepare("
    SELECT pp.*, pr.name AS product_name
    FROM purchased_products pp 
    JOIN products pr ON pp.product_id = pr.id
    WHERE pp.student_id = ?
");
$prodStmt->execute([$student_id]);
$prods = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

// Hesaplamalar
$totalFees = array_sum(array_column($fees, 'price'));
$totalProds = array_sum(array_column($prods, 'price'));
$totalDebt = $student['total_debt'];
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$photo = $student['photo_path'] ? "./photos/" . htmlspecialchars($student['photo_path']) : "assets/default-avatar.png";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = (int)$_POST['is_active'];
    $stmt = $db->prepare("UPDATE students SET is_active = ? WHERE id = ?");
    $stmt->execute([$newStatus, $student_id]);
    $_SESSION['message'] = "Öğrenci durumu güncellendi.";
    header("Location: student.php?student_id=$student_id");
    exit;
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($student['name']) ?> - Hesap</title>
    <link rel="stylesheet" href="style.css?v=updated">
    <style>
        .section-box { padding:20px; background:#fff; margin-bottom:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
        .section-box h2 { margin-top:0; }
        .list-item { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #eee;}
        .payment-form { margin-top:10px; text-align:center;}
        .payment-form input { width:120px; padding:8px; }
        .payment-form button { padding:8px 12px; margin-left:6px;}
        .student-photo {
    width: 180px;
    height: 180px;
    object-fit: cover;
    border-radius: 50%;
    margin: 0 auto 15px;
    display: block;
}

        .button-group { display:inline-block; padding:6px 12px; background:#007BFF; color:#fff; border-radius:4px; text-decoration:none; }
        .button-group:hover { background:#0056b3; }
        .back-link { display:block; margin-top:20px; text-align:center; color:#333; text-decoration:none; }
        .back-link:hover { text-decoration:underline; }
    </style>
</head>
<body>
<div class="student-container">
    <?php if ($message): ?>
        <p style="color:green; text-align:center"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    
    <img src="<?= $photo ?>" alt="Öğrenci Fotoğrafı" class="student-photo" />
    <h1><?= htmlspecialchars($student['name']) ?></h1>
    <a href="edit_profile.php?student_id=<?= $student_id ?>" class="button-group edit-profile-btn">Profili Düzenle</a>
    <p><strong>Veli:</strong> <?= htmlspecialchars($student['guardian_name']) ?></p>
    <p><strong>İletişim:</strong> <?= htmlspecialchars($student['contact_info']) ?></p>
    <p><strong>Gün/Grup:</strong> <?= htmlspecialchars($student['course_day']) ?> – <?= htmlspecialchars($student['group_name']) ?></p>
    <p><strong>Kurs Kayit Tarihi:</strong> <?= htmlspecialchars($student['course_start']) ?></p>
    <p><strong>Toplam Borç:</strong> <span style="color:red"><?= number_format($totalDebt, 2) ?> ₺</span></p>
    <p><strong>Durumu:</strong> <?= $student['is_active'] ? 'Aktif' : 'Pasif' ?></p>
    <hr>

    <div class="section-box">
        <h2>Kurs Ücretleri (Toplam Kalan: <?= number_format($totalFees, 2) ?> ₺)</h2>
        <?php if (empty($fees)): ?>
            <p>Henüz kurs ücreti yok.</p>
        <?php else: ?>
            <?php foreach ($fees as $f): ?>
                <div class="list-item">
                    <span><?= htmlspecialchars($f['date_start']) ?> → <?= htmlspecialchars($f['date_end']) ?></span>
                    <span><?= number_format($f['price'], 2) ?> ₺ (<?= $f['status'] ? 'Ödendi' : 'Kalan' ?>)</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section-box">
        <h2>Alınan Ürünler (Toplam Kalan: <?= number_format($totalProds, 2) ?> ₺)</h2>
        <?php if (empty($prods)): ?>
            <p>Ürün alınmamış.</p>
        <?php else: ?>
            <?php foreach ($prods as $p): ?>
                <div class="list-item">
                    <span><?= htmlspecialchars($p['product_name']) ?> (<?= htmlspecialchars($p['purchase_date']) ?>)</span>
                    <span><?= number_format($p['price'], 2) ?> ₺ (<?= $p['status'] ? 'Ödendi' : 'Kalan' ?>)</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="payment-form">
        <form method="post">
            <label>Ödeme Tutarı: <input type="number" name="payment_amount" min="1" step="0.01" required> ₺</label>
            <button type="submit">Öde</button>
        </form>
    </div>

    <div style="text-align:center; margin-top:20px;">
        <a href="add_product.php?student_id=<?= $student_id ?>" class="button-group">Ürün Ekle</a>
    </div>

    <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
</div>
</body>
</html>
