```php
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

$userId = (int) $_SESSION['user_id'];

if (empty($_SESSION['journal_token'])) {
    $_SESSION['journal_token'] = bin2hex(random_bytes(32));
}

$token = $_SESSION['journal_token'];

function journalEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Daftar suasana hati
$moods = [
    'Bahagia',
    'Tenang',
    'Bersyukur',
    'Bingung',
    'Sedih',
    'Kecewa',
    'Cemas',
    'Marah',
    'Lelah',
    'Lainnya'
];

// Proses formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['journal_token'] ?? '';

    if (!hash_equals($token, $submittedToken)) {
        header("Location: relationship_journal.php?status=invalid");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // SIMPAN JURNAL
    if ($action === 'save') {
        $title = trim($_POST['title'] ?? '');
        $mood = trim($_POST['mood'] ?? '');
        $entryDate = trim($_POST['entry_date'] ?? '');
        $content = trim($_POST['content'] ?? '');

        $dateObject = DateTime::createFromFormat('Y-m-d', $entryDate);
        $validDate = $dateObject && $dateObject->format('Y-m-d') === $entryDate;

        if (
            $title === '' ||
            $mood === '' ||
            !$validDate ||
            $content === '' ||
            !in_array($mood, $moods, true)
        ) {
            header("Location: relationship_journal.php?status=invalid");
            exit;
        }

        if (mb_strlen($title) > 150) {
            header("Location: relationship_journal.php?status=invalid");
            exit;
        }

        $stmt = $conn->prepare(
            "INSERT INTO relationship_journals
            (user_id, title, mood, entry_date, content)
            VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            die("Terjadi kesalahan saat menyiapkan penyimpanan jurnal.");
        }

        $stmt->bind_param(
            "issss",
            $userId,
            $title,
            $mood,
            $entryDate,
            $content
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: relationship_journal.php?status=saved");
            exit;
        }

        $stmt->close();
        header("Location: relationship_journal.php?status=error");
        exit;
    }

    // HAPUS JURNAL
    if ($action === 'delete') {
        $journalId = filter_input(INPUT_POST, 'journal_id', FILTER_VALIDATE_INT);

        if (!$journalId) {
            header("Location: relationship_journal.php?status=invalid");
            exit;
        }

        $stmt = $conn->prepare(
            "DELETE FROM relationship_journals
            WHERE id = ? AND user_id = ?"
        );

        if (!$stmt) {
            die("Terjadi kesalahan saat menyiapkan penghapusan jurnal.");
        }

        $stmt->bind_param("ii", $journalId, $userId);
        $stmt->execute();

        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted > 0) {
            header("Location: relationship_journal.php?status=deleted");
        } else {
            header("Location: relationship_journal.php?status=notfound");
        }

        exit;
    }
}

// Ambil daftar jurnal milik pengguna yang sedang login
$stmt = $conn->prepare(
    "SELECT id, title, mood, entry_date, content
    FROM relationship_journals
    WHERE user_id = ?
    ORDER BY entry_date DESC, id DESC"
);

if (!$stmt) {
    die("Terjadi kesalahan saat mengambil data jurnal.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$journals = [];

while ($row = $result->fetch_assoc()) {
    $journals[] = $row;
}

$stmt->close();

// Pesan notifikasi
$status = $_GET['status'] ?? '';

$messages = [
    'saved' => ['success', 'Jurnal berhasil disimpan. Terima kasih sudah meluangkan waktu untuk dirimu sendiri. ♡'],
    'deleted' => ['success', 'Catatan jurnal berhasil dihapus.'],
    'invalid' => ['error', 'Data tidak valid. Silakan periksa kembali formulirmu.'],
    'error' => ['error', 'Jurnal belum berhasil disimpan. Silakan coba lagi.'],
    'notfound' => ['error', 'Catatan jurnal tidak ditemukan.']
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Relationship Journal | MySpace</title>

    <link rel="stylesheet" href="assets/css/relationship_journal.css">
</head>
<body>

<div class="journal-container">

    <!-- HEADER -->
    <header class="journal-header">
        <h1>MySpace ♡</h1>

        <a href="love_relationship.php" class="journal-back">
            ← Kembali ke Love & Relationship
        </a>
    </header>

    <!-- HERO -->
    <section class="journal-hero">
        <span>RUANG UNTUK CERITAMU</span>

        <h2>Relationship Journal</h2>

        <p>
            Tidak semua perasaan harus disimpan sendiri.
            Tuliskan apa yang kamu rasakan, ceritakan pengalamanmu,
            dan berikan waktu untuk memahami dirimu sendiri.
        </p>
    </section>

    <!-- NOTIFICATION -->
    <?php if (isset($messages[$status])): ?>
        <div class="journal-alert <?= journalEscape($messages[$status][0]); ?>">
            <?= journalEscape($messages[$status][1]); ?>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <section class="journal-card">

        <h3>Tulis Ceritamu</h3>

        <p class="journal-description">
            Tidak perlu menulis dengan sempurna. Cukup ceritakan
            apa yang sedang kamu pikirkan dan rasakan.
        </p>

        <form action="relationship_journal.php" method="POST" class="journal-form">

            <input
                type="hidden"
                name="journal_token"
                value="<?= journalEscape($token); ?>"
            >

            <input type="hidden" name="action" value="save">

            <div class="journal-form-group">
                <label for="title">Judul Catatan</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    placeholder="Contoh: Hari yang penuh cerita..."
                    required
                >
            </div>

            <div class="journal-form-group">
                <label for="mood">Bagaimana perasaanmu hari ini?</label>

                <select id="mood" name="mood" required>
                    <option value="">Pilih suasana hati</option>

                    <?php foreach ($moods as $mood): ?>
                        <option value="<?= journalEscape($mood); ?>">
                            <?= journalEscape($mood); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="journal-form-group">
                <label for="entry_date">Tanggal Catatan</label>

                <input
                    type="date"
                    id="entry_date"
                    name="entry_date"
                    value="<?= date('Y-m-d'); ?>"
                    required
                >
            </div>

            <div class="journal-form-group">
                <label for="content">Ceritakan Perasaanmu</label>

                <textarea
                    id="content"
                    name="content"
                    placeholder="Apa yang terjadi hari ini? Apa yang kamu rasakan? Apa yang ingin kamu sampaikan kepada dirimu sendiri?"
                    required
                ></textarea>
            </div>

            <button type="submit" class="journal-submit">
                Simpan Jurnal ♡
            </button>

        </form>
    </section>

    <!-- JOURNAL ENTRIES -->
    <section class="journal-card">

        <h3>Catatan Jurnalmu</h3>

        <p class="journal-description">
            Semua catatan di bawah ini hanya ditampilkan untuk akunmu.
            Kamu bisa membacanya kembali kapan saja.
        </p>

        <?php if (count($journals) > 0): ?>

            <div class="journal-list">

                <?php foreach ($journals as $journal): ?>

                    <article class="journal-entry">

                        <div class="journal-entry-date">
                            <?= journalEscape(date(
                                'd F Y',
                                strtotime($journal['entry_date'])
                            )); ?>
                        </div>

                        <h4><?= journalEscape($journal['title']); ?></h4>

                        <span class="journal-entry-mood">
                            ♡ <?= journalEscape($journal['mood']); ?>
                        </span>

                        <p class="journal-entry-content"><?= journalEscape($journal['content']); ?></p>

                        <form
                            action="relationship_journal.php"
                            method="POST"
                            onsubmit="return confirm('Apakah kamu yakin ingin menghapus catatan ini?');"
                        >
                            <input type="hidden" name="action" value="delete">

                            <input
                                type="hidden"
                                name="journal_token"
                                value="<?= journalEscape($token); ?>"
                            >

                            <input
                                type="hidden"
                                name="journal_id"
                                value="<?= (int) $journal['id']; ?>"
                            >

                            <button type="submit" class="journal-delete">
                                Hapus Catatan
                            </button>
                        </form>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="journal-empty">
                <div class="journal-empty-icon">♡</div>

                <h4>Belum Ada Catatan</h4>

                <p>
                    Kamu belum menulis jurnal apa pun.
                    Mulai dengan menuliskan sedikit cerita tentang harimu hari ini.
                </p>
            </div>

        <?php endif; ?>

    </section>

    <!-- FOOTER -->
    <footer class="journal-footer">
        <p>MySpace © <?= date('Y'); ?> · Setiap perasaanmu berarti ♡</p>
    </footer>

</div>

</body>
</html>
```