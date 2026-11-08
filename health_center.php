<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

$user_id = (int) $_SESSION['user_id'];
$user_name = 'Bestie';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Ambil nama pengguna
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

// Simpan pilihan Health Center
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $choice = $_POST['preferred_center'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Permintaan tidak valid. Silakan muat ulang halaman.');
    }

    if (!in_array($choice, ['female', 'male'], true)) {
        header("Location: health_center.php");
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO health_center_preferences
            (user_id, preferred_center)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE
            preferred_center = VALUES(preferred_center)
    ");

    if (!$stmt) {
        exit('Gagal menyiapkan penyimpanan pilihan.');
    }

    $stmt->bind_param("is", $user_id, $choice);

    if (!$stmt->execute()) {
        exit('Gagal menyimpan pilihan Health Center.');
    }

    $stmt->close();

    // Regenerate token setelah aksi berhasil
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $destination = $choice === 'female'
        ? 'health_female.php'
        : 'health_male.php';

    header("Location: " . $destination);
    exit;
}

// Ambil pilihan terakhir pengguna
$preferred_center = '';

$stmt = $conn->prepare("
    SELECT preferred_center
    FROM health_center_preferences
    WHERE user_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $preferred_center = $row['preferred_center'];
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Center | MySpace</title>

    <link rel="stylesheet" href="assets/css/health_center.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="health-page">

    <header class="topbar">
        <a href="dashboard.php" class="brand">
            <span class="brand-icon">
                <i class="fa-solid fa-heart-pulse"></i>
            </span>
            <span>MySpace</span>
        </a>

        <a href="dashboard.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </header>

    <main class="main-content">

        <section class="welcome-section">
            <span class="eyebrow">
                <i class="fa-solid fa-heart"></i>
                YOUR WELLNESS SPACE
            </span>

            <h1>Your Health Matters, <?= e($user_name) ?>!</h1>

            <p>
                Taking care of yourself is a beautiful journey.
                Choose your space and explore wellness tools
                made to support you.
            </p>
        </section>

        <section class="choice-section">

            <div class="section-heading">
                <h2>Choose Your Health Center</h2>
                <p>Your previous choice is remembered, but you can change it anytime.</p>
            </div>

            <div class="choice-grid">

                <!-- FEMALE -->
                <article class="choice-card female-card">

                    <div class="card-decoration female-decoration">
                        <i class="fa-solid fa-spa"></i>
                    </div>

                    <div class="choice-icon female-icon">
                        <i class="fa-solid fa-seedling"></i>
                    </div>

                    <span class="card-label">SOFT • SELF-LOVE • WELLNESS</span>

                    <h3>Female Health Center</h3>

                    <p>
                        A gentle space to care for your body, mind,
                        menstrual health, nutrition, and everyday wellness.
                    </p>

                    <ul>
                        <li><i class="fa-solid fa-check"></i> Menstrual Tracker</li>
                        <li><i class="fa-solid fa-check"></i> Mental Wellness</li>
                        <li><i class="fa-solid fa-check"></i> Self-Care & Healthy Habits</li>
                        <li><i class="fa-solid fa-check"></i> Nutrition & Hydration</li>
                    </ul>

                    <?php if ($preferred_center === 'female'): ?>
                        <span class="saved-choice female-saved">
                            <i class="fa-solid fa-heart"></i> Your last choice
                        </span>
                    <?php endif; ?>

                    <form method="POST" action="health_center.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="preferred_center"
                               value="female">

                        <button type="submit"
                                class="choice-button female-button">
                            Explore Female Health
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>

                    <div class="flower-decoration flower-one">✿</div>
                    <div class="flower-decoration flower-two">❀</div>
                </article>

                <!-- MALE -->
                <article class="choice-card male-card">

                    <div class="card-decoration male-decoration">
                        <i class="fa-solid fa-dumbbell"></i>
                    </div>

                    <div class="choice-icon male-icon">
                        <i class="fa-solid fa-person"></i>
                    </div>

                    <span class="card-label">STRENGTH • BALANCE • WELLNESS</span>

                    <h3>Male Health Center</h3>

                    <p>
                        A space to build healthy routines, improve fitness,
                        support mental wellness, and learn about men's health.
                    </p>

                    <ul>
                        <li><i class="fa-solid fa-check"></i> Fitness Tracker</li>
                        <li><i class="fa-solid fa-check"></i> Men's Health Guide</li>
                        <li><i class="fa-solid fa-check"></i> Mental Wellness</li>
                        <li><i class="fa-solid fa-check"></i> Nutrition & Healthy Habits</li>
                    </ul>

                    <?php if ($preferred_center === 'male'): ?>
                        <span class="saved-choice male-saved">
                            <i class="fa-solid fa-heart"></i> Your last choice
                        </span>
                    <?php endif; ?>

                    <form method="POST" action="health_center.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="preferred_center"
                               value="male">

                        <button type="submit"
                                class="choice-button male-button">
                            Explore Male Health
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>

                    <div class="circle-decoration circle-one"></div>
                    <div class="circle-decoration circle-two"></div>
                </article>

            </div>
        </section>

        <section class="bottom-note">
            <i class="fa-solid fa-heart"></i>
            <p>
                Remember, bestie: your health journey is personal.
                Take it one small step at a time.
            </p>
        </section>

    </main>

    <footer>
        <p>© <?= date('Y') ?> MySpace. Made with care for your well-being.</p>
    </footer>

</div>

</body>
</html>