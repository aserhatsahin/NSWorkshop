<?php
session_start();
require 'db.php';

// Kurs borçları
$feeStmt = $db->prepare("
  SELECT s.id AS student_id, s.name,
         cf.date_start, cf.date_end, cf.price
  FROM course_fees cf
  JOIN students s ON cf.student_id = s.id
  WHERE cf.status = 0
  ORDER BY s.name, cf.date_start
");
$feeStmt->execute();
$fees = $feeStmt->fetchAll(PDO::FETCH_ASSOC);
$totalFees = array_sum(array_column($fees, 'price'));

// Ürün borçları
$prodStmt = $db->prepare("
  SELECT s.id AS student_id, s.name,
         pp.purchase_date, pr.name AS product_name,
         pp.price
  FROM purchased_products pp
  JOIN students s ON pp.student_id = s.id
  JOIN products pr ON pp.product_id = pr.id
  WHERE pp.status = 0
  ORDER BY s.name, pp.purchase_date
");
$prodStmt->execute();
$prods = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
$totalProds = array_sum(array_column($prods, 'price'));

$grandTotal = $totalFees + $totalProds;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Toplam Borçlar</title>
  <link rel="stylesheet" href="style.css?v=updated">
  <style>
    .container { max-width: 900px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
    h1 { text-align:center; margin-bottom:20px; }
    .summary { font-size: 1.2em; margin-bottom: 30px; text-align: center; color: #c0392b; }
    .section { margin-bottom:40px; }
    .section h2 { border-bottom:2px solid #1abc9c; padding-bottom:6px; color:#1abc9c; }
    .list-item { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #eee; }
    .list-item span:first-child { flex:1; }
    .list-item span:last-child { font-weight:bold; }
    .total-row { text-align:right; font-weight:bold; margin-top:10px; color:#2c3e50; }
    .back-link { display:block; margin-top:20px; text-align:center; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Toplam Borçlar</h1>
    <div class="summary">
      Genel Toplam Borç: <strong><?= number_format($grandTotal,2) ?> ₺</strong>
    </div>

    <?php if ($fees): ?>
    <div class="section">
      <h2>Kurs Ücretlerinden Kalan Borçlar</h2>
      <?php foreach ($fees as $f): ?>
        <div class="list-item">
          <span><?= htmlspecialchars($f['name']) ?> — <?= $f['date_start'] ?> → <?= $f['date_end'] ?></span>
          <span><?= number_format($f['price'],2) ?> ₺</span>
        </div>
      <?php endforeach; ?>
      <div class="total-row">Bu listedeki toplam borç: <?= number_format($totalFees, 2) ?> ₺</div>
    </div>
    <?php endif; ?>

    <?php if ($prods): ?>
    <div class="section">
      <h2>Alınan Ürünlere Ait Kalan Borçlar</h2>
      <?php foreach ($prods as $p): ?>
        <div class="list-item">
          <span><?= htmlspecialchars($p['name']) ?> — <?= htmlspecialchars($p['product_name']) ?> (<?= $p['purchase_date'] ?>)</span>
          <span><?= number_format($p['price'],2) ?> ₺</span>
        </div>
      <?php endforeach; ?>
      <div class="total-row">Bu listedeki toplam borç: <?= number_format($totalProds, 2) ?> ₺</div>
    </div>
    <?php endif; ?>

    <?php if (empty($fees) && empty($prods)): ?>
      <p style="text-align:center; color:#555; font-style:italic;">Tüm borçlar ödenmiş.</p>
    <?php endif; ?>

    <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
  </div>
</body>
</html>
