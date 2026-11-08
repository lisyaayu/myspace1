
<?php
session_start();

require_once "config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// Ambil nama pengguna
$stmt = $conn->prepare("
    SELECT name
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$user_name = $user["name"] ?? "Bestie";
$stmt->close();

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kesehatan Perempuan | MySpace</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/health_female.css">
</head>

<body>

<div class="female-page">

    <!-- DEKORASI LATAR BELAKANG -->
    <div class="bg-decoration bg-decoration-one"></div>
    <div class="bg-decoration bg-decoration-two"></div>
    <div class="bg-decoration bg-decoration-three"></div>


    <!-- NAVIGASI -->
    <header class="topbar">

        <a href="health_center.php" class="brand">
            <div class="brand-icon">
                <i class="fa-solid fa-heart"></i>
            </div>

            <div class="brand-text">
                <h2>MySpace</h2>
                <span>ruang pribadimu</span>
            </div>
        </a>

        <a href="health_center.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Pilih Kategori Lain</span>
        </a>

    </header>


    <main class="main-content">

        <!-- BAGIAN PEMBUKA -->
        <section class="female-hero">

            <div class="hero-content">

                <span class="hero-label">
                    <i class="fa-solid fa-sparkles"></i>
                    RUANG KESEHATAN PEREMPUANMU
                </span>

                <h1>
                    Tumbuh menjadi
                    <span>versi dirimu yang lebih sehat.</span>
                </h1>

                <p>
                    Halo, <?= escape($user_name); ?>!

                    Ini adalah ruang kecil untuk mengenal tubuhmu,
                    menjaga pikiranmu, dan menghargai dirimu sendiri.

                    Kamu berhak merasa nyaman dan sehat,
                    baik secara fisik maupun emosional.
                </p>

                <a href="#female-features" class="hero-button">
                    Jelajahi Kesehatanmu
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

                <div class="hero-note">
                    <i class="fa-solid fa-heart"></i>
                    Pengingat kecil: kamu sudah melakukan yang terbaik.
                </div>

            </div>


            <div class="hero-art">

                <div class="art-circle">
                    <i class="fa-solid fa-spa"></i>
                </div>

                <div class="art-flower flower-one">✿</div>
                <div class="art-flower flower-two">✿</div>
                <div class="art-star star-one">✦</div>
                <div class="art-star star-two">✧</div>

                <div class="art-caption">
                    <i class="fa-solid fa-heart"></i>
                    Lebih mencintai diri sendiri hari ini
                </div>

            </div>

        </section>


        <!-- PENGINGAT KESEHATAN -->
        <section class="wellness-reminder">

            <div class="reminder-icon">
                <i class="fa-solid fa-heart"></i>
            </div>

            <div class="reminder-content">

                <span>SEBUAH PESAN PENUH KASIH</span>

                <h3>
                    Tubuhmu pantas mendapatkan kasih sayang,
                    bukan perbandingan.
                </h3>

                <p>
                    Dengarkan tubuhmu, beristirahatlah saat dibutuhkan,
                    dan ingat bahwa perjalanan kesehatan setiap orang
                    berbeda-beda.
                </p>

            </div>

        </section>


        <!-- BAGIAN FITUR -->
        <section class="features-section" id="female-features">

            <div class="section-heading">

                <div>
                    <span class="section-subtitle">
                        JELAJAHI KESEHATANMU
                    </span>

                    <h2>Ruang Perawatan Dirimu</h2>

                    <p>
                        Langkah kecil, kebiasaan positif, dan informasi
                        bermanfaat untuk mendukung kesehatanmu setiap hari.
                    </p>
                </div>

                <div class="heading-decoration">
                    <i class="fa-solid fa-heart"></i>
                </div>

            </div>


            <div class="feature-grid">


                <!-- KESEHATAN MENTAL -->
               


                <!-- PELACAK MENSTRUASI -->
                <a href="menstrual_tracker.php" class="feature-card">

                    <div class="feature-icon rose">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>

                    <span class="feature-label">KENALI TUBUHMU</span>

                    <h3>Pelacak Menstruasi</h3>

                    <p>
                        Catat tanggal menstruasimu dan pantau
                        riwayat siklus menstruasi dalam satu ruang pribadi.
                    </p>

                    <div class="feature-link">
                        Pantau siklusku
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>

                </a>


                <!-- TIPS KESEHATAN -->
                <a href="health_tips.php" class="feature-card">

                    <div class="feature-icon pink">
                        <i class="fa-solid fa-book-medical"></i>
                    </div>

                    <span class="feature-label">BELAJAR & BERKEMBANG</span>

                    <h3>Tips Kesehatan</h3>

                    <p>
                        Temukan informasi kesehatan yang bermanfaat
                        dan cara sederhana untuk menjaga dirimu.
                    </p>

                    <div class="feature-link">
                        Baca tips kesehatan
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>

                </a>


                <!-- KEBIASAAN SEHAT -->
                


                <!-- NUTRISI -->
                <a href="nutrition.php" class="feature-card">

                    <div class="feature-icon orange">
                        <i class="fa-solid fa-carrot"></i>
                    </div>

                    <span class="feature-label">PENUHI NUTRISI TUBUHMU</span>

                    <h3>Nutrisi & Hidrasi</h3>

                    <p>
                        Pelajari tentang makanan bergizi seimbang,
                        pilihan makanan sehat, dan pentingnya minum air putih.
                    </p>

                    <div class="feature-link">
                        Penuhi kebutuhan nutrisimu
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>

                </a>


                <!-- PERAWATAN DIRI -->
                <a href="self_care.php" class="feature-card">

                    <div class="feature-icon lavender">
                        <i class="fa-solid fa-spa"></i>
                    </div>

                    <span class="feature-label">SAYANGI DIRIMU</span>

                    <h3>Ruang Perawatan Diri</h3>

                    <p>
                        Temukan cara sederhana untuk bersantai,
                        memulihkan energi, dan meluangkan waktu untuk dirimu.
                    </p>

                    <div class="feature-link">
                        Jelajahi perawatan diri
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>

                </a>


                <!-- PELACAK KEBUGARAN -->
                <a href="fitness_tracker.php" class="feature-card">

                    <div class="feature-icon green">
                        <i class="fa-solid fa-dumbbell"></i>
                    </div>

                    <span class="feature-label">AKTIF & BUGAR</span>

                    <h3>Pelacak Kebugaran</h3>

                    <p>
                        Catat aktivitas olahraga, pantau durasi latihan,
                        dan bangun rutinitas aktif untuk menjaga kebugaran tubuhmu.
                    </p>

                    <div class="feature-link">
                        Pantau kebugaranku
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>

                </a>


            </div>

        </section>


        <!-- PESAN PENUTUP -->
        <section class="bottom-quote">

            <div class="quote-heart">
                <i class="fa-solid fa-heart"></i>
            </div>

            <div>
                <h3>Bersikaplah lembut kepada dirimu sendiri.</h3>

                <p>
                    Kamu tidak harus menjadi sempurna untuk layak
                    mendapatkan perhatian dan kasih sayang.
                    Setiap langkah kecil tetap berarti.
                </p>
            </div>

            <span class="quote-flower">✿</span>

        </section>


        <!-- FOOTER -->
        <footer class="page-footer">

            <div class="footer-heart">♥</div>

            <p>Dibuat dengan penuh cinta oleh MySpace.</p>

            <span>
                Kesehatanmu, kebahagiaanmu, perjalananmu. ♡
            </span>

        </footer>

    </main>

</div>

</body>
</html>
