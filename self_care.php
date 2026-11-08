
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

$allowedMoods = [
    'Happy',
    'Calm',
    'Grateful',
    'Tired',
    'Stressed',
    'Sad',
    'Anxious',
    'Neutral'
];

$moodLabels = [
    'Happy' => 'Bahagia',
    'Calm' => 'Tenang',
    'Grateful' => 'Bersyukur',
    'Tired' => 'Lelah',
    'Stressed' => 'Stres',
    'Sad' => 'Sedih',
    'Anxious' => 'Cemas',
    'Neutral' => 'Biasa saja'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $activity = trim($_POST['activity_name'] ?? '');
            $date = $_POST['activity_date'] ?? date('Y-m-d');
            $mood = trim($_POST['mood'] ?? '');
            $reflection = trim($_POST['reflection'] ?? '');

            $dateObject = DateTime::createFromFormat('Y-m-d', $date);
            $validDate = $dateObject &&
                $dateObject->format('Y-m-d') === $date;

            if ($activity === '' || strlen($activity) > 150) {
                $error = 'Masukkan nama aktivitas (maksimal 150 karakter).';
            } elseif (!$validDate || $date > date('Y-m-d')) {
                $error = 'Silakan pilih tanggal yang valid.';
            } elseif ($mood !== '' && !in_array($mood, $allowedMoods, true)) {
                $error = 'Silakan pilih suasana hati yang valid.';
            } elseif (strlen($reflection) > 5000) {
                $error = 'Catatan refleksi terlalu panjang.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO self_care_logs
                    (user_id, activity_name, activity_date, mood, reflection)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "issss",
                    $userId,
                    $activity,
                    $date,
                    $mood,
                    $reflection
                );

                if ($stmt->execute()) {
                    header("Location: self_care.php?status=added");
                    exit;
                }

                $error = 'Catatan perawatan diri gagal disimpan.';
            }
        }

        if ($action === 'delete') {
            $id = filter_var(
                $_POST['log_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if ($id) {
                $stmt = $conn->prepare(
                    "DELETE FROM self_care_logs
                     WHERE id = ? AND user_id = ?"
                );
                $stmt->bind_param("ii", $id, $userId);
                $stmt->execute();
            }

            header("Location: self_care.php?status=deleted");
            exit;
        }
    }
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $selectedDate ||
    $selectedDate > date('Y-m-d')
) {
    $selectedDate = date('Y-m-d');
}

$stmt = $conn->prepare(
    "SELECT id, activity_name, activity_date, mood, reflection, created_at
     FROM self_care_logs
     WHERE user_id = ? AND activity_date = ?
     ORDER BY id DESC"
);
$stmt->bind_param("is", $userId, $selectedDate);
$stmt->execute();

$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalActivities = count($logs);
$moodCount = 0;

foreach ($logs as $log) {
    if (!empty($log['mood'])) {
        $moodCount++;
    }
}

$success = '';

if (($_GET['status'] ?? '') === 'added') {
    $success = 'Aktivitas perawatan dirimu berhasil disimpan. Jangan lupa menyayangi dirimu sendiri!';
} elseif (($_GET['status'] ?? '') === 'deleted') {
    $success = 'Catatan berhasil dihapus.';
}

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$userName = $user['name'] ?? 'Sahabat';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Perawatan Diri | MySpace</title>

    <link rel="stylesheet" href="assets/css/self_care.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .idea-card {
            cursor: pointer;
            text-align: left;
            font: inherit;
            color: inherit;
            width: 100%;
            transition: .25s ease;
        }

        .idea-card:focus-visible {
            outline: 3px solid #c87997;
            outline-offset: 4px;
        }

        .idea-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(165, 99, 124, .13);
        }

        .idea-hint {
            display: block;
            margin-top: 12px;
            color: #bd718d;
            font-size: 11px;
            font-weight: 700;
        }

        .idea-modal {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: none;
            place-items: center;
            padding: 20px;
            background: rgba(54, 35, 44, .45);
            backdrop-filter: blur(5px);
        }

        .idea-modal.show {
            display: grid;
        }

        .idea-modal-content {
            width: min(100%, 440px);
            padding: 30px;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 20px 60px rgba(65, 35, 49, .2);
            animation: ideaIn .2s ease;
        }

        @keyframes ideaIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .idea-modal-icon {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            margin-bottom: 16px;
            border-radius: 16px;
            color: #bd718d;
            background: #fff0f4;
            font-size: 21px;
        }

        .idea-modal-content h2 {
            margin: 0 0 10px;
            color: #59434e;
            font-family: "Playfair Display", serif;
        }

        .idea-modal-content p {
            color: #927e87;
            font-size: 13px;
            line-height: 1.8;
        }

        .idea-modal-content ul {
            padding-left: 20px;
            color: #806572;
            font-size: 12px;
            line-height: 2;
        }

        .idea-close {
            width: 100%;
            margin-top: 15px;
            padding: 12px;
            border: 0;
            border-radius: 11px;
            color: #fff;
            background: #c87997;
            font-weight: 700;
            cursor: pointer;
        }

        .idea-close:hover {
            background: #b76585;
        }
    </style>
</head>

<body>

<header class="selfcare-topbar">
    <a href="health_center.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Pusat Kesehatan
    </a>

    <span class="brand">
        MySpace <i class="fa-solid fa-heart"></i>
    </span>
</header>

<main class="selfcare-container">

    <section class="selfcare-hero">
        <div class="hero-content">
            <span class="eyebrow">RUANG KECIL KHUSUS UNTUKMU</span>

            <h1>Berhenti Sejenak. Bernapas. Sayangi Dirimu.</h1>

            <p>
                Halo, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>!
                Kamu berhak mendapatkan waktu untuk beristirahat,
                merasa tenang, dan merawat diri sendiri.
                Luangkan sedikit waktu untuk dirimu hari ini.
            </p>

            <a href="#add-activity" class="hero-button">
                Mulai Perawatan Diri
                <i class="fa-solid fa-arrow-down"></i>
            </a>
        </div>

        <div class="hero-decoration">
            <i class="fa-solid fa-spa"></i>
        </div>
    </section>

    <?php if ($success): ?>
        <div class="notice success">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="selfcare-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-heart"></i>
            </div>
            <div>
                <span>Aktivitas Hari Ini</span>
                <strong><?= $totalActivities ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-face-smile"></i>
            </div>
            <div>
                <span>Catatan Suasana Hati</span>
                <strong><?= $moodCount ?></strong>
            </div>
        </div>
    </section>

    <section class="ideas-section">
        <div class="section-heading">
            <span>SEBUAH PENGINGAT LEMBUT</span>
            <h2>Ide Perawatan Diri</h2>
            <p>Pilih aktivitas sederhana yang membuatmu merasa lebih baik.</p>
        </div>

        <div class="ideas-grid">

            <button type="button" class="idea-card"
                data-title="Membaca Sesuatu"
                data-icon="fa-book-open"
                data-description="Luangkan beberapa menit untuk membaca buku, novel, artikel, atau bacaan yang kamu sukai. Membaca bisa menjadi waktu tenang untuk dirimu sendiri."
                data-tips="Pilih buku atau bacaan favoritmu|Cari tempat yang nyaman dan tenang|Matikan notifikasi ponsel sejenak|Nikmati beberapa halaman tanpa terburu-buru">
                <div class="idea-icon pink">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <h3>Membaca Sesuatu</h3>
                <p>Luangkan waktu untuk menikmati buku atau bacaan kesukaanmu.</p>
                <span class="idea-hint">Lihat ide aktivitas <i class="fa-solid fa-arrow-right"></i></span>
            </button>

            <button type="button" class="idea-card"
                data-title="Mendengarkan Musik"
                data-icon="fa-music"
                data-description="Nikmati musik yang membuatmu merasa nyaman. Kamu bisa memilih lagu yang menenangkan, menyenangkan, atau lagu favorit yang membuat suasana hatimu lebih baik."
                data-tips="Buat daftar lagu favoritmu|Pilih musik sesuai suasana hatimu|Gunakan earphone jika ingin suasana lebih tenang|Nikmati musik tanpa melakukan hal lain sejenak">
                <div class="idea-icon lavender">
                    <i class="fa-solid fa-music"></i>
                </div>
                <h3>Mendengarkan Musik</h3>
                <p>Dengarkan lagu yang menenangkan atau membuatmu bahagia.</p>
                <span class="idea-hint">Lihat ide aktivitas <i class="fa-solid fa-arrow-right"></i></span>
            </button>

            <button type="button" class="idea-card"
                data-title="Menikmati Waktu Istirahat"
                data-icon="fa-mug-hot"
                data-description="Berikan dirimu kesempatan untuk berhenti sejenak dari kesibukan. Nikmati minuman kesukaan dan lepaskan pikiran dari pekerjaan untuk sementara."
                data-tips="Buat teh atau minuman hangat favoritmu|Jauhkan diri dari pekerjaan sebentar|Duduk santai dan atur napas|Nikmati waktu istirahat tanpa merasa bersalah">
                <div class="idea-icon peach">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <h3>Menikmati Waktu Istirahat</h3>
                <p>Berhenti sejenak, nikmati minuman, dan berikan ruang untuk dirimu.</p>
                <span class="idea-hint">Lihat ide aktivitas <i class="fa-solid fa-arrow-right"></i></span>
            </button>

            <button type="button" class="idea-card"
                data-title="Menghabiskan Waktu di Luar"
                data-icon="fa-seedling"
                data-description="Cobalah menikmati udara segar dan suasana di luar ruangan. Kamu bisa berjalan santai, duduk di taman, atau sekadar menikmati pemandangan."
                data-tips="Berjalan santai di sekitar rumah|Duduk sebentar di taman|Nikmati udara segar dan pemandangan|Pilih tempat yang aman dan nyaman">
                <div class="idea-icon green">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <h3>Menghabiskan Waktu di Luar</h3>
                <p>Nikmati udara segar atau berjalan santai jika memungkinkan.</p>
                <span class="idea-hint">Lihat ide aktivitas <i class="fa-solid fa-arrow-right"></i></span>
            </button>

            <button type="button" class="idea-card"
                data-title="Refleksi Diri"
                data-icon="fa-person-praying"
                data-description="Ambil waktu untuk mengenali perasaan dan pikiranmu. Tidak perlu menghakimi diri sendiri. Cukup perhatikan apa yang sedang kamu rasakan."
                data-tips="Tanyakan pada dirimu bagaimana perasaanmu hari ini|Tuliskan hal yang sedang kamu pikirkan|Ingat satu hal yang kamu syukuri|Terima perasaanmu tanpa menyalahkan diri sendiri">
                <div class="idea-icon blue">
                    <i class="fa-solid fa-person-praying"></i>
                </div>
                <h3>Refleksi Diri</h3>
                <p>Kenali perasaanmu dan terima dirimu tanpa menghakimi.</p>
                <span class="idea-hint">Lihat ide aktivitas <i class="fa-solid fa-arrow-right"></i></span>
            </button>

            <button type="button" class="idea-card"
                data-title="Beristirahat dengan Baik"
                data-icon="fa-bed"
                data-description="Tubuh dan pikiranmu membutuhkan waktu untuk beristirahat. Berikan dirimu kesempatan untuk tidur atau bersantai ketika merasa lelah."
                data-tips="Kurangi penggunaan ponsel sebelum tidur|Buat suasana kamar lebih nyaman|Lakukan kegiatan santai sebelum tidur|Berikan tubuhmu waktu istirahat yang cukup">
                <div class="idea-icon yellow">
                    <i class="fa-solid fa-bed"></i>
                </div>
                <h3>Beristirahat dengan Baik</h3>
                <p>Berikan dirimu waktu untuk beristirahat saat tubuh membutuhkannya.</p>
                <span class="idea-hint">Lihat ide aktivitas <i class="fa-solid fa-arrow-right"></i></span>
            </button>

        </div>
    </section>

    <!-- JENDELA INFORMASI IDE PERAWATAN DIRI -->
    <div class="idea-modal" id="ideaModal" role="dialog"
         aria-modal="true" aria-labelledby="ideaModalTitle">

        <div class="idea-modal-content">
            <div class="idea-modal-icon" id="ideaModalIcon">
                <i class="fa-solid fa-heart"></i>
            </div>

            <h2 id="ideaModalTitle">Ide Perawatan Diri</h2>

            <p id="ideaModalDescription"></p>

            <strong>Hal sederhana yang bisa kamu lakukan:</strong>

            <ul id="ideaModalTips"></ul>

            <button type="button" class="idea-close" id="closeIdeaModal">
                Mengerti, Terima Kasih
            </button>
        </div>
    </div>

    <section class="selfcare-layout" id="add-activity">

        <div class="selfcare-form-card">
            <span class="small-label">CATATAN PRIBADIMU</span>
            <h2>Catat Perawatan Dirimu</h2>

            <p class="muted">
                Apa yang sudah kamu lakukan untuk dirimu hari ini?
            </p>

            <form method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <input type="hidden" name="action" value="add">

                <label for="activity_name">Nama aktivitas</label>

                <input
                    type="text"
                    id="activity_name"
                    name="activity_name"
                    maxlength="150"
                    placeholder="Contoh: Berjalan santai"
                    required
                >

                <label for="activity_date">Tanggal aktivitas</label>

                <input
                    type="date"
                    id="activity_date"
                    name="activity_date"
                    value="<?= htmlspecialchars($selectedDate) ?>"
                    max="<?= date('Y-m-d') ?>"
                    required
                >

                <label for="mood">Bagaimana perasaanmu?</label>

                <select name="mood" id="mood">
                    <option value="">Pilih suasana hati (opsional)</option>
                    <?php foreach ($allowedMoods as $moodOption): ?>
                        <option value="<?= htmlspecialchars($moodOption) ?>">
                            <?= htmlspecialchars($moodLabels[$moodOption]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="reflection">Catatan atau refleksi dirimu</label>

                <textarea
                    id="reflection"
                    name="reflection"
                    rows="5"
                    maxlength="5000"
                    placeholder="Tuliskan hal yang ingin kamu ingat..."
                ></textarea>

                <button type="submit" class="submit-button">
                    <i class="fa-solid fa-heart"></i>
                    Simpan Catatan
                </button>
            </form>
        </div>

        <div class="selfcare-history-card">
            <div class="history-heading">
                <div>
                    <span class="small-label">PERJALANAN PRIBADIMU</span>
                    <h2>Jurnal Perawatan Diri</h2>
                </div>

                <form method="GET">
                    <input
                        type="date"
                        name="date"
                        value="<?= htmlspecialchars($selectedDate) ?>"
                        max="<?= date('Y-m-d') ?>"
                        onchange="this.form.submit()"
                    >
                </form>
            </div>

            <?php if (empty($logs)): ?>

                <div class="empty-state">
                    <i class="fa-solid fa-feather-pointed"></i>
                    <h3>Ruang catatanmu masih kosong</h3>
                    <p>
                        Catatan perawatan dirimu akan muncul di sini
                        setelah kamu menyimpan aktivitas pertamamu.
                    </p>
                </div>

            <?php else: ?>

                <div class="journal-list">

                    <?php foreach ($logs as $log): ?>

                        <article class="journal-item">

                            <div class="journal-icon">
                                <i class="fa-solid fa-heart"></i>
                            </div>

                            <div class="journal-content">
                                <h3>
                                    <?= htmlspecialchars($log['activity_name']) ?>
                                </h3>

                                <span class="journal-time">
                                    <?= date('H:i', strtotime($log['created_at'])) ?>
                                </span>

                                <?php if (!empty($log['mood'])): ?>
                                    <span class="mood-tag">
                                        <i class="fa-regular fa-face-smile"></i>
                                        <?= htmlspecialchars(
                                            $moodLabels[$log['mood']] ?? $log['mood']
                                        ) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($log['reflection'])): ?>
                                    <p>
                                        <?= nl2br(htmlspecialchars($log['reflection'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <form method="POST"
                                  onsubmit="return confirm('Apakah kamu yakin ingin menghapus catatan ini?');">

                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                <input type="hidden" name="action" value="delete">

                                <input type="hidden" name="log_id"
                                       value="<?= (int)$log['id'] ?>">

                                <button type="submit" class="delete-button"
                                        aria-label="Hapus catatan">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </div>

    </section>

    <section class="closing-message">
        <i class="fa-solid fa-heart"></i>

        <h2>Kamu berhak untuk beristirahat.</h2>

        <p>
            Nilai dirimu tidak ditentukan oleh seberapa banyak
            hal yang berhasil kamu lakukan.
            Beristirahat juga merupakan bagian dari menyayangi diri sendiri.
        </p>
    </section>

    <footer>
        MySpace - Ruang Perawatan Diri · Pengingat lembut untuk menyayangi dirimu.
    </footer>

</main>

<script>
const ideaModal = document.getElementById('ideaModal');
const ideaModalTitle = document.getElementById('ideaModalTitle');
const ideaModalDescription = document.getElementById('ideaModalDescription');
const ideaModalTips = document.getElementById('ideaModalTips');
const ideaModalIcon = document.getElementById('ideaModalIcon');
const closeIdeaModal = document.getElementById('closeIdeaModal');

let previousFocus = null;

document.querySelectorAll('.idea-card').forEach(card => {
    card.addEventListener('click', function () {
        previousFocus = this;

        const title = this.dataset.title;
        const icon = this.dataset.icon;
        const description = this.dataset.description;
        const tips = this.dataset.tips.split('|');

        ideaModalTitle.textContent = title;
        ideaModalDescription.textContent = description;

        ideaModalIcon.innerHTML = '';
        const iconElement = document.createElement('i');
        iconElement.className = 'fa-solid ' + icon;
        ideaModalIcon.appendChild(iconElement);

        ideaModalTips.innerHTML = '';

        tips.forEach(tip => {
            const li = document.createElement('li');
            li.textContent = tip;
            ideaModalTips.appendChild(li);
        });

        ideaModal.classList.add('show');
        closeIdeaModal.focus();
    });
});

function closeIdea() {
    ideaModal.classList.remove('show');

    if (previousFocus) {
        previousFocus.focus();
    }
}

closeIdeaModal.addEventListener('click', closeIdea);

ideaModal.addEventListener('click', function (event) {
    if (event.target === ideaModal) {
        closeIdea();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && ideaModal.classList.contains('show')) {
        closeIdea();
    }
});
</script>

</body>
</html>
```