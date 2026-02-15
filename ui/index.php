<?php
include '../includes/db.php';

$search = trim($_GET['q'] ?? '');
$searchParam = '%' . strtolower($search) . '%';

try {
    $stmt = $db->prepare("
        SELECT s.*, sp.photo_path
        FROM students s
        LEFT JOIN student_photos sp ON s.id = sp.student_id
        WHERE (:search = '%%' OR LOWER(s.name) LIKE :search)
        ORDER BY s.name
        LIMIT 6
    ");
    $stmt->execute(['search' => $searchParam]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Veritabanı hatası: " . $e->getMessage();
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Resim Atölyesi - Ana Sayfa</title>
    <link rel="stylesheet" href="../assets/style.css?v=3.1" />
    <script>
        let debounceTimer;
        async function liveSearch(e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                const q = e.target.value.trim();
                const container = document.getElementById("student-list");
                const url = `../logic/search_students.php?q=${encodeURIComponent(q)}`;

                try {
                    const res = await fetch(url);
                    const results = await res.json();

                    container.innerHTML = '';

                    if (results.length === 0) {
                        container.innerHTML = `<p style="text-align:center; margin-top: 40px; color: #999;">Öğrenci bulunamadı.</p>`;
                        return;
                    }

                    results.forEach(student => {
                        const photo = student.photo_path ? `../photos/${student.photo_path}` : '../assets/default-avatar.png';

                        const card = document.createElement('div');
                        card.className = 'card';
                        card.innerHTML = `
                            <img src="${photo}" alt="Fotoğraf" class="student-photo" />
                            <h3>${student.name}</h3>
                            <p><strong>Veli:</strong> ${student.guardian_name || '-'}</p>
                            <p><strong>İletişim:</strong> ${student.contact_info || '-'}</p>
                            <p><strong>Kurs Başlangıç:</strong> ${student.course_start || '-'}</p>
                            <a href="student.php?student_id=${student.id}">Hesaba Git</a>
                        `;
                        container.appendChild(card);
                    });
                } catch {
                    container.innerHTML = `<p style="text-align:center; margin-top: 40px; color: red;">Veri alınırken hata oluştu.</p>`;
                }
            }, 300);
        }
    </script>
</head>
<body>
<nav class="nav-with-logo">
    <div class="branding">
        <img src="../assets/neriman.jpeg" alt="Logo" class="logo-icon" />
        <span class="site-title">Neriman Şahin Resim Atölyesi</span>
    </div>
    <div class="nav-links">
        <a href="index.php" class="active">Ana Sayfa</a>
        <a href="atolye_hesap.php" class="button">Atölye Hesap</a>
        <a href="products.php">Ürün Listesi</a>
        <a href="total_debt.php">Toplam Borçlar</a>
        <a href="schedule.php">Ders Programı</a>
        <a href="student_add.php">Yeni Kayıt</a>
    </div>
</nav>

<div class="container">
    <h1>Öğrenci Listesi</h1>

    <div class="search-bar">
        <input
            type="text"
            name="search"
            id="search"
            placeholder="Öğrenci Ara..."
            value="<?php echo htmlspecialchars($search); ?>"
            oninput="liveSearch(event)"
            autocomplete="off"
        />
    </div>

    <div id="student-list" class="student-list">
        <?php if (empty($students)): ?>
            <p style="text-align:center; margin-top: 40px; color: #999;">Öğrenci bulunamadı.</p>
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                <div class="card">
                    <?php
                        $photoPath = !empty($student['photo_path']) ? '../photos/' . htmlspecialchars($student['photo_path']) : '../assets/default-avatar.png';
                    ?>
                    <img src="<?php echo $photoPath; ?>" alt="Fotoğraf" class="student-photo" />
                    <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                    <p><strong>Veli:</strong> <?php echo htmlspecialchars($student['guardian_name'] ?? '-'); ?></p>
                    <p><strong>İletişim:</strong> <?php echo htmlspecialchars($student['contact_info'] ?? '-'); ?></p>
                    <p><strong>Kurs Başlangıç:</strong> <?php echo htmlspecialchars($student['course_start'] ?? '-'); ?></p>
                    <a href="student.php?student_id=<?php echo $student['id']; ?>">Hesaba Git</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
