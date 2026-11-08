
<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage($message) {
    $_SESSION['fitness_message'] = $message;
    header("Location: fitness_tracker.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit("Permintaan tidak valid. Silakan refresh halaman.");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $activity = trim($_POST['activity'] ?? '');
        $duration = filter_var($_POST['duration'] ?? '', FILTER_VALIDATE_INT);
        $activity_date = $_POST['activity_date'] ?? '';
        $notes = trim($_POST['notes'] ?? '');

        $allowed_activities = [
            'Walking', 'Running', 'Cycling', 'Yoga',
            'Workout', 'Swimming', 'Dancing', 'Other'
        ];

        $date = DateTime::createFromFormat('Y-m-d', $activity_date);
        $valid_date = $date && $date->format('Y-m-d') === $activity_date;

        if (
            !in_array($activity, $allowed_activities, true) ||
            $duration === false ||
            $duration < 1 ||
            $duration > 1440 ||
            !$valid_date ||
            $activity_date > date('Y-m-d') ||
            strlen($notes) > 5000
        ) {
            redirectWithMessage("Data aktivitas tidak valid. Periksa kembali isianmu.");
        }

        $stmt = $conn->prepare(
            "INSERT INTO fitness_logs
             (user_id, activity, duration, activity_date, notes)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "isiss",
            $user_id,
            $activity,
            $duration,
            $activity_date,
            $notes
        );

        if ($stmt->execute()) {
            $stmt->close();
            redirectWithMessage("Aktivitas olahraga berhasil dicatat!");
        }

        $stmt->close();
        redirectWithMessage("Aktivitas gagal disimpan. Coba lagi.");
    }

    if ($action === 'delete') {
        $log_id = filter_var($_POST['log_id'] ?? '', FILTER_VALIDATE_INT);

        if (!$log_id) {
            redirectWithMessage("Catatan tidak valid.");
        }

        $stmt = $conn->prepare(
            "DELETE FROM fitness_logs WHERE id = ? AND user_id = ?"
        );

        $stmt->bind_param("ii", $log_id, $user_id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        redirectWithMessage(
            $deleted > 0
                ? "Catatan aktivitas berhasil dihapus."
                : "Catatan tidak ditemukan."
        );
    }
}

$message = $_SESSION['fitness_message'] ?? '';
unset($_SESSION['fitness_message']);

$name = "Bestie";

$stmt = $conn->prepare(
    "SELECT name FROM users WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user = $user_result->fetch_assoc()) {
    $name = $user['name'] ?: "Bestie";
}
$stmt->close();

$selected_date = trim($_GET['date'] ?? '');

if ($selected_date !== '') {
    $date_object = DateTime::createFromFormat('Y-m-d', $selected_date);

    if (
        !$date_object ||
        $date_object->format('Y-m-d') !== $selected_date
    ) {
        $selected_date = '';
    }
}

$today = date('Y-m-d');

$stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total_sessions,
        COALESCE(SUM(duration), 0) AS total_minutes
     FROM fitness_logs
     WHERE user_id = ? AND activity_date = ?"
);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_sessions = (int)($today_stats['total_sessions'] ?? 0);
$total_minutes = (int)($today_stats['total_minutes'] ?? 0);

$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(duration), 0) AS total
     FROM fitness_logs
     WHERE user_id = ?
       AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
       AND activity_date <= CURDATE()"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$week_result = $stmt->get_result()->fetch_assoc();
$week_minutes = (int)($week_result['total'] ?? 0);
$stmt->close();

if ($selected_date !== '') {
    $stmt = $conn->prepare(
        "SELECT * FROM fitness_logs
         WHERE user_id = ? AND activity_date = ?
         ORDER BY id DESC"
    );
    $stmt->bind_param("is", $user_id, $selected_date);
} else {
    $stmt = $conn->prepare(
        "SELECT * FROM fitness_logs
         WHERE user_id = ?
         ORDER BY activity_date DESC, id DESC
         LIMIT 100"
    );
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();

$activity_icons = [
    'Walking' => 'fa-person-walking',
    'Running' => 'fa-person-running',
    'Cycling' => 'fa-bicycle',
    'Yoga' => 'fa-spa',
    'Workout' => 'fa-dumbbell',
    'Swimming' => 'fa-person-swimming',
    'Dancing' => 'fa-music',
    'Other' => 'fa-heart-pulse'
];

$hours = intdiv($total_minutes, 60);
$minutes = $total_minutes % 60;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Tracker | MySpace</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f7fc;
            color: #25334a;
            min-height: 100vh;
        }

        .page-wrapper {
            display: block !important;
            width: 100% !important;
            min-height: 100vh;
            margin: 0 !important;
            padding: 0 !important;
        }

        .main-content {
            display: block !important;
            width: 100% !important;
            max-width: 1200px !important;
            min-height: 100vh;
            margin: 0 auto !important;
            padding: 30px 35px !important;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .eyebrow {
            display: inline-block;
            color: #4779c8;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }

        .topbar h2 {
            color: #172b4d;
            font-size: 27px;
            font-weight: 750;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 16px 9px 9px;
            background: #fff;
            border: 1px solid #e9eef6;
            border-radius: 30px;
            color: #34445e;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(30, 55, 90, .04);
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #e9f1ff;
            color: #386bb4;
        }

        .hero {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            overflow: hidden;
            padding: 38px 42px;
            min-height: 245px;
            color: white;
            background: linear-gradient(120deg, #17345f, #2867b5);
            border-radius: 22px;
            box-shadow: 0 12px 28px rgba(36, 82, 145, .14);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 610px;
        }

        .hero-tag {
            display: inline-block;
            padding: 7px 12px;
            margin-bottom: 15px;
            border-radius: 20px;
            background: rgba(255,255,255,.14);
            color: #e4efff;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.4px;
        }

        .hero h1 {
            margin-bottom: 13px;
            color: white;
            font-size: clamp(27px, 4vw, 38px);
            line-height: 1.2;
        }

        .hero p {
            max-width: 490px;
            color: #dce9fb;
            font-size: 14px;
            line-height: 1.8;
        }

        .hero-art {
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            width: 155px;
            height: 155px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            color: white;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }

        .hero-art i {
            font-size: 52px;
        }

        .notice {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 22px;
            padding: 14px 17px;
            border: 1px solid #bce8d0;
            border-radius: 12px;
            background: #edfff4;
            color: #21764b;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin: 25px 0;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
            padding: 22px;
            border: 1px solid #edf0f6;
            border-radius: 17px;
            background: white;
            box-shadow: 0 5px 18px rgba(30, 55, 90, .04);
        }

        .stat-icon {
            width: 49px;
            height: 49px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: #eaf2ff;
            color: #3972bd;
            font-size: 20px;
        }

        .stat-card span {
            display: block;
            margin-bottom: 7px;
            color: #8290a5;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .7px;
        }

        .stat-card h3 {
            color: #1c3152;
            font-size: 22px;
            overflow-wrap: anywhere;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
            align-items: start;
            gap: 22px;
        }

        .form-card,
        .history-card {
            min-width: 0;
            padding: 27px;
            border: 1px solid #edf0f6;
            border-radius: 18px;
            background: white;
            box-shadow: 0 5px 18px rgba(30, 55, 90, .04);
        }

        .section-heading {
            margin-bottom: 22px;
        }

        .section-heading h2 {
            margin-bottom: 7px;
            color: #1d3150;
            font-size: 22px;
        }

        .section-heading p {
            color: #8290a5;
            font-size: 13px;
            line-height: 1.6;
        }

        .activity-form {
            display: flex;
            flex-direction: column;
        }

        .activity-form label {
            margin: 14px 0 8px;
            color: #43536c;
            font-size: 13px;
            font-weight: 700;
        }

        .activity-form input,
        .activity-form select,
        .activity-form textarea {
            width: 100%;
            min-width: 0;
            padding: 12px 13px;
            border: 1px solid #dfe6f0;
            border-radius: 10px;
            outline: none;
            background: #fbfcfe;
            color: #34445e;
            font-family: inherit;
            font-size: 13px;
            transition: border .2s, box-shadow .2s;
        }

        .activity-form input:focus,
        .activity-form select:focus,
        .activity-form textarea:focus {
            border-color: #5a8ed3;
            box-shadow: 0 0 0 3px rgba(75, 130, 200, .12);
        }

        .activity-form textarea {
            resize: vertical;
        }

        .submit-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            margin-top: 22px;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #2867b5;
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s;
        }

        .submit-button:hover {
            background: #1d528f;
            transform: translateY(-1px);
        }

        .date-filter {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .date-filter input {
            flex: 1;
            min-width: 130px;
            padding: 10px;
            border: 1px solid #dfe6f0;
            border-radius: 9px;
            color: #43536c;
            font-family: inherit;
            font-size: 12px;
        }

        .date-filter button,
        .date-filter a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 13px;
            border: none;
            border-radius: 9px;
            background: #2867b5;
            color: white;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .date-filter a {
            background: #edf2f9;
            color: #53647d;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 17px 0;
            border-bottom: 1px solid #edf0f5;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #edf4ff;
            color: #3972bd;
            font-size: 17px;
        }

        .activity-details {
            flex: 1;
            min-width: 0;
        }

        .activity-details h3 {
            margin: 2px 0 5px;
            color: #263b5b;
            font-size: 14px;
        }

        .activity-details span {
            color: #8996a9;
            font-size: 11px;
        }

        .activity-details p {
            margin-top: 9px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .delete-button {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border: none;
            border-radius: 9px;
            background: #fff0f0;
            color: #d45c5c;
            cursor: pointer;
        }

        .delete-button:hover {
            background: #ffdddd;
        }

        .empty-state {
            padding: 40px 15px;
            text-align: center;
        }

        .empty-state > i {
            margin-bottom: 15px;
            color: #9bb5d8;
            font-size: 42px;
        }

        .empty-state h3 {
            margin-bottom: 8px;
            color: #34445e;
            font-size: 16px;
        }

        .empty-state p {
            color: #8996a9;
            font-size: 12px;
        }

        .reminder {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-top: 23px;
            padding: 21px 24px;
            border: 1px solid #dce9fa;
            border-radius: 15px;
            background: #edf5ff;
        }

        .reminder > i {
            margin-top: 3px;
            color: #3972bd;
            font-size: 21px;
        }

        .reminder h3 {
            margin-bottom: 6px;
            color: #294b78;
            font-size: 14px;
        }

        .reminder p {
            color: #627b9b;
            font-size: 12px;
            line-height: 1.7;
        }

        footer {
            padding: 28px 0 10px;
            color: #9aa5b5;
            text-align: center;
            font-size: 11px;
        }

        footer i {
            color: #e47b86;
        }

        @media (max-width: 850px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 25px 22px !important;
            }
        }

        @media (max-width: 600px) {
            .main-content {
                padding: 20px 15px !important;
            }

            .topbar {
                align-items: flex-start;
            }

            .topbar h2 {
                font-size: 22px;
            }

            .user-pill {
                padding: 7px;
                font-size: 12px;
            }

            .user-pill span {
                display: none;
            }

            .hero {
                min-height: auto;
                padding: 28px 23px;
            }

            .hero-art {
                width: 85px;
                height: 85px;
                font-size: 9px;
            }

            .hero-art i {
                font-size: 29px;
            }

            .hero h1 {
                font-size: 26px;
            }

            .hero p {
                font-size: 12px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 17px;
            }

            .form-card,
            .history-card {
                padding: 20px 17px;
            }

            .section-heading h2 {
                font-size: 20px;
            }

            .reminder {
                padding: 17px;
            }
        }

        @media (max-width: 380px) {
            .hero-art {
                display: none;
            }

            .date-filter {
                align-items: stretch;
            }

            .date-filter input {
                flex-basis: 100%;
            }
        }
    </style>
</head>

<body>

<div class="page-wrapper">
    <main class="main-content">

        <header class="topbar">
            <div>
                <span class="eyebrow">HEALTH CENTER / FITNESS</span>
                <h2>Fitness Tracker</h2>
            </div>

            <div class="user-pill">
                <div class="user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <span><?= e($name) ?></span>
            </div>
        </header>

        <section class="hero">
            <div class="hero-content">
                <span class="hero-tag">YOUR MOVEMENT JOURNEY</span>
                <h1>Move your body,<br>love yourself more! 💪</h1>
                <p>
                    Keep track of your activities and celebrate
                    every little progress. Your pace is your own.
                </p>
            </div>

            <div class="hero-art">
                <i class="fa-solid fa-person-running"></i>
                <span>One step<br>at a time</span>
            </div>
        </section>

        <?php if ($message): ?>
            <div class="notice">
                <i class="fa-solid fa-circle-check"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
                <div>
                    <span>TODAY'S ACTIVE TIME</span>
                    <h3>
                        <?php if ($hours > 0): ?>
                            <?= $hours ?>h <?= $minutes ?>m
                        <?php else: ?>
                            <?= $minutes ?> min
                        <?php endif; ?>
                    </h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <span>TODAY'S ACTIVITIES</span>
                    <h3><?= $total_sessions ?> sessions</h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-calendar-week"></i>
                </div>
                <div>
                    <span>LAST 7 DAYS</span>
                    <h3><?= $week_minutes ?> min</h3>
                </div>
            </div>
        </section>

        <section class="content-grid">

            <div class="form-card">
                <div class="section-heading">
                    <span class="eyebrow">ADD YOUR PROGRESS</span>
                    <h2>Record an activity</h2>
                    <p>What movement did you enjoy today?</p>
                </div>

                <form method="POST" class="activity-form">
                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION['csrf_token']) ?>">

                    <input type="hidden" name="action" value="add">

                    <label for="activity">Activity type</label>
                    <select name="activity" id="activity" required>
                        <option value="">Choose an activity</option>
                        <?php foreach ($activity_icons as $activity => $icon): ?>
                            <option value="<?= e($activity) ?>">
                                <?= e($activity) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="duration">Duration (minutes)</label>
                    <input type="number" id="duration" name="duration"
                           min="1" max="1440" placeholder="Example: 30" required>

                    <label for="activity_date">Activity date</label>
                    <input type="date" id="activity_date"
                           name="activity_date" max="<?= e($today) ?>"
                           value="<?= e($today) ?>" required>

                    <label for="notes">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="4"
                              maxlength="5000"
                              placeholder="How did you feel after your activity?"></textarea>

                    <button type="submit" class="submit-button">
                        <i class="fa-solid fa-plus"></i> Save Activity
                    </button>
                </form>
            </div>

            <div class="history-card">
                <div class="section-heading">
                    <span class="eyebrow">YOUR JOURNEY</span>
                    <h2>Activity history</h2>
                    <p>Your recorded movement, one day at a time.</p>
                </div>

                <form method="GET" class="date-filter">
                    <input type="date" name="date"
                           value="<?= e($selected_date) ?>"
                           max="<?= e($today) ?>">
                    <button type="submit">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="fitness_tracker.php">All</a>
                </form>

                <?php if (empty($logs)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-person-walking"></i>
                        <h3>No activities yet</h3>
                        <p>Start with a small activity. You've got this!</p>
                    </div>
                <?php else: ?>

                    <div class="activity-list">
                        <?php foreach ($logs as $log): ?>
                            <?php
                                $icon = $activity_icons[$log['activity']]
                                    ?? 'fa-heart-pulse';
                            ?>

                            <article class="activity-item">
                                <div class="activity-icon">
                                    <i class="fa-solid <?= e($icon) ?>"></i>
                                </div>

                                <div class="activity-details">
                                    <h3><?= e($log['activity']) ?></h3>
                                    <span>
                                        <?= e(date(
                                            'd M Y',
                                            strtotime($log['activity_date'])
                                        )) ?>
                                        · <?= (int)$log['duration'] ?> minutes
                                    </span>

                                    <?php if (!empty($log['notes'])): ?>
                                        <p><?= nl2br(e($log['notes'])) ?></p>
                                    <?php endif; ?>
                                </div>

                                <form method="POST"
                                      onsubmit="return confirm('Delete this activity?');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION['csrf_token']) ?>">

                                    <input type="hidden" name="action"
                                           value="delete">

                                    <input type="hidden" name="log_id"
                                           value="<?= (int)$log['id'] ?>">

                                    <button type="submit" class="delete-button"
                                            aria-label="Delete activity">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>

        </section>

        <section class="reminder">
            <i class="fa-solid fa-heart"></i>
            <div>
                <h3>Remember, bestie!</h3>
                <p>
                    Fitness is not a competition. Rest when you need to,
                    choose activities that feel right for your body,
                    and celebrate your consistency.
                </p>
            </div>
        </section>

        <footer>
            Made with <i class="fa-solid fa-heart"></i>
            for your well-being · MySpace
        </footer>

    </main>
</div>

</body>
</html>
