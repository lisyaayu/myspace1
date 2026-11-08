
<?php
session_start();
require_once "config/database.php";

// Cek login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// Ambil nama pengguna
$stmt = $conn->prepare(
    "SELECT name FROM users WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: login.php");
    exit;
}

// Ambil daftar tes aktif
$stmt = $conn->prepare(
    "SELECT id, slug, title, description, icon
     FROM knowme_tests
     WHERE is_active = 1
     ORDER BY id ASC"
);
$stmt->execute();

$tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hitung jumlah tes yang pernah diselesaikan
$stmt = $conn->prepare(
    "SELECT COUNT(DISTINCT test_id) AS total
     FROM knowme_results
     WHERE user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$completed = $stmt->get_result()->fetch_assoc();
$total_completed = (int) $completed["total"];
$stmt->close();

// Daftar warna dan gambar setiap tes
$test_styles = [
    "personality" => [
        "color" => "personality",
        "symbol" => "fa-brain",
        "label" => "Mengenal Karakter"
    ],
    "interests" => [
        "color" => "interests",
        "symbol" => "fa-heart",
        "label" => "Menemukan Minat"
    ],
    "strengths" => [
        "color" => "strengths",
        "symbol" => "fa-star",
        "label" => "Menggali Potensi"
    ],
    "love-language" => [
        "color" => "love",
        "symbol" => "fa-hand-holding-heart",
        "label" => "Memahami Kasih Sayang"
    ]
];

$first_name = explode(" ", trim($user["name"]))[0];
$first_name = htmlspecialchars($first_name, ENT_QUOTES, "UTF-8");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Know Me | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/know-me.css">
</head>

<body>

<div class="knowme-container">

    <!-- TOP NAVIGATION -->
    <header class="knowme-header">

        <a href="dashboard.php" class="brand">
            <span class="brand-icon">
                <i class="fa-solid fa-heart"></i>
            </span>
            MySpace
        </a>

        <a href="dashboard.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>

    </header>


    <!-- WELCOME SECTION -->
    <section class="knowme-hero">

        <div class="hero-content">

            <span class="hero-label">
                <i class="fa-solid fa-sparkles"></i>
                SELF DISCOVERY SPACE
            </span>

            <h1>Get to Know <span>Yourself.</span></h1>

            <p>
                Hai, <?= $first_name ?>! Setiap orang memiliki karakter,
                minat, dan kelebihan yang berbeda. Yuk, kenali dirimu
                lebih dalam melalui beberapa tes sederhana.
            </p>

            <a href="#test-list" class="hero-button">
                Explore Tests
                <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>

        <div class="hero-decoration">
            <div class="hero-circle">
                <i class="fa-solid fa-user-astronaut"></i>
            </div>
            <span class="hero-star star-one">✦</span>
            <span class="hero-star star-two">♡</span>
            <span class="hero-star star-three">✧</span>
        </div>

    </section>


    <!-- PROGRESS SUMMARY -->
    <section class="progress-section">

        <div class="progress-card">

            <div class="progress-icon">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>

            <div>
                <span>Tes yang telah diselesaikan</span>
                <h2><?= $total_completed ?> <small>/ 4</small></h2>
            </div>

        </div>

        <div class="progress-description">
            <h3>Your Journey, Your Story.</h3>
            <p>
                Luangkan sedikit waktu untuk mengenali hal-hal
                menarik yang ada dalam dirimu.
            </p>
        </div>

    </section>


    <!-- TEST LIST -->
    <section class="test-section" id="test-list">

        <div class="section-heading">

            <div>
                <span class="section-label">DISCOVER YOURSELF</span>
                <h2>Explore Your Tests</h2>
                <p>Pilih tes yang ingin kamu kerjakan.</p>
            </div>

            <span class="test-count">
                <?= count($tests) ?> Tests Available
            </span>

        </div>


        <div class="test-grid">

            <?php if (count($tests) > 0): ?>

                <?php foreach ($tests as $test): ?>

                    <?php
                    $slug = $test["slug"];

                    $style = $test_styles[$slug] ?? [
                        "color" => "personality",
                        "symbol" => $test["icon"],
                        "label" => "Self Discovery"
                    ];
                    ?>

                    <article class="test-card">

                        <div class="test-card-top">

                            <div class="test-icon <?= htmlspecialchars($style["color"]) ?>">
                                <i class="fa-solid <?= htmlspecialchars($style["symbol"]) ?>"></i>
                            </div>

                            <span class="test-number">
                                TEST <?= str_pad($test["id"], 2, "0", STR_PAD_LEFT) ?>
                            </span>

                        </div>

                        <div class="test-content">

                            <span class="test-category">
                                <?= htmlspecialchars($style["label"]) ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($test["title"]) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($test["description"]) ?>
                            </p>

                        </div>

                        <a href="personality_test.php?test=<?= urlencode($slug) ?>"
                           class="test-button">

                            Start Test
                            <i class="fa-solid fa-arrow-right"></i>

                        </a>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <p>Belum ada tes yang tersedia.</p>

            <?php endif; ?>

        </div>

    </section>


    <!-- REMINDER -->
    <section class="reminder-card">

        <div class="reminder-icon">
            <i class="fa-solid fa-seedling"></i>
        </div>

        <div>
            <span>A LITTLE REMINDER</span>

            <h3>There is no one exactly like you.</h3>

            <p>
                Jangan membandingkan perjalananmu dengan orang lain.
                Kenali dirimu, hargai kelebihanmu, dan terus berkembang.
            </p>
        </div>

    </section>


    <!-- FOOTER -->
    <footer class="knowme-footer">
        <p>© <?= date("Y") ?> MySpace. Your Personal Life Companion.</p>
    </footer>

</div>

</body>
</html>
