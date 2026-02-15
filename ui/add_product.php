<?php
session_start();
require "../includes/db.php";

if (!isset($_GET['student_id'])) {
    die("Geçersiz öğrenci ID.");
}
$student_id = (int)$_GET['student_id'];

$search = trim($_GET['search'] ?? '');

try {
    if ($search !== '') {
        $stmt = $db->prepare("SELECT * FROM products WHERE LOWER(name) LIKE :search ORDER BY name");
        $stmt->execute(['search' => '%' . strtolower($search) . '%']);
    } else {
        $stmt = $db->prepare("SELECT * FROM products ORDER BY name");
        $stmt->execute();
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $product = $db->prepare("SELECT * FROM products WHERE id = ?");
    $product->execute([$product_id]);
    $p = $product->fetch();

    if ($p) {
        $price = $p['price'];
        $db->beginTransaction();
        $db->prepare("INSERT INTO purchased_products (student_id, product_id, price, purchase_date, status) VALUES (?, ?, ?, CURDATE(), 0)")
            ->execute([$student_id, $product_id, $price]);

        $db->prepare("UPDATE students SET total_debt = total_debt + ? WHERE id = ?")
            ->execute([$price, $student_id]);

        $db->commit();
        $_SESSION['message'] = "Ürün başarıyla eklendi.";
        header("Location: student.php?student_id=$student_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ürün Ekle</title>
    <link rel="stylesheet" href="../assets/style.css?v=updated">
    <style>
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; margin-bottom: 30px; }
        .search-bar input { width: 100%; padding: 10px; margin-bottom: 20px; font-size: 16px; }
        .product-list .product-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .product-list form { display: inline; }
        .product-list button { padding: 6px 12px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .product-list button:hover { background: #45a049; }
        .back-link { display: block; margin-top: 30px; text-align: center; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('product-search');
        const productCards = document.querySelectorAll('.product-card');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            productCards.forEach(card => {
                const name = card.getAttribute('data-name').toLowerCase();
                if (query === '' || name.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
</script>
</head>
<body>
<div class="container">
    <h1>Ürün Ekle</h1>

    <form method="get" action="add_product.php" class="search-bar">
        <input type="hidden" name="student_id" value="<?= $student_id ?>">
        <input type="text" name="search" placeholder="Ürün adıyla ara..." value="<?= htmlspecialchars($search) ?>">
    </form>

    <div class="product-list">
        <?php if (empty($products)): ?>
            <p>Ürün bulunamadı.</p>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
    <div class="product-item product-card" data-name="<?= htmlspecialchars($product['name']) ?>">
        <span><?= htmlspecialchars($product['name']) ?> - <?= number_format($product['price'], 2) ?> ₺</span>
        <form method="post">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <button type="submit">Ekle</button>
        </form>
    </div>
<?php endforeach; ?>

        <?php endif; ?>
    </div>

    <a href="student.php?student_id=<?= $student_id ?>" class="back-link">← Öğrenciye Geri Dön</a>
</div>
</body>
</html>