<?php
require "db.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q !== '') {
    $stmt = $db->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY id DESC LIMIT 5");
    $stmt->execute(['%' . $q . '%']);
} else {
    $stmt = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 5");
}

$products = $stmt->fetchAll();

foreach ($products as $product): ?>
    <div class="product-card">
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p><strong>Tür:</strong> <?= htmlspecialchars($product['type']) ?></p>
        <p><strong>Fiyat:</strong> <?= number_format($product['price'], 2) ?> ₺</p>
        <form method="post" action="products.php">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="number" name="new_price" step="0.01" min="0" required>
            <button type="submit" name="update_price">Fiyatı Güncelle</button>
        </form>
    </div>
<?php endforeach; ?>
