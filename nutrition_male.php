<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $date = $_POST['log_date'] ?? date('Y-m-d');
            $water = filter_var(
                $_POST['water_glasses'] ?? 0,
                FILTER_VALIDATE_INT
            );
            $meal = trim($_POST['meal_name'] ?? '');
            $description = trim($_POST['meal_description'] ?? '');

            $dateObject = DateTime::createFromFormat('Y-m-d', $date);
            $validDate = $dateObject &&
                $dateObject->format('Y-m-d') === $date;

            if (!$validDate || $date > date('Y-m-d')) {
                $error = 'Please choose a valid date.';
            } elseif ($water === false || $water < 0 || $water > 50) {
                $error = 'Water amount must be between 0 and 50 glasses.';
            } elseif (strlen($meal) > 100) {
                $error = 'Meal name is too long.';
            } elseif (strlen($description) > 5000) {
                $error = 'Description is too long.';
            } else {
                if ($meal === '') {
                    $meal = 'Water intake';
                }

                if ($water === 0 && $description === '' &&
                    $meal === 'Water intake') {
                    $error = 'Please enter a meal or water amount.';
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO nutrition_logs
                        (user_id, log_date, water_glasses, meal_name, meal_description)
                        VALUES (?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "isiss",
                        $userId,
                        $date,
                        $water,
                        $meal,
                        $description
                    );

                    if ($stmt->execute()) {
                        header("Location: nutrition.php?status=added");
                        exit;
                    }

                    $error = 'Could not save your nutrition record.';
                }
            }
        }

        if ($action === 'delete') {
            $id = filter_var(
                $_POST['log_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if ($id) {
                $stmt = $conn->prepare(
                    "DELETE FROM nutrition_logs
                     WHERE id = ? AND user_id = ?"
                );
                $stmt->bind_param("ii", $id, $userId);
                $stmt->execute();
            }

            header("Location: nutrition.php?status=deleted");
            exit;
        }
    }
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);

if (!$dateObject || $dateObject->format('Y-m-d') !== $selectedDate ||
    $selectedDate > date('Y-m-d')) {
    $selectedDate = date('Y-m-d');
}

$stmt = $conn->prepare(
    "SELECT id, log_date, water_glasses, meal_name,
            meal_description, created_at
     FROM nutrition_logs
     WHERE user_id = ? AND log_date = ?
     ORDER BY id DESC"
);
$stmt->bind_param("is", $userId, $selectedDate);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalWater = 0;
foreach ($logs as $log) {
    $totalWater += (int)$log['water_glasses'];
}

$waterGoal = 8;
$waterProgress = min(100, (int)round(($totalWater / $waterGoal) * 100));

$success = '';
if (($_GET['status'] ?? '') === 'added') {
    $success = 'Your nutrition record has been saved!';
} elseif (($_GET['status'] ?? '') === 'deleted') {
    $success = 'Record deleted successfully.';
}

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$userName = $user['name'] ?? 'Bestie';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrition & Hydration | MySpace</title>
    <link rel="stylesheet" href="assets/css/nutrition.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<header class="nutrition-topbar">
    <a href="health_center.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Health Center
    </a>

    <span class="brand">MySpace <i class="fa-solid fa-heart"></i></span>
</header>

<main class="nutrition-container">

    <section class="nutrition-hero">
        <span class="eyebrow">NOURISH YOUR BODY</span>
        <h1>Eat Well, Feel Well.</h1>
        <p>
            Hi, <?= htmlspecialchars($userName) ?>!
            Keep track of your meals and hydration.
            Small mindful choices can support your everyday wellness.
        </p>
    </section>

    <?php if ($success): ?>
        <div class="notice success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <section class="hydration-card">
        <div class="hydration-info">
            <span class="small-label">DAILY HYDRATION RECORD</span>
            <h2><?= $totalWater ?> <small>/ <?= $waterGoal ?> glasses</small></h2>
            <p>
                <?= $totalWater >= $waterGoal
                    ? 'You have reached your example tracking goal!'
                    : 'Keep recording your water intake throughout the day.' ?>
            </p>

            <div class="water-progress">
                <div style="width: <?= $waterProgress ?>%"></div>
            </div>

            <small class="progress-caption">
                <?= $waterProgress ?>% of your example goal
            </small>
        </div>

        <div class="water-icon">
            <i class="fa-solid fa-glass-water"></i>
        </div>
    </section>

    <p class="goal-note">
        Note: 8 glasses is an illustrative tracking target, not a medical
        requirement for everyone. Individual fluid needs vary.
    </p>

    <section class="nutrition-layout">

        <div class="nutrition-form-card">
            <span class="small-label">ADD YOUR DAILY RECORD</span>
            <h2>Log Your Meal & Water</h2>
            <p class="muted">Record what you eat and drink.</p>

            <form method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="add">

                <label for="log_date">Date</label>
                <input type="date" id="log_date" name="log_date"
                       value="<?= htmlspecialchars($selectedDate) ?>"
                       max="<?= date('Y-m-d') ?>" required>

                <label for="meal_name">Meal / Drink</label>
                <select id="meal_name" name="meal_name">
                    <option value="">Water only / no meal</option>
                    <option value="Breakfast">Breakfast</option>
                    <option value="Lunch">Lunch</option>
                    <option value="Dinner">Dinner</option>
                    <option value="Snack">Snack</option>
                    <option value="Water">Water</option>
                    <option value="Other">Other</option>
                </select>

                <label for="water_glasses">Water intake (glasses)</label>
                <input type="number" id="water_glasses"
                       name="water_glasses" min="0" max="50"
                       value="0" required>

                <label for="meal_description">Food / Drink details</label>
                <textarea id="meal_description" name="meal_description"
                          rows="4" maxlength="5000"
                          placeholder="Example: Rice, vegetables, eggs..."></textarea>

                <button type="submit" class="primary-button">
                    <i class="fa-solid fa-plus"></i> Save Record
                </button>
            </form>
        </div>

        <div class="nutrition-history-card">
            <div class="history-heading">
                <div>
                    <span class="small-label">YOUR DAILY RECORDS</span>
                    <h2>Food & Hydration History</h2>
                </div>

                <form method="GET">
                    <input type="date" name="date"
                           value="<?= htmlspecialchars($selectedDate) ?>"
                           max="<?= date('Y-m-d') ?>"
                           onchange="this.form.submit()">
                </form>
            </div>

            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-carrot"></i>
                    <h3>No records yet</h3>
                    <p>Add your first meal or water intake record.</p>
                </div>
            <?php else: ?>
                <div class="nutrition-records">
                    <?php foreach ($logs as $log): ?>
                        <article class="nutrition-record">
                            <div class="record-icon">
                                <?php if ((int)$log['water_glasses'] > 0): ?>
                                    <i class="fa-solid fa-glass-water"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-utensils"></i>
                                <?php endif; ?>
                            </div>

                            <div class="record-details">
                                <h3><?= htmlspecialchars($log['meal_name']) ?></h3>

                                <span>
                                    <?= date('H:i', strtotime($log['created_at'])) ?>
                                    · <?= (int)$log['water_glasses'] ?> glasses of water
                                </span>

                                <?php if (!empty($log['meal_description'])): ?>
                                    <p><?= nl2br(htmlspecialchars($log['meal_description'])) ?></p>
                                <?php endif; ?>
                            </div>

                            <form method="POST"
                                  onsubmit="return confirm('Delete this record?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="log_id"
                                       value="<?= (int)$log['id'] ?>">

                                <button type="submit" class="delete-button">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </section>

    <section class="nutrition-reminder">
        <i class="fa-solid fa-leaf"></i>
        <div>
            <h3>Make room for balance.</h3>
            <p>
                Enjoy a variety of foods, pay attention to your body's needs,
                and remember that healthy eating does not have to be perfect.
            </p>
        </div>
    </section>

</main>

</body>
</html>