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

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

function redirectDiary(string $query = ""): void
{
    header("Location: diary.php" . ($query !== "" ? "?" . $query : ""));
    exit;
}

function removeDiaryPhoto(?string $photo): void
{
    if (!$photo) {
        return;
    }

    $prefix = "assets/uploads/diary/";
    if (strpos($photo, $prefix) !== 0) {
        return;
    }

    $fullPath = __DIR__ . "/" . $photo;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function uploadDiaryPhoto(array $file): ?string
{
    if (!isset($file["error"]) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Foto gagal diunggah. Silakan coba lagi.");
    }

    if ($file["size"] > 5 * 1024 * 1024) {
        throw new RuntimeException("Ukuran foto maksimal 5 MB.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException("Format foto harus JPG, PNG, atau WEBP.");
    }

    $directory = __DIR__ . "/assets/uploads/diary/";
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException("Folder penyimpanan foto tidak dapat dibuat.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];
    $destination = $directory . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        throw new RuntimeException("Foto tidak berhasil disimpan.");
    }

    return "assets/uploads/diary/" . $filename;
}

function getDiaryEntry(mysqli $conn, int $entry_id, int $user_id): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, title, entry_date, mood, content, photo
         FROM diary_entries
         WHERE id = ? AND user_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "ii", $entry_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $entry = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);

    return $entry;
}

$error_message = "";
$success_message = "";
$editing_entry = null;

// Hapus entri milik pengguna yang sedang login.
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete") {
    if (!hash_equals($_SESSION["csrf_token"], (string) ($_POST["csrf_token"] ?? ""))) {
        http_response_code(403);
        die("Permintaan tidak valid. Muat ulang halaman dan coba lagi.");
    }

    $entry_id = filter_input(INPUT_POST, "entry_id", FILTER_VALIDATE_INT);

    if ($entry_id) {
        $entry = getDiaryEntry($conn, $entry_id, $user_id);

        if ($entry) {
            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM diary_entries WHERE id = ? AND user_id = ?"
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $entry_id, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                removeDiaryPhoto($entry["photo"]);
                redirectDiary("deleted=1");
            }
        }
    }

    redirectDiary("error=delete");
}

// Simpan entri baru atau perubahan entri.
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "save") {
    if (!hash_equals($_SESSION["csrf_token"], (string) ($_POST["csrf_token"] ?? ""))) {
        http_response_code(403);
        die("Permintaan tidak valid. Muat ulang halaman dan coba lagi.");
    }

    $entry_id = (int) ($_POST["entry_id"] ?? 0);
    $title = trim((string) ($_POST["title"] ?? ""));
    $entry_date = trim((string) ($_POST["entry_date"] ?? ""));
    $mood = trim((string) ($_POST["mood"] ?? ""));
    $content = trim((string) ($_POST["content"] ?? ""));

    $valid_moods = ["Bahagia", "Bersyukur", "Tenang", "Biasa saja", "Sedih", "Lelah", "Bersemangat"];

    if ($title === "" || mb_strlen($title) > 150) {
        $error_message = "Judul wajib diisi dan maksimal 150 karakter.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entry_date) ||
        !DateTime::createFromFormat("Y-m-d", $entry_date)) {
        $error_message = "Tanggal diary tidak valid.";
    } elseif (!in_array($mood, $valid_moods, true)) {
        $error_message = "Silakan pilih mood yang tersedia.";
    } elseif ($content === "") {
        $error_message = "Isi diary tidak boleh kosong.";
    } else {
        $existing = $entry_id > 0 ? getDiaryEntry($conn, $entry_id, $user_id) : null;

        if ($entry_id > 0 && !$existing) {
            $error_message = "Catatan tidak ditemukan atau bukan milik akunmu.";
        } else {
            try {
                $new_photo = uploadDiaryPhoto($_FILES["photo"] ?? []);

                if ($entry_id > 0) {
                    $photo = $new_photo ?: ($existing["photo"] ?? null);

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE diary_entries
                         SET title = ?, entry_date = ?, mood = ?, content = ?, photo = ?
                         WHERE id = ? AND user_id = ?"
                    );

                    if (!$stmt) {
                        throw new RuntimeException("Catatan gagal disiapkan untuk diperbarui.");
                    }

                    mysqli_stmt_bind_param(
                        $stmt,
                        "sssssii",
                        $title,
                        $entry_date,
                        $mood,
                        $content,
                        $photo,
                        $entry_id,
                        $user_id
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    if ($new_photo && !empty($existing["photo"])) {
                        removeDiaryPhoto($existing["photo"]);
                    }

                    redirectDiary("updated=1");
                } else {
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO diary_entries
                         (user_id, title, entry_date, mood, content, photo)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    if (!$stmt) {
                        if ($new_photo) {
                            removeDiaryPhoto($new_photo);
                        }
                        throw new RuntimeException("Catatan gagal disiapkan untuk disimpan.");
                    }

                    mysqli_stmt_bind_param(
                        $stmt,
                        "isssss",
                        $user_id,
                        $title,
                        $entry_date,
                        $mood,
                        $content,
                        $new_photo
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    redirectDiary("saved=1");
                }
            } catch (Throwable $exception) {
                $error_message = $exception->getMessage();
            }
        }
    }

    if ($entry_id > 0) {
        $editing_entry = getDiaryEntry($conn, $entry_id, $user_id);
    }
}

// Muat catatan untuk mode edit.
if (isset($_GET["edit"])) {
    $edit_id = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);
    if ($edit_id) {
        $editing_entry = getDiaryEntry($conn, $edit_id, $user_id);
        if (!$editing_entry) {
            $error_message = "Catatan tidak ditemukan.";
        }
    }
}

// Ambil daftar diary milik pengguna ini saja.
$search = trim((string) ($_GET["q"] ?? ""));
$entries = [];

if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, title, entry_date, mood, content, photo
         FROM diary_entries
         WHERE user_id = ? AND (title LIKE ? OR content LIKE ?)
         ORDER BY entry_date DESC, id DESC"
    );

    mysqli_stmt_bind_param($stmt, "iss", $user_id, $like, $like);
} else {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, title, entry_date, mood, content, photo
         FROM diary_entries
         WHERE user_id = ?
         ORDER BY entry_date DESC, id DESC"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);
}

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = $row;
    }

    mysqli_stmt_close($stmt);
}

if (isset($_GET["saved"])) {
    $success_message = "Diary kamu berhasil disimpan. ♡";
} elseif (isset($_GET["updated"])) {
    $success_message = "Diary kamu berhasil diperbarui.";
} elseif (isset($_GET["deleted"])) {
    $success_message = "Diary berhasil dihapus.";
} elseif (isset($_GET["error"])) {
    $error_message = "Catatan tidak dapat dihapus. Silakan coba lagi.";
}

$display_name = e($_SESSION["user_name"] ?? "Dear");
$today = date("Y-m-d");
$moods = ["Bahagia", "Bersyukur", "Tenang", "Biasa saja", "Sedih", "Lelah", "Bersemangat"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Diary & Memories | MySpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #51434d;
            --muted: #8e7e89;
            --pink: #d88eae;
            --pink-dark: #b96e91;
            --soft-pink: #fff0f5;
            --lavender: #f4effa;
            --cream: #fffaf7;
            --line: #f0e5eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background: linear-gradient(135deg, #fff9fb, #f8f4fb 55%, #fffaf5);
            font-family: "DM Sans", sans-serif;
        }
        .diary-shell { max-width: 1180px; margin: 0 auto; padding: 34px 22px 60px; }
        .top-nav { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:34px; }
        .brand { color:var(--ink); text-decoration:none; font-weight:700; font-size:1.2rem; }
        .brand i { color:var(--pink-dark); margin-right:9px; }
        .nav-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .nav-link-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 15px; border:1px solid var(--line); border-radius:999px; background:#fff; color:var(--ink); text-decoration:none; font-size:.9rem; }
        .hero { position:relative; overflow:hidden; padding:38px; border:1px solid #f3e4ec; border-radius:28px; background:linear-gradient(115deg,#fff0f5,#f5effb 70%,#fff8ee); box-shadow:0 14px 40px rgba(121,83,108,.07); }
        .eyebrow { color:var(--pink-dark); font-size:.72rem; font-weight:700; letter-spacing:.16em; }
        h1,h2,h3 { font-family:"Playfair Display",serif; }
        .hero h1 { margin:10px 0; font-size:clamp(2rem,5vw,3.2rem); }
        .hero p { max-width:650px; margin:0; color:var(--muted); line-height:1.8; }
        .hero-flower { position:absolute; right:45px; top:18px; color:rgba(185,110,145,.18); font-size:8rem; }
        .content-grid { display:grid; grid-template-columns:minmax(0, .95fr) minmax(0, 1.05fr); gap:24px; margin-top:26px; align-items:start; }
        .panel { padding:25px; border:1px solid var(--line); border-radius:22px; background:rgba(255,255,255,.9); box-shadow:0 10px 30px rgba(121,83,108,.05); }
        .panel h2 { margin:0 0 7px; font-size:1.55rem; }
        .panel-subtitle { color:var(--muted); font-size:.9rem; margin-bottom:22px; }
        .form-label { color:#6c5966; font-size:.86rem; font-weight:700; }
        .form-control,.form-select { border-color:#eadde5; border-radius:12px; padding:11px 13px; color:var(--ink); }
        .form-control:focus,.form-select:focus { border-color:#d99ab6; box-shadow:0 0 0 .2rem rgba(216,142,174,.14); }
        textarea.form-control { min-height:180px; resize:vertical; line-height:1.7; }
        .btn-diary { border:0; border-radius:999px; padding:11px 20px; color:#fff; background:var(--pink-dark); font-weight:700; }
        .btn-diary:hover { color:#fff; background:#a95f82; }
        .btn-soft { border:1px solid var(--line); border-radius:999px; padding:10px 17px; color:var(--ink); background:#fff; text-decoration:none; }
        .entry-list { display:grid; gap:15px; }
        .entry-card { padding:18px; border:1px solid var(--line); border-radius:17px; background:#fff; }
        .entry-meta { display:flex; justify-content:space-between; align-items:center; gap:10px; color:var(--muted); font-size:.78rem; }
        .mood-pill { display:inline-block; padding:5px 10px; border-radius:999px; color:#a45f80; background:var(--soft-pink); font-size:.75rem; font-weight:700; }
        .entry-card h3 { margin:12px 0 7px; font-size:1.3rem; }
        .entry-content { color:#766874; line-height:1.7; white-space:pre-wrap; overflow-wrap:anywhere; }
        .entry-photo { display:block; width:100%; max-height:260px; object-fit:cover; margin-top:13px; border-radius:13px; }
        .entry-actions { display:flex; gap:8px; margin-top:15px; }
        .entry-actions a,.entry-actions button { border:1px solid var(--line); border-radius:999px; padding:7px 12px; color:var(--ink); background:#fff; text-decoration:none; font-size:.8rem; }
        .entry-actions .delete-btn { color:#b85d76; }
        .empty-state { padding:35px 15px; border:1px dashed #e8d8e2; border-radius:16px; text-align:center; color:var(--muted); }
        .empty-state i { display:block; margin-bottom:12px; color:#d99ab6; font-size:2rem; }
        .search-form { display:flex; gap:8px; margin-bottom:17px; }
        .search-form .form-control { min-width:0; }
        .alert { border:0; border-radius:13px; }
        .current-photo { max-width:100%; max-height:130px; display:block; margin-top:8px; border-radius:12px; }
        .footer-note { margin-top:28px; text-align:center; color:#a08e9a; font-size:.82rem; }
        @media (max-width: 850px) { .content-grid { grid-template-columns:1fr; } .hero { padding:28px 24px; } .hero-flower { right:12px; top:14px; font-size:5rem; } }
        @media (max-width: 520px) { .diary-shell { padding:22px 14px 40px; } .top-nav { align-items:flex-start; flex-direction:column; } .panel { padding:19px; } .entry-meta { align-items:flex-start; flex-direction:column; } }
    </style>
</head>
<body>
<div class="diary-shell">
    <header class="top-nav">
        <a class="brand" href="dashboard.php"><i class="fa-solid fa-heart"></i>MySpace.</a>
        <div class="nav-actions">
            <a class="nav-link-btn" href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a class="nav-link-btn" href="my_finance.php"><i class="fa-solid fa-wallet"></i> My Finance</a>
        </div>
    </header>

    <section class="hero">
        <span class="eyebrow">YOUR PERSONAL MEMORY SPACE</span>
        <h1>My Diary & Memories <span style="color:#d88eae;">♡</span></h1>
        <p>Halo, <?= $display_name ?>. Simpan cerita kecil, perasaan, dan momen berharga dalam satu ruang pribadi. Setiap halaman adalah bagian dari perjalananmu.</p>
        <div class="hero-flower">✿</div>
    </section>

    <?php if ($success_message !== ""): ?>
        <div class="alert alert-success mt-4"><?= e($success_message) ?></div>
    <?php endif; ?>
    <?php if ($error_message !== ""): ?>
        <div class="alert alert-danger mt-4"><?= e($error_message) ?></div>
    <?php endif; ?>

    <div class="content-grid">
        <section class="panel">
            <h2><?= $editing_entry ? "Edit Your Story" : "Write a New Memory" ?></h2>
            <p class="panel-subtitle">Tidak harus sempurna. Tulis saja apa yang ingin kamu ingat.</p>

            <form method="POST" action="diary.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                <input type="hidden" name="entry_id" value="<?= (int) ($editing_entry["id"] ?? 0) ?>">

                <div class="mb-3">
                    <label class="form-label" for="title">Judul cerita</label>
                    <input class="form-control" id="title" name="title" maxlength="150" required
                           placeholder="Contoh: A lovely Sunday"
                           value="<?= e($editing_entry["title"] ?? ($_POST["title"] ?? "")) ?>">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="entry_date">Tanggal</label>
                        <input class="form-control" type="date" id="entry_date" name="entry_date" required
                               value="<?= e($editing_entry["entry_date"] ?? ($_POST["entry_date"] ?? $today)) ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="mood">Bagaimana perasaanmu?</label>
                        <select class="form-select" id="mood" name="mood" required>
                            <option value="">Pilih mood</option>
                            <?php foreach ($moods as $mood_option): ?>
                                <option value="<?= e($mood_option) ?>"
                                    <?= (($editing_entry["mood"] ?? ($_POST["mood"] ?? "")) === $mood_option) ? "selected" : "" ?>>
                                    <?= e($mood_option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="content">Ceritamu</label>
                    <textarea class="form-control" id="content" name="content" required
                              placeholder="Apa yang terjadi hari ini? Apa yang kamu rasakan?"><?= e($editing_entry["content"] ?? ($_POST["content"] ?? "")) ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="photo">Foto kenangan (opsional)</label>
                    <input class="form-control" type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <div class="form-text">JPG, PNG, atau WEBP. Maksimal 5 MB.</div>
                    <?php if (!empty($editing_entry["photo"])): ?>
                        <img class="current-photo" src="<?= e($editing_entry["photo"]) ?>" alt="Foto diary saat ini">
                        <div class="form-text">Pilih foto baru jika ingin mengganti foto ini.</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-diary" type="submit">
                        <i class="fa-solid fa-heart me-2"></i><?= $editing_entry ? "Save Changes" : "Save Memory" ?>
                    </button>
                    <?php if ($editing_entry): ?>
                        <a class="btn-soft" href="diary.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Your Memory Lane</h2>
            <p class="panel-subtitle"><?= count($entries) ?> catatan ditemukan. Semua catatan hanya ditampilkan untuk akunmu.</p>

            <form class="search-form" method="GET" action="diary.php">
                <input class="form-control" type="search" name="q" placeholder="Cari judul atau isi diary..."
                       value="<?= e($search) ?>">
                <button class="btn-diary" type="submit" aria-label="Cari"><i class="fa-solid fa-search"></i></button>
                <?php if ($search !== ""): ?>
                    <a class="btn-soft" href="diary.php">Reset</a>
                <?php endif; ?>
            </form>

            <?php if (count($entries) > 0): ?>
                <div class="entry-list">
                    <?php foreach ($entries as $entry): ?>
                        <article class="entry-card">
                            <div class="entry-meta">
                                <span><i class="fa-regular fa-calendar me-1"></i><?= e(date("d F Y", strtotime($entry["entry_date"]))) ?></span>
                                <span class="mood-pill"><?= e($entry["mood"]) ?></span>
                            </div>
                            <h3><?= e($entry["title"]) ?></h3>
                            <div class="entry-content"><?= e($entry["content"]) ?></div>
                            <?php if (!empty($entry["photo"])): ?>
                                <img class="entry-photo" src="<?= e($entry["photo"]) ?>" alt="Foto kenangan">
                            <?php endif; ?>
                            <div class="entry-actions">
                                <a href="diary.php?edit=<?= (int) $entry["id"] ?>"><i class="fa-solid fa-pen me-1"></i>Edit</a>
                                <form method="POST" action="diary.php" onsubmit="return confirm('Yakin ingin menghapus diary ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="entry_id" value="<?= (int) $entry["id"] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                                    <button class="delete-btn" type="submit"><i class="fa-regular fa-trash-can me-1"></i>Hapus</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-bookmark"></i>
                    <strong>Belum ada kenangan tersimpan.</strong>
                    <div class="mt-2">Mulai tulis cerita pertamamu di formulir ini. ♡</div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="footer-note">Made with <i class="fa-solid fa-heart" style="color:#d88eae;"></i> for every little moment of your life.</div>
</div>
</body>
</html>
