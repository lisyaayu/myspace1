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

$wellness_tips = [
    [
        "icon" => "fa-cloud",
        "title" => "Take a Little Break",
        "description" => "Berikan dirimu waktu untuk berhenti sejenak ketika merasa lelah.",
        "color" => "pink"
    ],
    [
        "icon" => "fa-book-open",
        "title" => "Write Your Feelings",
        "description" => "Menuliskan pikiran dan perasaan bisa menjadi cara untuk mengenali dirimu.",
        "color" => "purple"
    ],
    [
        "icon" => "fa-wind",
        "title" => "Practice Deep Breathing",
        "description" => "Cobalah bernapas perlahan dan nyaman untuk membantu tubuh lebih rileks.",
        "color" => "blue"
    ],
    [
        "icon" => "fa-users",
        "title" => "Stay Connected",
        "description" => "Berbagi cerita dengan orang yang kamu percaya dapat membantu mengurangi rasa sendirian.",
        "color" => "green"
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mental Wellness | MySpace</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/mental_wellness.css">
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
                    <i class="fa-solid fa-sparkles"></i>
                    YOUR MIND DESERVES CARE TOO
                </span>

                <h1>
                    Take a breath,
                    <span>you are doing okay.</span>
                </h1>

                <p>
                    Welcome, <?= escape($user_name); ?>!
                    Tidak apa-apa untuk beristirahat, merasa lelah,
                    atau mengambil waktu untuk dirimu sendiri.
                </p>

                <a href="#mood-section" class="hero-button">
                    Check in with yourself
                    <i class="fa-solid fa-arrow-down"></i>
                </a>

            </div>

            <div class="hero-art">
                <div class="hero-circle">
                    <i class="fa-solid fa-spa"></i>
                </div>

                <span class="decoration decoration-one">✦</span>
                <span class="decoration decoration-two">♥</span>
                <span class="decoration decoration-three">✧</span>
            </div>

        </section>


        <!-- MOOD CHECK-IN -->
        <section class="mood-section" id="mood-section">

            <div class="section-heading">
                <span class="section-label">A MOMENT FOR YOURSELF</span>
                <h2>How are you feeling today?</h2>
                <p>
                    Tidak ada jawaban benar atau salah.
                    Pilih perasaan yang paling menggambarkan dirimu sekarang.
                </p>
            </div>

            <div class="mood-grid">

                <button type="button" class="mood-option"
                        data-mood="Happy" data-message="Senang mendengarnya! Nikmati momen baik hari ini.">
                    <span>😊</span>
                    <strong>Happy</strong>
                </button>

                <button type="button" class="mood-option"
                        data-mood="Calm" data-message="Semoga ketenangan ini menemanimu sepanjang hari.">
                    <span>😌</span>
                    <strong>Calm</strong>
                </button>

                <button type="button" class="mood-option"
                        data-mood="Tired" data-message="Tidak apa-apa untuk memperlambat langkah dan beristirahat.">
                    <span>😴</span>
                    <strong>Tired</strong>
                </button>

                <button type="button" class="mood-option"
                        data-mood="Sad" data-message="Perasaan sedihmu valid. Berikan dirimu ruang untuk merasakannya.">
                    <span>🥺</span>
                    <strong>Sad</strong>
                </button>

                <button type="button" class="mood-option"
                        data-mood="Anxious" data-message="Cobalah fokus pada satu hal kecil yang bisa kamu lakukan sekarang.">
                    <span>😟</span>
                    <strong>Anxious</strong>
                </button>

                <button type="button" class="mood-option"
                        data-mood="Excited" data-message="Wah, semoga semangat positifmu membawa hal baik hari ini!">
                    <span>🤩</span>
                    <strong>Excited</strong>
                </button>

            </div>

            <div class="mood-result" id="moodResult" aria-live="polite">
                <i class="fa-solid fa-heart"></i>
                <span>Pilih mood untuk mendapatkan pesan kecil untukmu.</span>
            </div>

            <p class="privacy-note">
                <i class="fa-solid fa-lock"></i>
                Mood check-in ini hanya ditampilkan sementara di halaman ini
                dan belum disimpan ke database.
            </p>

        </section>


        <!-- SELF CARE -->
        <section class="selfcare-section">

            <div class="section-heading">
                <span class="section-label">LITTLE THINGS THAT MATTER</span>
                <h2>Self-care ideas</h2>
                <p>
                    Beberapa aktivitas sederhana yang bisa kamu coba
                    sesuai kebutuhan dan kenyamananmu.
                </p>
            </div>

            <div class="selfcare-grid">

                <?php foreach ($wellness_tips as $tip): ?>

                    <article class="selfcare-card">

                        <div class="selfcare-icon <?= escape($tip["color"]); ?>">
                            <i class="fa-solid <?= escape($tip["icon"]); ?>"></i>
                        </div>

                        <h3><?= escape($tip["title"]); ?></h3>

                        <p><?= escape($tip["description"]); ?></p>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- BREATHING EXERCISE -->
        <section class="breathing-section">

            <div class="breathing-content">

                <span class="section-label">SLOW DOWN & RELAX</span>

                <h2>A little breathing moment</h2>

                <p>
                    Luangkan waktu sejenak untuk mengikuti latihan
                    pernapasan sederhana. Lakukan dengan nyaman,
                    tanpa memaksakan diri.
                </p>

                <button type="button" id="breathingButton" class="breathing-button">
                    Start breathing exercise
                    <i class="fa-solid fa-play"></i>
                </button>

                <p class="breathing-note" id="breathingNote" aria-live="polite">
                    Kamu bisa berhenti kapan saja jika merasa tidak nyaman.
                </p>

            </div>

            <div class="breathing-visual">

                <div class="breathing-circle" id="breathingCircle">
                    <span id="breathingText">Breathe</span>
                </div>

            </div>

        </section>


        <!-- JOURNAL -->
        <section class="journal-section">

            <div class="section-heading">
                <span class="section-label">A SAFE SPACE FOR YOUR THOUGHTS</span>
                <h2>My little journal</h2>
                <p>
                    Tulis apa yang sedang kamu pikirkan.
                    Kamu tidak perlu menyusun kata-kata dengan sempurna.
                </p>
            </div>

            <div class="journal-card">

                <label for="journalText">
                    What is on your mind today?
                </label>

                <textarea
                    id="journalText"
                    rows="5"
                    maxlength="2000"
                    placeholder="Dear me, today I feel..."
                ></textarea>

                <div class="journal-bottom">

                    <span id="characterCount">0 / 2000 characters</span>

                    <button type="button" id="downloadJournal">
                        <i class="fa-solid fa-download"></i>
                        Download my journal
                    </button>

                </div>

                <p class="journal-privacy">
                    <i class="fa-solid fa-lock"></i>
                    Catatan tidak dikirim atau disimpan ke server.
                    Unduh catatan jika ingin menyimpannya.
                </p>

            </div>

        </section>


        <!-- REMINDER -->
        <section class="reminder">

            <div class="reminder-icon">
                <i class="fa-solid fa-heart"></i>
            </div>

            <div>
                <h3>You deserve kindness, including from yourself.</h3>

                <p>
                    Kamu tidak harus selalu produktif.
                    Istirahat juga merupakan bagian dari perjalananmu.
                </p>
            </div>

        </section>


        <footer>
            <p>Made with love by MySpace ♡</p>
            <span>Your mind matters. Take care of it.</span>
        </footer>

    </main>

</div>


<script>
// MOOD CHECK-IN

const moodOptions = document.querySelectorAll(".mood-option");
const moodResult = document.getElementById("moodResult");

moodOptions.forEach(function(option) {

    option.addEventListener("click", function() {

        moodOptions.forEach(function(item) {
            item.classList.remove("selected");
        });

        option.classList.add("selected");

        const mood = option.dataset.mood;
        const message = option.dataset.message;

        moodResult.innerHTML = "";

        const icon = document.createElement("i");
        icon.className = "fa-solid fa-heart";

        const text = document.createElement("span");
        text.textContent = mood + ": " + message;

        moodResult.appendChild(icon);
        moodResult.appendChild(text);

    });

});


// BREATHING EXERCISE

const breathingButton = document.getElementById("breathingButton");
const breathingCircle = document.getElementById("breathingCircle");
const breathingText = document.getElementById("breathingText");
const breathingNote = document.getElementById("breathingNote");

let breathingTimer = null;
let breathingRunning = false;
let breathingStep = 0;
let breathingCount = 0;

const breathingSteps = [
    {
        text: "Breathe in",
        duration: 4000,
        className: "inhale"
    },
    {
        text: "Breathe out",
        duration: 6000,
        className: "exhale"
    }
];

function stopBreathing() {

    clearTimeout(breathingTimer);

    breathingRunning = false;
    breathingStep = 0;
    breathingCount = 0;

    breathingCircle.classList.remove("inhale", "exhale");

    breathingText.textContent = "Breathe";

    breathingButton.innerHTML =
        'Start breathing exercise <i class="fa-solid fa-play"></i>';

    breathingNote.textContent =
        "Latihan selesai. Terima kasih sudah meluangkan waktu untuk dirimu.";

}

function runBreathing() {

    if (!breathingRunning) return;

    const step = breathingSteps[breathingStep];

    breathingCircle.classList.remove("inhale", "exhale");
    breathingCircle.classList.add(step.className);

    breathingText.textContent = step.text;

    breathingNote.textContent =
        "Ikuti ritme yang nyaman. Jangan memaksakan napas.";

    breathingTimer = setTimeout(function() {

        if (!breathingRunning) return;

        breathingStep++;

        if (breathingStep >= breathingSteps.length) {
            breathingStep = 0;
            breathingCount++;
        }

        if (breathingCount >= 3) {
            stopBreathing();
            return;
        }

        runBreathing();

    }, step.duration);

}

breathingButton.addEventListener("click", function() {

    if (breathingRunning) {
        stopBreathing();
        return;
    }

    breathingRunning = true;
    breathingStep = 0;
    breathingCount = 0;

    breathingButton.innerHTML =
        'Stop exercise <i class="fa-solid fa-stop"></i>';

    runBreathing();

});


// JOURNAL CHARACTER COUNT

const journalText = document.getElementById("journalText");
const characterCount = document.getElementById("characterCount");

journalText.addEventListener("input", function() {

    characterCount.textContent =
        journalText.value.length + " / 2000 characters";

});


// DOWNLOAD JOURNAL

document.getElementById("downloadJournal").addEventListener("click", function() {

    const content = journalText.value.trim();

    if (content === "") {
        alert("Tulis sesuatu terlebih dahulu sebelum mengunduh jurnalmu ya!");
        return;
    }

    const date = new Date().toLocaleDateString("id-ID");

    const journalContent =
        "MYSPACE - MY LITTLE JOURNAL\n" +
        "Date: " + date + "\n\n" +
        content +
        "\n\nMade with love by MySpace.";

    const blob = new Blob(
        [journalContent],
        { type: "text/plain;charset=utf-8" }
    );

    const url = URL.createObjectURL(blob);

    const link = document.createElement("a");

    link.href = url;
    link.download = "myspace-journal.txt";

    document.body.appendChild(link);
    link.click();
    link.remove();

    URL.revokeObjectURL(url);

});

</script>

</body>
</html>