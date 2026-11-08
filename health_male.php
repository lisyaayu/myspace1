
<?php
session_start();

require_once "config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// Ambil nama pengguna tanpa bergantung pada get_result()
$user_name = "Bestie";

$stmt = $conn->prepare("
    SELECT name
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name);

    if ($stmt->fetch() && !empty($name)) {
        $user_name = $name;
    }

    $stmt->close();
}

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Semua tujuan fitur dibuat khusus untuk halaman pria
$male_features = [
    
    [
        "url" => "fitness_tracker_male.php",
        "icon" => "fa-dumbbell",
        "color" => "blue",
        "label" => "KEBUGARAN FISIK",
        "title" => "Pelacak Kebugaran",
        "description" => "Catat latihan, pantau aktivitas fisik, dan bangun target kebugaran sesuai kemampuanmu.",
        "link" => "Mulai mencatat aktivitas"
    ],
    [
        "url" => "mens_health.php",
        "icon" => "fa-user-shield",
        "color" => "navy",
        "label" => "KESEHATAN PRIA",
        "title" => "Panduan Kesehatan Pria",
        "description" => "Pelajari kesehatan reproduksi pria, kebersihan diri, pemeriksaan rutin, dan perawatan kesehatan.",
        "link" => "Jelajahi kesehatan pria"
    ],
    [
        "url" => "healthy_habits_male.php",
        "icon" => "fa-list-check",
        "color" => "green",
        "label" => "RUTINITAS HARIAN",
        "title" => "Kebiasaan Sehat",
        "description" => "Bangun rutinitas positif, jaga konsistensi, dan kembangkan kebiasaan sehat setiap hari.",
        "link" => "Bangun kebiasaan sehat"
    ],
    [
        "url" => "nutrition_male.php",
        "icon" => "fa-carrot",
        "color" => "orange",
        "label" => "NUTRISI & HIDRASI",
        "title" => "Nutrisi dan Hidrasi",
        "description" => "Kenali gizi seimbang, kebutuhan cairan, dan pilihan makanan untuk mendukung aktivitas tubuh.",
        "link" => "Jelajahi nutrisi"
    
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kesehatan Pria | MySpace</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/health_male.css">
</head>

<body>

<div class="male-page">

    <div class="bg-decoration decoration-one"></div>
    <div class="bg-decoration decoration-two"></div>
    <div class="bg-decoration decoration-three"></div>

    <!-- TOPBAR KHUSUS MALE -->
    <header class="topbar">
        <a href="health_center.php" class="brand">
            <div class="brand-icon">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>

            <div class="brand-text">
                <h2>MySpace</h2>
                <span>ruang pribadimu</span>
            </div>
        </a>

        <a href="health_center.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Pilih Ruang Lain</span>
        </a>
    </header>

    <main class="main-content">

        <!-- HERO -->
        <section class="male-hero">
            <div class="hero-content">

                <span class="hero-label">
                    <i class="fa-solid fa-bolt"></i>
                    RUANG KESEHATAN PRIA
                </span>

                <h1>
                    Bangun kekuatanmu.
                    <span>Jaga kesehatanmu.</span>
                </h1>

                <p>
                    Halo, <?= escape($user_name); ?>!
                    Selamat datang di ruang kesehatan pribadimu.
                    Mulailah menjaga kesehatan, meningkatkan kebugaran,
                    membangun kebiasaan sehat, dan menjadi versi terbaik
                    dari dirimu sendiri.
                </p>

                <a href="#male-features" class="hero-button">
                    Jelajahi Kesehatanmu
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

                <div class="hero-note">
                    <i class="fa-solid fa-shield-heart"></i>
                    Pikiran kuat. Tubuh sehat. Hidup lebih baik.
                </div>

            </div>

            <div class="hero-art">
                <div class="art-circle">
                    <i class="fa-solid fa-dumbbell"></i>
                </div>

                <div class="art-ring ring-one"></div>
                <div class="art-ring ring-two"></div>

                <div class="art-star star-one">✦</div>
                <div class="art-star star-two">✧</div>

                <div class="art-caption">
                    <i class="fa-solid fa-bolt"></i>
                    Kemajuan lebih penting daripada kesempurnaan
                </div>
            </div>
        </section>

        <!-- PENGINGAT -->
        <section class="wellness-reminder">
            <div class="reminder-icon">
                <i class="fa-solid fa-shield-heart"></i>
            </div>

            <div class="reminder-content">
                <span>PENGINGAT HARIAN</span>

                <h3>
                    Merawat diri sendiri adalah tanda kekuatan.
                </h3>

                <p>
                    Tetap aktif, konsumsi makanan bergizi seimbang,
                    tidur yang cukup, dan jangan lupa menjaga
                    kesehatan mentalmu.
                </p>
            </div>
        </section>

        <!-- FITUR KHUSUS PRIA -->
        <section class="features-section" id="male-features">

            <div class="section-heading">
                <div>
                    <span class="section-subtitle">
                        JELAJAHI KESEHATANMU
                    </span>

                    <h2>Ruang Kesehatan Pria</h2>

                    <p>
                        Semua kebutuhan kesehatan pria dalam satu ruang.
                        Pilih fitur yang ingin kamu jelajahi dan mulailah
                        membangun gaya hidup yang lebih sehat.
                    </p>
                </div>

                <div class="heading-decoration">
                    <i class="fa-solid fa-bolt"></i>
                </div>
            </div>

            <div class="feature-grid">

                <?php foreach ($male_features as $feature): ?>
                    <a href="<?= escape($feature["url"]); ?>"
                       class="feature-card">

                        <div class="feature-icon <?= escape($feature["color"]); ?>">
                            <i class="fa-solid <?= escape($feature["icon"]); ?>"></i>
                        </div>

                        <span class="feature-label">
                            <?= escape($feature["label"]); ?>
                        </span>

                        <h3>
                            <?= escape($feature["title"]); ?>
                        </h3>

                        <p>
                            <?= escape($feature["description"]); ?>
                        </p>

                        <div class="feature-link">
                            <?= escape($feature["link"]); ?>
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>

                    </a>
                <?php endforeach; ?>

            </div>
        </section>

        <!-- KUTIPAN -->
        <section class="bottom-quote">
            <div class="quote-icon">
                <i class="fa-solid fa-shield-heart"></i>
            </div>

            <div>
                <h3>
                    Jadilah pribadi yang kuat dengan merawat dirimu sendiri.
                </h3>

                <p>
                    Kesehatan adalah investasi.
                    Setiap perubahan kecil tetap berarti.
                </p>
            </div>

            <span class="quote-decoration">✦</span>
        </section>

        <!-- FOOTER -->
        <footer class="page-footer">
            <div class="footer-icon">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>

            <p>Dibuat dengan penuh cinta oleh MySpace.</p>
            <span>Kesehatanmu. Kekuatanmu. Perjalananmu.</span>
        </footer>

    </main>
</div>

</body>
</html>
