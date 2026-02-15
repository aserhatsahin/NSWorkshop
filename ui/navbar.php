<?php
// Get current page name to set active class
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="nav-with-logo">
    <div class="branding">
        <img src="../assets/neriman.jpeg" alt="Logo" class="logo-icon" />
        <span class="site-title">Neriman Şahin Resim Atölyesi</span>
    </div>
    <div class="nav-links">
        <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Ana Sayfa</a>
        <a href="atolye_hesap.php" class="<?= $current_page == 'atolye_hesap.php' ? 'active' : '' ?>">Atölye Hesap</a>
        <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>">Ürün Listesi</a>
        <a href="total_debt.php" class="<?= $current_page == 'total_debt.php' ? 'active' : '' ?>">Toplam Borçlar</a>
        <a href="schedule.php" class="<?= $current_page == 'schedule.php' ? 'active' : '' ?>">Ders Programı</a>
        <a href="student_add.php" class="<?= $current_page == 'student_add.php' ? 'active' : '' ?>">Yeni Kayıt</a>
    </div>
</nav>
