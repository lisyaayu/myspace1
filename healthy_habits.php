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
            $habit = trim($_POST['habit_name'] ?? '');
            $date = $_POST['habit_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');

            $dateObject = DateTime::createFromFormat('Y-m-d', $date);
            $validDate = $dateObject &&
                $dateObject->format('Y-m-d') === $date;

            if ($habit === '' || strlen($habit) > 150) {
                $error = 'Please enter a habit name (maximum 150 characters).';
            } elseif (!$validDate || $date > date('Y-m-d')) {
                $error = 'Please choose a valid date.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO healthy_habits
                    (user_id, habit_name, habit_date, notes)
                    VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("isss", $userId, $habit, $date, $notes);

                if ($stmt->execute()) {
                    header("Location: healthy_habits.php?status=added");
                    exit;
                }

                $error = 'Could not save your habit.';
            }
        }

        if ($action === 'toggle') {
            $id = filter_var($_POST['habit_id'] ?? '', FILTER_VALIDATE_INT);

            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE healthy_habits
                     SET is_completed = IF(is_completed = 1, 0, 1)
                     WHERE id = ? AND user_id = ?"
                );
                $stmt->bind_param("ii", $id, $userId);
                $stmt->execute();
            }

            header("Location: healthy_habits.php");
            exit;
        }

        if ($action === 'delete') {
            $id = filter_var($_POST['habit_id'] ?? '', FILTER_VALIDATE_INT);

            if ($id) {
                $stmt = $conn->prepare(
                    "DELETE FROM healthy_habits WHERE id = ? AND user_id = ?"
                );
                $stmt->bind_param("ii", $id, $userId);
                $stmt->execute();
            }

            header("Location: healthy_habits.php?status=deleted");
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
    "SELECT id, habit_name, habit_date, is_completed, notes
     FROM healthy_habits
     WHERE user_id = ? AND habit_date = ?
     ORDER BY id DESC"
);
$stmt->bind_param("is", $userId, $selectedDate);
$stmt->execute();
$habits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalHabits = count($habits);
$completedHabits = 0;

foreach ($habits as $habit) {
    if ((int)$habit['is_completed'] === 1) {
        $completedHabits++;
    }
}

$progress = $totalHabits > 0
    ? (int) round(($completedHabits / $totalHabits) * 100)
    : 0;

$success = '';
if (($_GET['status'] ?? '') === 'added') {
    $success = 'New habit added successfully!';
} elseif (($_GET['status'] ?? '') === 'deleted') {
    $success = 'Habit deleted successfully.';
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
    <title>Healthy Habits | MySpace</title>
    <link rel="stylesheet" href="assets/css/healthy_habits.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<header class="habit-topbar">
    <a href="health_center.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Health Center
    </a>
    <span class="brand">MySpace <i class="fa-solid fa-heart"></i></span>
</header>

<main class="habit-container">

    <section class="habit-hero">
        <span class="eyebrow">YOUR DAILY WELLNESS ROUTINE</span>
        <h1>Little Habits, Big Changes.</h1>
        <p>
            Hello, <?= htmlspecialchars($userName) ?>!
            Build healthy routines one small step at a time.
        </p>
    </section>

    <?php if ($success): ?>
        <div class="notice success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <section class="habit-progress">
        <div>
            <span class="small-label">YOUR DAILY PROGRESS</span>
            <h2><?= $progress ?>% completed</h2>
            <p><?= $completedHabits ?> of <?= $totalHabits ?> habits completed</p>
        </div>

        <div class="progress-circle"
             style="--progress: <?= $progress ?>%">
            <span><?= $progress ?>%</span>
        </div>
    </section>

    <section class="habit-layout">

        <div class="habit-form-card">
            <span class="small-label">CREATE YOUR ROUTINE</span>
            <h2>Add a Healthy Habit</h2>
            <p class="muted">Choose one small thing to work on.</p>

            <form method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="add">

                <label for="habit_name">Habit name</label>
                <input type="text" id="habit_name" name="habit_name"
                       maxlength="150"
                       placeholder="Example: Drink enough water" required>

                <label for="habit_date">Date</label>
                <input type="date" id="habit_date" name="habit_date"
                       value="<?= htmlspecialchars($selectedDate) ?>"
                       max="<?= date('Y-m-d') ?>" required>

                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3"
                          placeholder="Write a little reminder..."></textarea>

                <button type="submit" class="primary-button">
                    <i class="fa-solid fa-plus"></i> Add Habit
                </button>
            </form>

            <div class="suggestions">
                <h3>Habit ideas</h3>
                <div class="suggestion-list">
                    <span>Drink water</span>
                    <span>Walk 20 minutes</span>
                    <span>Sleep on time</span>
                    <span>Read a book</span>
                    <span>Stretch</span>
                    <span>Take a break</span>
                </div>
            </div>
        </div>

        <div class="habit-list-card">
            <div class="list-heading">
                <div>
                    <span class="small-label">YOUR CHECKLIST</span>
                    <h2>Daily Habits</h2>
                </div>

                <form method="GET">
                    <input type="date" name="date"
                           value="<?= htmlspecialchars($selectedDate) ?>"
                           max="<?= date('Y-m-d') ?>"
                           onchange="this.form.submit()">
                </form>
            </div>

            <?php if (empty($habits)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-seedling"></i>
                    <h3>A fresh start!</h3>
                    <p>Add your first habit and begin building your routine.</p>
                </div>
            <?php else: ?>
                <div class="habit-items">
                    <?php foreach ($habits as $habit): ?>
                        <article class="habit-item">
                            <form method="POST" class="toggle-form">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="habit_id"
                                       value="<?= (int)$habit['id'] ?>">

                                <button type="submit"
                                        class="check-button <?= $habit['is_completed'] ? 'checked' : '' ?>"
                                        aria-label="Toggle habit completion">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>

                            <div class="habit-details">
                                <h3 class="<?= $habit['is_completed'] ? 'done' : '' ?>">
                                    <?= htmlspecialchars($habit['habit_name']) ?>
                                </h3>

                                <?php if (!empty($habit['notes'])): ?>
                                    <p><?= nl2br(htmlspecialchars($habit['notes'])) ?></p>
                                <?php endif; ?>

                                <span>
                                    <?= $habit['is_completed'] ? 'Completed' : 'Not completed yet' ?>
                                </span>
                            </div>

                            <form method="POST"
                                  onsubmit="return confirm('Delete this habit?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="habit_id"
                                       value="<?= (int)$habit['id'] ?>">
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

    <section class="habit-quote">
        <i class="fa-solid fa-heart"></i>
        <p>"You don't need to change everything today. Just start with one good habit."</p>
    </section>

</main>

</body>
</html>