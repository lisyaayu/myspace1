
<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$test_slug = $_GET['test'] ?? 'personality';

$allowed_tests = [
    'personality',
    'interests',
    'strengths',
    'love-language'
];

if (!in_array($test_slug, $allowed_tests, true)) {
    header("Location: know_me.php");
    exit;
}

// Mengambil informasi tes
$stmt = $conn->prepare("
    SELECT id, title, description
    FROM knowme_tests
    WHERE slug = ? AND is_active = 1
    LIMIT 1
");
$stmt->bind_param("s", $test_slug);
$stmt->execute();
$test = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$test) {
    header("Location: know_me.php");
    exit;
}

// Mengambil hasil tes terakhir milik pengguna
$stmt = $conn->prepare("
    SELECT id, result_key, result_title, result_description, completed_at
    FROM knowme_results
    WHERE user_id = ? AND test_id = ?
    ORDER BY completed_at DESC, id DESC
    LIMIT 1
");
$stmt->bind_param("ii", $user_id, $test['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    header("Location: know_me.php");
    exit;
}

// Mengambil rekomendasi hasil
$stmt = $conn->prepare("
    SELECT recommendation
    FROM knowme_result_profiles
    WHERE test_id = ? AND result_key = ?
    LIMIT 1
");
$stmt->bind_param("is", $test['id'], $result['result_key']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$recommendation = $profile['recommendation'] ?? 'Terus kenali dirimu dan kembangkan potensi yang kamu miliki.';

$user_name = $_SESSION['user_name'] ?? 'Pengguna';

$test_names = [
    'personality' => 'Personality Test',
    'interests' => 'My Interests',
    'strengths' => 'My Strengths',
    'love-language' => 'Love Language'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Tes | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #fff9fb;
            color: #493b43;
            min-height: 100vh;
        }

        .navbar {
            background: #ffffff;
            padding: 18px 7%;
            border-bottom: 1px solid #f5e5ec;
        }

        .brand {
            font-size: 24px;
            font-weight: 700;
            color: #d8759b;
            text-decoration: none;
        }

        .brand span {
            color: #55434c;
        }

        .back-link {
            color: #b96b8b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }

        .result-container {
            max-width: 850px;
            margin: 55px auto;
            padding: 0 20px;
        }

        .result-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .result-heading .eyebrow {
            color: #d17b9d;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .result-heading h1 {
            font-size: 30px;
            font-weight: 700;
            margin: 12px 0;
        }

        .result-heading p {
            color: #93818a;
            font-size: 14px;
        }

        .result-card {
            background: #ffffff;
            border: 1px solid #f4dfe8;
            border-radius: 25px;
            padding: 45px;
            text-align: center;
            box-shadow: 0 12px 35px rgba(190, 125, 151, 0.08);
        }

        .result-icon {
            width: 85px;
            height: 85px;
            background: #fff0f5;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 38px;
            margin: 0 auto 22px;
        }

        .result-label {
            color: #ce7b9c;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .result-title {
            color: #b85e83;
            font-size: 29px;
            font-weight: 700;
            margin: 12px 0 18px;
        }

        .result-description {
            color: #786973;
            font-size: 14px;
            line-height: 1.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .recommendation-card {
            background: #fff0f5;
            border-radius: 18px;
            padding: 25px;
            margin-top: 25px;
            text-align: left;
        }

        .recommendation-card h3 {
            color: #a95779;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .recommendation-card p {
            color: #786973;
            font-size: 13px;
            line-height: 1.9;
            margin: 0;
        }

        .result-date {
            color: #a3949b;
            font-size: 12px;
            margin-top: 25px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-main {
            background: #d77d9f;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 12px 25px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
        }

        .btn-main:hover {
            background: #c9688f;
            color: white;
        }

        .btn-outline {
            border: 1px solid #d77d9f;
            color: #bd6b8b;
            background: white;
            border-radius: 30px;
            padding: 12px 25px;
            font-size: 13px;
            text-decoration: none;
        }

        .btn-outline:hover {
            background: #fff0f5;
            color: #bd6b8b;
        }

        .footer {
            text-align: center;
            color: #b09da6;
            font-size: 12px;
            padding: 25px;
        }

        @media (max-width: 576px) {
            .result-container {
                margin: 35px auto;
            }

            .result-card {
                padding: 30px 20px;
            }

            .result-heading h1 {
                font-size: 24px;
            }

            .result-title {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar d-flex justify-content-between align-items-center">
    <a href="dashboard.php" class="brand">My<span>Space</span></a>
    <a href="know_me.php" class="back-link">← Kembali ke Know Me</a>
</nav>

<main class="result-container">

    <div class="result-heading">
        <div class="eyebrow">Your Self Discovery Journey</div>
        <h1>Hasil Tes Kamu</h1>
        <p>Hai, <?= e($user_name); ?>! Ini dia hasil dari perjalanan mengenal dirimu.</p>
    </div>

    <section class="result-card">

        <div class="result-icon">🌷</div>

        <div class="result-label">
            <?= e($test_names[$test_slug]); ?>
        </div>

        <h2 class="result-title">
            <?= e($result['result_title']); ?>
        </h2>

        <p class="result-description">
            <?= e($result['result_description']); ?>
        </p>

        <div class="recommendation-card">
            <h3>✨ Rekomendasi untuk Kamu</h3>
            <p><?= e($recommendation); ?></p>
        </div>

        <div class="result-date">
            Diselesaikan pada
            <?= e(date('d F Y, H:i', strtotime($result['completed_at']))); ?>
        </div>

        <div class="action-buttons">
            <a href="know_me.php" class="btn-main">
                Kembali ke Know Me
            </a>

            <a href="personality_test.php?test=<?= e($test_slug); ?>" class="btn-outline">
                Ulangi Tes
            </a>
        </div>

    </section>

</main>

<footer class="footer">
    MySpace — Your Personal Life Companion
</footer>

</body>
</html>
