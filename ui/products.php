<?php
session_start();
require "../includes/db.php";

// Fiyat güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $product_id = (int) $_POST['product_id'];
    $new_price = (float) $_POST['new_price'];

    $stmt = $db->prepare("UPDATE products SET price = ? WHERE id = ?");
    $stmt->execute([$new_price, $product_id]);
    header("Location: products.php");
    exit;
}

// Yeni ürün ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $price = (float) $_POST['price'];

    if ($name && $type && $price > 0) {
        $stmt = $db->prepare("INSERT INTO products (name, type, price) VALUES (?, ?, ?)");
        $stmt->execute([$name, $type, $price]);
        header("Location: products.php");
        exit;
    }
}

// Ürünleri çek
$stmt = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 5");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ürün Listesi</title>
    <link rel="stylesheet" href="../assets/style.css?v=4">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
<div class="product-container">
    <h1>Ürün Listesi</h1>

    <div class="search-bar">
        <input type="text" id="search" placeholder="Ürün ara...">
    </div>

    <div class="product-list" id="product-list">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><strong>Tür:</strong> <?= htmlspecialchars($product['type']) ?></p>
                <p><strong>Fiyat:</strong> <?= number_format($product['price'], 2) ?> ₺</p>
                <form method="post">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="number" name="new_price" step="10" min="0" value="<?= htmlspecialchars($product['price']) ?>" required>
                    <button type="submit" name="update_price">Fiyatı Güncelle</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="add-product-form">
        <h2>Yeni Ürün Ekle</h2>
        <form method="post">
            <input type="text" name="name" placeholder="Ürün Adı" required>
            <input type="text" name="type" placeholder="Ürün Türü" required>
            <input type="number" name="price" step="0.01" min="0" placeholder="Fiyat (₺)" required>
            <button type="submit" name="add_product">Ürünü Ekle</button>
        </form>
    </div>
    <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
</div>
</div>

<script>
    document.getElementById('search').addEventListener('input', function () {
        const query = this.value;

        fetch('../logic/search_products.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const productList = document.getElementById('product-list');
                productList.innerHTML = '';
                
                if (data.length === 0) {
                    productList.innerHTML = '<p>Ürün bulunamadı.</p>';
                    return;
                }

                data.forEach(product => {
                    const card = document.createElement('div');
                    card.className = 'product-card';
                    card.innerHTML = `
                        <h3>${product.name}</h3>
                        <p><strong>Tür:</strong> ${product.type}</p>
                        <p><strong>Fiyat:</strong> ${parseFloat(product.price).toFixed(2)} ₺</p>
                        <form method="post">
                            <input type="hidden" name="product_id" value="${product.id}">
                            <input type="number" name="new_price" step="0.01" min="0" value="${product.price}" required>
                            <button type="submit" name="update_price">Fiyatı Güncelle</button>
                        </form>
                    `;
                    productList.appendChild(card);
                });
            })
            .catch(error => console.error('Error:', error));
    });
</script>
</body>
</html>
