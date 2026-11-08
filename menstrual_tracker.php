
<?php
session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$message = "";
$message_type = "success";

// SIMPAN CATATAN MENSTRUASI
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Permintaan tidak valid. Silakan muat ulang halaman.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "save") {

        $start_date = trim($_POST["start_date"] ?? "");
        $end_date = trim($_POST["end_date"] ?? "");
        $notes = trim($_POST["notes"] ?? "");

        $start = DateTime::createFromFormat("!Y-m-d", $start_date);
        $end = $end_date !== ""
            ? DateTime::createFromFormat("!Y-m-d", $end_date)
            : null;

        $valid_start = $start && $start->format("Y-m-d") === $start_date;
        $valid_end = $end_date === "" ||
            ($end && $end->format("Y-m-d") === $end_date);

        if (!$valid_start || !$valid_end) {
            $message = "Tanggal yang dimasukkan tidak valid.";
            $message_type = "error";

        } elseif ($end && $end < $start) {
            $message = "Tanggal selesai tidak boleh sebelum tanggal mulai.";
            $message_type = "error";

        } elseif (strlen($notes) > 2000) {
            $message = "Catatan terlalu panjang. Maksimal 2.000 karakter.";
            $message_type = "error";

        } else {

            $end_value = $end_date !== "" ? $end_date : null;

            $stmt = $conn->prepare("
                INSERT INTO menstrual_cycles
                    (user_id, start_date, end_date, notes)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isss",
                $user_id,
                $start_date,
                $end_value,
                $notes
            );

            if ($stmt->execute()) {
                header("Location: menstrual_tracker.php?saved=1");
                exit;
            } else {
                $message = "Catatan belum berhasil disimpan. Silakan coba lagi.";
                $message_type = "error";
            }
        }
    }

    // HAPUS CATATAN
    if ($action === "delete") {

        $cycle_id = (int) ($_POST["cycle_id"] ?? 0);

        if ($cycle_id > 0) {
            $stmt = $conn->prepare("
                DELETE FROM menstrual_cycles
                WHERE id = ? AND user_id = ?
            ");

            $stmt->bind_param("ii", $cycle_id, $user_id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                header("Location: menstrual_tracker.php?deleted=1");
                exit;
            } else {
                $message = "Catatan tidak ditemukan atau sudah dihapus.";
                $message_type = "error";
            }
        }
    }
}

if (isset($_GET["saved"])) {
    $message = "Catatan menstruasimu berhasil disimpan.";
}

if (isset($_GET["deleted"])) {
    $message = "Catatan berhasil dihapus.";
}


// AMBIL RIWAYAT PENGGUNA
$stmt = $conn->prepare("
    SELECT id, start_date, end_date, notes, created_at
    FROM menstrual_cycles
    WHERE user_id = ?
    ORDER BY start_date DESC, id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$cycles = [];

while ($row = $result->fetch_assoc()) {
    $cycles[] = $row;
}


// HITUNG PERKIRAAN SIKLUS
$unique_dates = [];

foreach ($cycles as $cycle) {
    if (!in_array($cycle["start_date"], $unique_dates, true)) {
        $unique_dates[] = $cycle["start_date"];
    }
}

// Urutkan tanggal dari yang paling lama
sort($unique_dates);

$average_cycle = null;
$next_period = null;
$cycle_status = "Belum cukup data";

if (count($unique_dates) >= 2) {

    // Gunakan maksimal 6 jarak siklus terakhir
    $intervals = [];

    for ($i = 1; $i < count($unique_dates); $i++) {
        $previous = new DateTime($unique_dates[$i - 1]);
        $current = new DateTime($unique_dates[$i]);

        $days = (int) $previous->diff($current)->days;

        // Abaikan interval yang tidak masuk akal untuk perkiraan
        if ($days >= 15 && $days <= 60) {
            $intervals[] = $days;
        }
    }

    $intervals = array_slice($intervals, -6);

    if (count($intervals) > 0) {

        $average_cycle = (int) round(
            array_sum($intervals) / count($intervals)
        );

        $last_start = new DateTime(end($unique_dates));

        $next = clone $last_start;
        $next->modify("+{$average_cycle} days");

        $next_period = $next->format("Y-m-d");
        $cycle_status = "Perkiraan berdasarkan riwayat";
    }
}


// AMBIL NAMA PENGGUNA
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$user_name = $user["name"] ?? "Bestie";

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Menstrual Tracker | MySpace</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/menstrual_tracker.css">
</head>

<body>

<div class="page-wrapper">

    <header class="topbar">

        <a href="health_center.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Health Center
        </a>

        <div class="brand">
            <i class="fa-solid fa-heart"></i>
            MySpace
        </div>

    </header>


    <main class="container">

        <!-- HERO -->
        <section class="hero">

            <div class="hero-content">

                <span class="eyebrow">
                    <i class="fa-solid fa-calendar-heart"></i>
                    YOUR PERSONAL CYCLE SPACE
                </span>

                <h1>
                    Understand your cycle,
                    <span>care for yourself.</span>
                </h1>

                <p>
                    Hi, <?= escape($user_name); ?>!
                    Catat siklus menstruasimu dan kenali pola tubuhmu
                    dari waktu ke waktu.
                </p>

            </div>

            <div class="hero-art">
                <i class="fa-solid fa-calendar-heart"></i>
                <span>♥</span>
            </div>

        </section>


        <?php if ($message !== ""): ?>
            <div class="alert <?= $message_type === "error" ? "alert-error" : "alert-success"; ?>">
                <i class="fa-solid <?= $message_type === "error"
                    ? "fa-circle-exclamation"
                    : "fa-circle-check"; ?>"></i>

                <?= escape($message); ?>
            </div>
        <?php endif; ?>


        <!-- RINGKASAN -->
        <section class="summary-grid">

            <div class="summary-card">
                <div class="summary-icon pink">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>

                <span>Total catatan</span>

                <h2><?= count($cycles); ?></h2>

                <small>Catatan menstruasi tersimpan</small>
            </div>


            <div class="summary-card">
                <div class="summary-icon purple">
                    <i class="fa-solid fa-repeat"></i>
                </div>

                <span>Rata-rata siklus</span>

                <h2>
                    <?= $average_cycle !== null
                        ? escape($average_cycle) . " hari"
                        : "—"; ?>
                </h2>

                <small>Perhitungan dari catatan sebelumnya</small>
            </div>


            <div class="summary-card">
                <div class="summary-icon rose">
                    <i class="fa-solid fa-calendar-heart"></i>
                </div>

                <span>Perkiraan berikutnya</span>

                <h2 class="date-value">
                    <?= $next_period
                        ? escape(date("d M Y", strtotime($next_period)))
                        : "—"; ?>
                </h2>

                <small><?= escape($cycle_status); ?></small>
            </div>

        </section>


        <!-- FORM CATATAN -->
        <section class="content-card">

            <div class="section-heading">
                <span class="section-label">YOUR CYCLE JOURNAL</span>
                <h2>Add a period record</h2>
                <p>
                    Masukkan tanggal mulai dan tanggal selesai
                    jika menstruasimu sudah selesai.
                </p>
            </div>

            <form method="POST" action="menstrual_tracker.php">

                <input type="hidden" name="csrf_token"
                       value="<?= escape($_SESSION["csrf_token"]); ?>">

                <input type="hidden" name="action" value="save">

                <div class="form-grid">

                    <div class="form-group">
                        <label for="start_date">
                            Start date <span>*</span>
                        </label>

                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            required
                        >
                    </div>


                    <div class="form-group">
                        <label for="end_date">
                            End date
                        </label>

                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                        >

                        <small>
                            Boleh dikosongkan jika belum selesai.
                        </small>
                    </div>


                    <div class="form-group full-width">
                        <label for="notes">
                            Notes (optional)
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            maxlength="2000"
                            placeholder="Contoh: mengalami kram ringan atau perubahan suasana hati..."
                        ></textarea>
                    </div>

                </div>

                <button type="submit" class="save-button">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save my record
                </button>

            </form>

        </section>


        <!-- RIWAYAT -->
        <section class="content-card history-section">

            <div class="section-heading">
                <span class="section-label">YOUR PERSONAL HISTORY</span>
                <h2>Menstrual history</h2>
                <p>
                    Semua catatan yang tersimpan di akunmu.
                </p>
            </div>


            <?php if (count($cycles) > 0): ?>

                <div class="history-list">

                    <?php foreach ($cycles as $cycle): ?>

                        <article class="history-item">

                            <div class="history-date-icon">
                                <i class="fa-solid fa-droplet"></i>
                            </div>

                            <div class="history-details">

                                <h3>
                                    <?= escape(date(
                                        "d F Y",
                                        strtotime($cycle["start_date"])
                                    )); ?>
                                </h3>

                                <p>
                                    Mulai:
                                    <?= escape(date(
                                        "d M Y",
                                        strtotime($cycle["start_date"])
                                    )); ?>
                                </p>

                                <p>
                                    Selesai:
                                    <?= $cycle["end_date"]
                                        ? escape(date(
                                            "d M Y",
                                            strtotime($cycle["end_date"])
                                        ))
                                        : "Belum dicatat"; ?>
                                </p>

                                <?php if (!empty($cycle["notes"])): ?>
                                    <div class="history-notes">
                                        <?= nl2br(escape($cycle["notes"])); ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <form method="POST"
                                  action="menstrual_tracker.php"
                                  onsubmit="return confirm('Yakin ingin menghapus catatan ini?');">

                                <input type="hidden" name="csrf_token"
                                       value="<?= escape($_SESSION["csrf_token"]); ?>">

                                <input type="hidden" name="action" value="delete">

                                <input type="hidden" name="cycle_id"
                                       value="<?= (int) $cycle["id"]; ?>">

                                <button type="submit" class="delete-button"
                                        aria-label="Hapus catatan">
                                    <i class="fa-solid fa-trash"></i>
                                </button>

                            </form>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-calendar-heart"></i>
                    </div>

                    <h3>No records yet</h3>

                    <p>
                        Belum ada catatan menstruasi.
                        Tambahkan catatan pertamamu melalui formulir di atas.
                    </p>
                </div>

            <?php endif; ?>

        </section>


        <!-- PRIVACY -->
        <section class="privacy-card">

            <i class="fa-solid fa-shield-heart"></i>

            <div>
                <h3>Your privacy matters</h3>

                <p>
                    Catatan menstruasi hanya ditampilkan untuk akun yang
                    membuatnya. Jangan membagikan informasi kesehatan pribadi
                    jika kamu tidak nyaman.
                </p>
            </div>

        </section>


        <!-- DISCLAIMER -->
        <section class="disclaimer">
            <i class="fa-solid fa-circle-info"></i>

            <p>
                Perkiraan tanggal berikutnya dihitung dari rata-rata jarak
                antar tanggal mulai menstruasi yang tercatat. Siklus dapat
                berubah dan perkiraan ini tidak menjamin tanggal menstruasi
                berikutnya. Jangan gunakan sebagai alat kontrasepsi atau
                dasar diagnosis medis.
            </p>
        </section>


        <footer>
            <p>Made with love by MySpace ♡</p>
            <span>Listen to your body, always.</span>
        </footer>

    </main>

</div>

</body>
</html>
