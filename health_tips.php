
<?php
session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$user_name = $user["name"] ?? "Bestie";

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$health_tips = [
    [
        "category" => "KESEHATAN SEHARI-HARI",
        "title" => "Awali Hari dengan Kebiasaan Sehat",
        "description" => "Mulai hari dengan rutinitas sederhana yang membantu tubuh dan pikiran terasa lebih siap menjalani aktivitas.",
        "icon" => "fa-sun",
        "color" => "pink",
        "tips" => [
            "Bangun dan tidur pada waktu yang relatif konsisten.",
            "Luangkan waktu untuk peregangan ringan.",
            "Sarapan dengan makanan yang beragam dan bergizi.",
            "Beri diri sendiri waktu untuk memulai hari tanpa terburu-buru."
        ]
    ],
    [
        "category" => "HIDRASI",
        "title" => "Jangan Lupa Minum Air Putih",
        "description" => "Menjaga asupan cairan membantu tubuh menjalankan berbagai fungsi sehari-hari.",
        "icon" => "fa-glass-water",
        "color" => "blue",
        "tips" => [
            "Sediakan botol minum agar air mudah dijangkau.",
            "Minumlah secara berkala sepanjang hari.",
            "Perhatikan rasa haus dan kondisi tubuh.",
            "Sesuaikan kebutuhan cairan dengan aktivitas dan kondisi kesehatan."
        ]
    ],
    [
        "category" => "NUTRISI",
        "title" => "Terapkan Pola Makan Seimbang",
        "description" => "Pola makan seimbang tidak harus rumit. Cobalah mengonsumsi makanan yang bervariasi.",
        "icon" => "fa-carrot",
        "color" => "orange",
        "tips" => [
            "Sertakan sayur dan buah dalam menu harian.",
            "Pilih sumber protein yang beragam.",
            "Konsumsi sumber karbohidrat sesuai kebutuhan.",
            "Nikmati makanan dengan perlahan dan perhatikan rasa kenyang."
        ]
    ],
    [
        "category" => "KESEHATAN MENTAL",
        "title" => "Berikan Waktu Istirahat untuk Pikiranmu",
        "description" => "Istirahat mental juga bagian penting dari menjaga kesehatan diri.",
        "icon" => "fa-brain",
        "color" => "purple",
        "tips" => [
            "Ambil jeda singkat ketika merasa lelah.",
            "Coba latihan pernapasan yang nyaman.",
            "Batasi paparan layar ketika sedang beristirahat.",
            "Ceritakan perasaan kepada orang yang dipercaya jika dibutuhkan."
        ]
    ],
    [
        "category" => "AKTIVITAS FISIK",
        "title" => "Gerakkan Tubuhmu Setiap Hari",
        "description" => "Aktivitas fisik dapat dimulai dari gerakan sederhana yang sesuai dengan kemampuan tubuh.",
        "icon" => "fa-person-walking",
        "color" => "green",
        "tips" => [
            "Berjalan kaki jika memungkinkan.",
            "Lakukan peregangan setelah duduk cukup lama.",
            "Pilih aktivitas fisik yang kamu sukai.",
            "Tingkatkan aktivitas secara bertahap sesuai kemampuan."
        ]
    ],
    [
        "category" => "ISTIRAHAT & TIDUR",
        "title" => "Luangkan Waktu untuk Tidur Berkualitas",
        "description" => "Tidur yang cukup dan teratur mendukung pemulihan tubuh serta keseharian yang lebih nyaman.",
        "icon" => "fa-moon",
        "color" => "rose",
        "tips" => [
            "Buat rutinitas sebelum tidur yang menenangkan.",
            "Usahakan jadwal tidur yang konsisten.",
            "Buat kamar tidur nyaman dan tidak terlalu terang.",
            "Kurangi penggunaan perangkat elektronik menjelang tidur."
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tips Kesehatan | MySpace</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/health_tips.css">
</head>

<body>

<div class="page-wrapper">

    <header class="topbar">
        <a href="health_center.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke Pusat Kesehatan
        </a>

        <div class="brand">
            <i class="fa-solid fa-heart"></i>
            MySpace
        </div>
    </header>

    <main class="container">

        <section class="hero">
            <div class="hero-content">
                <span class="eyebrow">
                    <i class="fa-solid fa-sparkles"></i>
                    PENGETAHUAN KECIL, HIDUP LEBIH SEHAT
                </span>

                <h1>
                    Tips Kesehatan untuk
                    <span>Kehidupan Sehari-hari</span>
                </h1>

                <p>
                    Halo, <?= escape($user_name); ?>! Temukan inspirasi
                    sederhana untuk merawat tubuh, pikiran, dan keseharianmu.
                </p>
            </div>

            <div class="hero-art">
                <div class="art-circle">
                    <i class="fa-solid fa-heart-pulse"></i>
                </div>
                <span class="art-star star-one">✦</span>
                <span class="art-star star-two">♥</span>
                <span class="art-star star-three">✧</span>
            </div>
        </section>

        <section class="intro">
            <div class="intro-icon">
                <i class="fa-solid fa-book-open"></i>
            </div>

            <div>
                <h2>Langkah Kecil Itu Berarti!</h2>
                <p>
                    Kamu tidak harus mengubah semuanya sekaligus.
                    Mulailah dari kebiasaan kecil yang terasa nyaman dan realistis.
                </p>
            </div>
        </section>

        <section class="tips-section">

            <div class="section-heading">
                <div>
                    <span class="section-label">KUMPULAN INFORMASI KESEHATANMU</span>
                    <h2>Jelajahi Tips Kesehatan</h2>
                    <p>Pilih topik yang ingin kamu pelajari hari ini.</p>
                </div>
            </div>

            <div class="tips-grid">

                <?php foreach ($health_tips as $index => $tip): ?>

                    <article class="tip-card">

                        <div class="tip-card-top">
                            <div class="tip-icon <?= escape($tip["color"]); ?>">
                                <i class="fa-solid <?= escape($tip["icon"]); ?>"></i>
                            </div>

                            <span class="tip-number">
                                <?= str_pad($index + 1, 2, "0", STR_PAD_LEFT); ?>
                            </span>
                        </div>

                        <span class="tip-category">
                            <?= escape($tip["category"]); ?>
                        </span>

                        <h3><?= escape($tip["title"]); ?></h3>

                        <p class="tip-description">
                            <?= escape($tip["description"]); ?>
                        </p>

                        <button
                            type="button"
                            class="read-button"
                            onclick="openTip(<?= $index; ?>)"
                        >
                            Baca Tips
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

        <section class="bottom-reminder">
            <i class="fa-solid fa-heart"></i>

            <div>
                <h3>Pengingat Kecil untukmu</h3>
                <p>
                    Setiap orang memiliki kebutuhan kesehatan yang berbeda.
                    Pilih kebiasaan yang sesuai dengan kondisi tubuhmu.
                </p>
            </div>
        </section>

        <footer>
            <p>Dibuat dengan cinta oleh MySpace ♡</p>
            <span>Kesehatanmu selalu berarti.</span>
        </footer>

    </main>

</div>


<!-- MODAL DETAIL TIPS -->
<div class="modal-overlay" id="tipModal" onclick="closeTipOutside(event)">

    <div class="modal-box">

        <button type="button" class="close-modal" onclick="closeTip()">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-icon" id="modalIcon">
            <i class="fa-solid fa-heart"></i>
        </div>

        <span class="modal-category" id="modalCategory"></span>

        <h2 id="modalTitle"></h2>

        <p id="modalDescription" class="modal-description"></p>

        <h4>Langkah kecil yang bisa kamu coba:</h4>

        <ul id="modalTips"></ul>

        <p class="modal-note">
            <i class="fa-solid fa-heart"></i>
            Jangan lupa menyayangi dirimu sendiri. Semua proses membutuhkan waktu.
        </p>

    </div>

</div>


<script>
const healthTips = <?= json_encode(
    $health_tips,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
); ?>;

function openTip(index) {
    const tip = healthTips[index];

    if (!tip) return;

    document.getElementById("modalCategory").textContent = tip.category;
    document.getElementById("modalTitle").textContent = tip.title;
    document.getElementById("modalDescription").textContent = tip.description;

    document.getElementById("modalIcon").innerHTML =
        '<i class="fa-solid ' + tip.icon + '"></i>';

    const list = document.getElementById("modalTips");
    list.innerHTML = "";

    tip.tips.forEach(function(item) {
        const li = document.createElement("li");
        li.textContent = item;
        list.appendChild(li);
    });

    document.getElementById("tipModal").classList.add("show");
    document.body.style.overflow = "hidden";
}

function closeTip() {
    document.getElementById("tipModal").classList.remove("show");
    document.body.style.overflow = "";
}

function closeTipOutside(event) {
    if (event.target.id === "tipModal") {
        closeTip();
    }
}

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeTip();
    }
});
</script>

</body>
</html>
```