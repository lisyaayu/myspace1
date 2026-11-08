
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

$user_id = (int) $_SESSION['user_id'];
$user_name = 'Bestie';

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user_name = $row['name'];
    }

    $stmt->close();
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthy Habits | MySpace</title>

    <link rel="stylesheet" href="assets/css/healthy_habits_male.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="page-wrapper">

    <header class="topbar">
        <a href="dashboard.php" class="brand">
            <i class="fa-solid fa-heart-pulse"></i>
            <span>MySpace</span>
        </a>

        <a href="health_center.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Health Center
        </a>
    </header>

    <main class="container">

        <section class="hero">
            <div class="hero-content">
                <span class="eyebrow">
                    <i class="fa-solid fa-bolt"></i>
                    MEN'S HEALTHY LIFESTYLE
                </span>

                <h1>Build Better Habits,<br>Become Your Best Self.</h1>

                <p>
                    Halo, <?= e($user_name) ?>! Mulai bangun kebiasaan
                    sehat dari hal-hal sederhana. Jaga kesehatan tubuh,
                    tingkatkan energi, dan konsisten menjalani rutinitas
                    yang lebih baik setiap hari.
                </p>

                <a href="#daily-routine" class="hero-button">
                    Start Your Routine
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="hero-icon">
                <i class="fa-solid fa-dumbbell"></i>
            </div>
        </section>

        <section class="progress-section">
            <div>
                <span class="section-label">YOUR DAILY PROGRESS</span>
                <h2>Today's Achievement</h2>
                <p>Setiap langkah kecil membawa perubahan.</p>
            </div>

            <div class="progress-card">
                <div class="progress-info">
                    <span>Completed Habits</span>
                    <strong id="progress-count">0/12</strong>
                </div>

                <div class="progress-bar">
                    <div id="progress-fill"></div>
                </div>

                <p id="progress-message">Ayo mulai rutinitas sehatmu!</p>
            </div>
        </section>

        <section class="habit-section" id="daily-routine">

            <div class="section-heading">
                <span class="section-label">DAILY CHECKLIST</span>
                <h2>Your Healthy Routine</h2>
                <p>
                    Centang kebiasaan yang sudah kamu lakukan hari ini.
                    Progres akan diperbarui secara otomatis.
                </p>
            </div>

            <div class="habit-grid">

                <article class="habit-card">
                    <div class="habit-icon blue">
                        <i class="fa-solid fa-bed"></i>
                    </div>

                    <span class="category">SLEEP SCHEDULE</span>
                    <h3>Good Sleep</h3>
                    <p>
                        Tidur cukup dan usahakan memiliki jadwal tidur
                        serta bangun yang teratur.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon cyan">
                        <i class="fa-solid fa-glass-water"></i>
                    </div>

                    <span class="category">HYDRATION</span>
                    <h3>Drink Enough Water</h3>
                    <p>
                        Penuhi kebutuhan cairan tubuh dengan minum air
                        secara teratur sepanjang hari.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon orange">
                        <i class="fa-solid fa-dumbbell"></i>
                    </div>

                    <span class="category">FITNESS & MOVEMENT</span>
                    <h3>Daily Exercise</h3>
                    <p>
                        Luangkan waktu untuk berolahraga, seperti gym,
                        jogging, bersepeda, atau berjalan kaki.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon green">
                        <i class="fa-solid fa-apple-whole"></i>
                    </div>

                    <span class="category">HEALTHY EATING</span>
                    <h3>Balanced Meals</h3>
                    <p>
                        Konsumsi makanan bergizi seimbang yang terdiri
                        dari sayuran, buah, protein, dan sumber energi.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon purple">
                        <i class="fa-solid fa-shower"></i>
                    </div>

                    <span class="category">PERSONAL HYGIENE</span>
                    <h3>Body Cleanliness</h3>
                    <p>
                        Jaga kebersihan tubuh, mandi secara teratur,
                        dan gunakan pakaian yang bersih.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon red">
                        <i class="fa-solid fa-tooth"></i>
                    </div>

                    <span class="category">ORAL CARE</span>
                    <h3>Dental Care</h3>
                    <p>
                        Sikat gigi dua kali sehari dan jaga kesehatan
                        mulut agar tetap bersih.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon cyan">
                        <i class="fa-solid fa-mobile-screen"></i>
                    </div>

                    <span class="category">DIGITAL BALANCE</span>
                    <h3>Reduce Screen Time</h3>
                    <p>
                        Batasi penggunaan gadget yang tidak diperlukan
                        dan berikan waktu istirahat untuk mata.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon orange">
                        <i class="fa-solid fa-brain"></i>
                    </div>

                    <span class="category">MENTAL WELLNESS</span>
                    <h3>Take Time to Relax</h3>
                    <p>
                        Luangkan waktu untuk beristirahat, mengelola
                        stres, dan melakukan aktivitas yang disukai.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon green">
                        <i class="fa-solid fa-person-walking"></i>
                    </div>

                    <span class="category">ACTIVE LIFESTYLE</span>
                    <h3>Stay Active</h3>
                    <p>
                        Hindari terlalu lama duduk. Sempatkan bergerak
                        dan melakukan peregangan.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon purple">
                        <i class="fa-solid fa-hand-sparkles"></i>
                    </div>

                    <span class="category">PERSONAL CARE</span>
                    <h3>Self Grooming</h3>
                    <p>
                        Rawat diri dengan menjaga kebersihan rambut,
                        kuku, kulit, dan penampilan.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon blue">
                        <i class="fa-solid fa-ban"></i>
                    </div>

                    <span class="category">HEALTHY CHOICES</span>
                    <h3>Avoid Smoking</h3>
                    <p>
                        Hindari rokok dan paparan asap rokok untuk
                        mendukung kesehatan tubuh.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

                <article class="habit-card">
                    <div class="habit-icon red">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>

                    <span class="category">HEALTH AWARENESS</span>
                    <h3>Health Check-Up</h3>
                    <p>
                        Perhatikan kondisi tubuh dan jadwalkan
                        pemeriksaan kesehatan secara berkala.
                    </p>

                    <label class="check-row">
                        <input type="checkbox" class="habit-check">
                        <span>Sudah dilakukan</span>
                    </label>
                </article>

            </div>
        </section>

        <section class="motivation">
            <i class="fa-solid fa-trophy"></i>

            <div>
                <h3>Consistency Builds Strength</h3>
                <p>
                    Tidak harus sempurna setiap hari. Yang terpenting
                    adalah terus berusaha membangun kebiasaan yang baik.
                </p>
            </div>
        </section>

    </main>

    <footer>
        <p>© <?= date('Y') ?> MySpace. Take care of yourself.</p>
    </footer>

</div>

<script>
const checkboxes = document.querySelectorAll('.habit-check');
const progressCount = document.getElementById('progress-count');
const progressFill = document.getElementById('progress-fill');
const progressMessage = document.getElementById('progress-message');

const storageKey = 'myspace_male_habits_<?= $user_id ?>';

function saveHabits() {
    const values = Array.from(checkboxes).map(item => item.checked);
    localStorage.setItem(storageKey, JSON.stringify(values));
}

function updateProgress() {
    const completed = Array.from(checkboxes)
        .filter(item => item.checked).length;

    const total = checkboxes.length;
    const percentage = Math.round((completed / total) * 100);

    progressCount.textContent = `${completed}/${total}`;
    progressFill.style.width = percentage + '%';

    if (completed === 0) {
        progressMessage.textContent = 'Ayo mulai rutinitas sehatmu!';
    } else if (completed < total / 2) {
        progressMessage.textContent = 'Bagus! Terus bangun kebiasaan sehatmu.';
    } else if (completed < total) {
        progressMessage.textContent = 'Keren! Kamu sudah melewati setengah perjalanan.';
    } else {
        progressMessage.textContent = 'Luar biasa! Semua kebiasaan hari ini selesai.';
    }

    saveHabits();
}

function loadHabits() {
    const saved = localStorage.getItem(storageKey);

    if (saved) {
        try {
            const values = JSON.parse(saved);

            if (Array.isArray(values)) {
                checkboxes.forEach((item, index) => {
                    item.checked = values[index] === true;
                });
            }
        } catch (error) {
            localStorage.removeItem(storageKey);
        }
    }

    updateProgress();
}

checkboxes.forEach(item => {
    item.addEventListener('change', updateProgress);
});

loadHabits();
</script>

</body>
</html>
