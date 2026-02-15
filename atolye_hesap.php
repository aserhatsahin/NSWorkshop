<?php
session_start();
require 'db.php';

// Öğrenci sayıları
$studentCountActive = $db->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
$studentCountInactive = $db->query("SELECT COUNT(*) FROM students WHERE is_active = 0")->fetchColumn();

// Aylık Kurs ücreti geliri
$total_monthly_fees = $db->query("SELECT SUM(monthly_fee) FROM students")->fetchColumn() ?: 0;

// Kurs borcu toplamı
$totalUnpaidFees = $db->query("SELECT SUM(price) FROM course_fees WHERE status = 0")->fetchColumn() ?: 0;

// Satılan ürünlerin toplam ücreti
$total_sold_products = $db->query("SELECT SUM(p.price) FROM purchased_products pp JOIN products p ON pp.product_id = p.id")->fetchColumn() ?: 0;

// Ürün borcu toplamı
$totalUnpaidProducts = $db->query("SELECT SUM(price) FROM purchased_products WHERE status = 0")->fetchColumn() ?: 0;

// Aylık kurs gelirleri
$monthlyCourse = $db->query("
    SELECT 
        DATE_FORMAT(cf.date_start, '%Y-%m') AS period, 
        SUM(s.monthly_fee) AS total
    FROM course_fees cf
    JOIN students s ON cf.student_id = s.id
    GROUP BY period
    HAVING total > 0
    ORDER BY period
")->fetchAll(PDO::FETCH_ASSOC);

// Aylık ürün satış gelirleri
$monthlyProductSales = $db->query("
    SELECT 
        DATE_FORMAT(pp.purchase_date, '%Y-%m') AS period, 
        SUM(p.price) AS total
    FROM purchased_products pp
    JOIN products p ON pp.product_id = p.id
    GROUP BY period
    HAVING total > 0
    ORDER BY period
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Atölye Hesap Özeti</title>
  <link rel="stylesheet" href="style.css?v=updated">
  <style>
    .container { max-width: 900px; margin: 40px auto; padding: 20px; background:#fff; border-radius: 10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
    h1, h2 { text-align: center; color: #1abc9c; margin-bottom: 20px; }
    .summary span { display: block; margin-bottom: 6px; font-size: 1.1em; }
    .section { margin-top:40px; }
    .list-item { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #eee; }
    .total-row { text-align:right; font-weight:bold; margin-top:10px; color:#2c3e50; }
    .back-link { display:block; margin-top:30px; text-align:center; }
  </style>
</head>
<body>
<div class="container">
  <h1>Atölye Hesap Özeti</h1>

  <div class="summary">
    <span><strong>Aktif Öğrenci Sayisi:</strong> <?= $studentCountActive ?></span>
    <span><strong>Pasif Öğrenci Sayisi:</strong> <?= $studentCountInactive ?></span>
    <span><strong>Aylık Kurs Ücretlerinden Toplam Gelir:</strong> <?= number_format($total_monthly_fees, 2) ?> ₺</span>
    <span><strong>Kurslardan Ödenmeyen Toplam Borç:</strong> <?= number_format($totalUnpaidFees, 2) ?> ₺</span>
    <span><strong>Ürün Satışlarından Toplam Gelir:</strong> <?= number_format($total_sold_products, 2) ?> ₺</span>
    <span><strong>Ürünlerden Toplam Borç:</strong> <?= number_format($totalUnpaidProducts, 2) ?> ₺</span>
  </div>

  <?php if ($monthlyCourse): ?>
    <div class="section">
      <h2>Aylık Kurs Gelirleri</h2>
      <?php foreach ($monthlyCourse as $row): ?>
        <div class="list-item">
          <span><?= date('F Y', strtotime($row['period'] . '-01')) ?></span>
          <span><?= number_format($row['total'], 2) ?> ₺</span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($monthlyProductSales): ?>
    <div class="section">
      <h2>Aylık Ürün Satış Gelirleri</h2>
      <?php foreach ($monthlyProductSales as $row): ?>
        <div class="list-item">
          <span><?= date('F Y', strtotime($row['period'] . '-01')) ?></span>
          <span><?= number_format($row['total'], 2) ?> ₺</span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
</div>
</body>
</html>
