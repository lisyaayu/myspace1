
<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$name = "Bestie";

$stmt = $conn->prepare(
    "SELECT name FROM users WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $name = $user['name'] ?: "Bestie";
}
$stmt->close();

$guides = [
    [
        'category' => 'Kesehatan Fisik',
        'icon' => 'fa-heart-pulse',
        'title' => 'Jaga Kesehatan Jantungmu',
        'description' => 'Aktivitas fisik secara teratur, makanan bergizi seimbang, menghindari tembakau, dan pemeriksaan kesehatan rutin dapat membantu menjaga kesehatan jantung.',
        'items' => [
            'Tetap aktif secara fisik sesuai dengan kemampuanmu.',
            'Pilih beragam makanan yang bergizi.',
            'Hindari merokok dan cari dukungan jika ingin berhenti.',
            'Konsultasikan pemeriksaan tekanan darah dan kolesterol dengan tenaga kesehatan.'
        ]
    ],
    [
        'category' => 'Kesehatan Mental',
        'icon' => 'fa-brain',
        'title' => 'Kesehatan Mentalmu Itu Penting',
        'description' => 'Kesehatan mental perlu mendapatkan perhatian yang sama seperti kesehatan fisik. Kamu tidak harus menghadapi setiap tantangan sendirian.',
        'items' => [
            'Luangkan waktu untuk beristirahat dan melakukan kegiatan yang kamu sukai.',
            'Bicaralah dengan seseorang yang kamu percaya ketika menghadapi kesulitan.',
            'Perhatikan perubahan suasana hati, pola tidur, atau rutinitas harianmu.',
            'Pertimbangkan bantuan profesional jika kesulitan emosional terus berlanjut.'
        ]
    ],
    [
        'category' => 'Kebugaran & Aktivitas Fisik',
        'icon' => 'fa-dumbbell',
        'title' => 'Bangun Rutinitas Olahraga yang Sehat',
        'description' => 'Rutinitas yang realistis dapat mencakup aktivitas aerobik, latihan kekuatan, latihan kelenturan, dan waktu pemulihan yang cukup.',
        'items' => [
            'Mulailah dari tingkat aktivitas yang terasa nyaman untukmu.',
            'Tingkatkan aktivitas secara bertahap dan jangan memaksakan diri.',
            'Sediakan waktu istirahat dan pemulihan dalam rutinitasmu.',
            'Hentikan olahraga dan cari saran medis jika mengalami gejala yang mengkhawatirkan.'
        ]
    ],
    [
        'category' => 'Perawatan Pencegahan',
        'icon' => 'fa-user-doctor',
        'title' => 'Jangan Lewatkan Pemeriksaan Kesehatan',
        'description' => 'Perawatan pencegahan membantu mendeteksi masalah kesehatan sejak dini. Jenis pemeriksaan yang tepat bergantung pada usia, riwayat kesehatan, dan faktor risiko masing-masing.',
        'items' => [
            'Diskusikan pemeriksaan tekanan darah dengan tenaga kesehatan.',
            'Tanyakan apakah pemeriksaan kolesterol atau gula darah diperlukan.',
            'Pastikan vaksinasi tetap diperbarui sesuai anjuran setempat.',
            'Ceritakan riwayat kesehatan keluarga dan faktor risiko pribadi saat berkonsultasi.'
        ]
    ],
    [
        'category' => 'Kesehatan Sehari-hari',
        'icon' => 'fa-moon',
        'title' => 'Berikan Tubuhmu Waktu Istirahat yang Cukup',
        'description' => 'Tidur dan pemulihan yang cukup mendukung aktivitas sehari-hari, suasana hati, dan kesehatan fisik.',
        'items' => [
            'Usahakan memiliki jadwal tidur yang teratur.',
            'Buat rutinitas yang menenangkan sebelum tidur.',
            'Batasi konsumsi kafein pada sore atau malam hari jika mengganggu tidurmu.',
            'Konsultasikan masalah tidur yang berkepanjangan dengan tenaga kesehatan.'
        ]
    ],
    [
        'category' => 'Kesehatan Pribadi',
        'icon' => 'fa-shield-heart',
        'title' => 'Kenali Tubuhmu',
        'description' => 'Perhatikan perubahan pada tubuhmu dan cari saran medis ketika ada sesuatu yang terasa tidak biasa atau mengkhawatirkan.',
        'items' => [
            'Jangan abaikan rasa sakit yang terus berlangsung atau gejala yang tidak biasa.',
            'Diskusikan masalah kesehatan seksual dan reproduksi secara pribadi dengan tenaga profesional.',
            'Tanyakan rekomendasi pemeriksaan kesehatan yang sesuai dengan usia dan riwayat kesehatanmu.',
            'Segera cari pertolongan medis jika mengalami gejala yang parah atau muncul secara tiba-tiba.'
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panduan Kesehatan Pria | MySpace</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/mens_health.css">

    <!-- PERBAIKAN LAYOUT TANPA SIDEBAR -->
    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Menghilangkan layout dua kolom bekas sidebar */
        .page-wrapper {
            display: block !important;
            grid-template-columns: none !important;
            grid-template-areas: none !important;
            width: 100% !important;
            min-height: 100vh;
            margin: 0 !important;
            padding: 0 !important;
            gap: 0 !important;
        }

        /* Konten memenuhi halaman dan berada di tengah */
        .main-content {
            display: block !important;
            grid-column: 1 / -1 !important;
            grid-area: auto !important;
            width: 100% !important;
            max-width: 1250px !important;
            min-height: 100vh;
            margin: 0 auto !important;
            padding: 30px 40px !important;
            box-sizing: border-box;
            float: none !important;
            left: auto !important;
            position: relative;
        }

        /* Bagian atas */
        .topbar {
            width: 100%;
            box-sizing: border-box;
        }

        /* Hero */
        .hero {
            width: 100%;
            box-sizing: border-box;
        }

        /* Bagian pengantar */
        .intro {
            width: 100%;
            box-sizing: border-box;
        }

        /* Kartu panduan */
        .guides-section {
            width: 100%;
            box-sizing: border-box;
        }

        .guide-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            width: 100%;
        }

        .guide-card {
            min-width: 0;
            box-sizing: border-box;
        }

        .professional-note {
            width: 100%;
            box-sizing: border-box;
        }

        footer {
            width: 100%;
            box-sizing: border-box;
            text-align: center;
        }

        /* Tablet */
        @media (max-width: 900px) {
            .main-content {
                padding: 25px 24px !important;
            }

            .guide-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }
        }

        /* HP */
        @media (max-width: 600px) {
            .main-content {
                padding: 18px 15px !important;
            }

            .guide-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .topbar {
                gap: 12px;
                flex-wrap: wrap;
            }

            .hero {
                padding: 25px 20px;
            }

            .hero-art {
                display: none;
            }

            .professional-note {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<div class="page-wrapper">

    <main class="main-content">

        <header class="topbar">

            <div>
                <span class="eyebrow">PUSAT KESEHATAN / KESEHATAN PRIA</span>
                <h2>Panduan Kesehatan Pria</h2>
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

                <span class="hero-tag">KESEHATANMU ADALAH PRIORITAS</span>

                <h1>
                    Pikiran kuat.<br>
                    Tubuh sehat. Pribadi lebih baik.
                </h1>

                <p>
                    Jelajahi informasi praktis seputar kesehatan pria,
                    mulai dari kebiasaan sehari-hari hingga perawatan
                    dan pemeriksaan kesehatan untuk pencegahan.
                </p>

                <a href="#guides" class="hero-button">
                    Jelajahi Panduan
                    <i class="fa-solid fa-arrow-down"></i>
                </a>

            </div>

            <div class="hero-art">
                <i class="fa-solid fa-shield-heart"></i>
                <span>Jadilah kuat<br>dengan menjaga kesehatan</span>
            </div>

        </section>

        <section class="intro">

            <span class="eyebrow">HIDUP SEHAT SETIAP HARI</span>

            <h2>
                Merawat diri sendiri dimulai dari langkah-langkah kecil.
            </h2>

            <p>
                Kesehatan bukan hanya tentang olahraga.
                Kesehatan juga mencakup kesejahteraan mental,
                perawatan pencegahan, tidur yang cukup, serta
                mengetahui kapan harus meminta bantuan profesional.
            </p>

        </section>

        <section class="guides-section" id="guides">

            <div class="section-heading">

                <div>
                    <span class="eyebrow">PELAJARI & LAKUKAN</span>
                    <h2>Jelajahi Topik Kesehatan</h2>
                    <p>
                        Pengingat sederhana untuk membantumu
                        membuat pilihan yang tepat bagi kesehatan.
                    </p>
                </div>

            </div>

            <div class="guide-grid">

                <?php foreach ($guides as $index => $guide): ?>

                    <article class="guide-card">

                        <div class="guide-top">

                            <div class="guide-icon">
                                <i class="fa-solid <?= e($guide['icon']) ?>"></i>
                            </div>

                            <span class="guide-number">
                                <?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?>
                            </span>

                        </div>

                        <span class="category">
                            <?= e($guide['category']) ?>
                        </span>

                        <h3><?= e($guide['title']) ?></h3>

                        <p class="description">
                            <?= e($guide['description']) ?>
                        </p>

                        <ul>

                            <?php foreach ($guide['items'] as $item): ?>

                                <li>
                                    <i class="fa-solid fa-check"></i>
                                    <?= e($item) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

        <section class="professional-note">

            <div class="note-icon">
                <i class="fa-solid fa-user-doctor"></i>
            </div>

            <div>

                <h3>
                    Kapan sebaiknya mencari bantuan tenaga kesehatan?
                </h3>

                <p>
                    Jika kamu mengalami gejala yang terus berlangsung,
                    perubahan kondisi tubuh yang mengkhawatirkan,
                    atau memiliki pertanyaan tentang pemeriksaan dan
                    pengobatan, konsultasikan dengan tenaga kesehatan
                    yang berkualifikasi. Untuk gejala yang parah atau
                    muncul secara tiba-tiba, segera cari pertolongan medis.
                </p>

            </div>

        </section>

        <footer>
            Dibuat dengan <i class="fa-solid fa-heart"></i>
            untuk kesehatan dan kesejahteraanmu · MySpace
        </footer>

    </main>

</div>

</body>
</html>
