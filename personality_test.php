
<?php
session_start();
require_once "config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];
$errorMessage = "";

// Ambil kategori tes dari URL
$test_slug = $_GET["test"] ?? "";

$allowed_tests = [
    "personality",
    "interests",
    "strengths",
    "love-language"
];

if (!in_array($test_slug, $allowed_tests, true)) {
    header("Location: know_me.php");
    exit;
}

// Ambil informasi tes
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, slug, title, description
     FROM knowme_tests
     WHERE slug = ? AND is_active = 1
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $test_slug);
mysqli_stmt_execute($stmt);

$test_result = mysqli_stmt_get_result($stmt);
$test = mysqli_fetch_assoc($test_result);

mysqli_stmt_close($stmt);

if (!$test) {
    header("Location: know_me.php");
    exit;
}

$test_id = (int) $test["id"];

// Ambil pertanyaan aktif
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, question_text, question_order
     FROM knowme_questions
     WHERE test_id = ? AND is_active = 1
     ORDER BY question_order ASC"
);

mysqli_stmt_bind_param($stmt, "i", $test_id);
mysqli_stmt_execute($stmt);

$question_result = mysqli_stmt_get_result($stmt);

$questions = [];

while ($question = mysqli_fetch_assoc($question_result)) {

    $question_id = (int) $question["id"];

    // Ambil pilihan jawaban
    $option_stmt = mysqli_prepare(
        $conn,
        "SELECT id, option_text, option_order
         FROM knowme_options
         WHERE question_id = ?
         ORDER BY option_order ASC"
    );

    mysqli_stmt_bind_param($option_stmt, "i", $question_id);
    mysqli_stmt_execute($option_stmt);

    $option_result = mysqli_stmt_get_result($option_stmt);

    $options = [];

    while ($option = mysqli_fetch_assoc($option_result)) {
        $options[] = $option;
    }

    mysqli_stmt_close($option_stmt);

    $question["options"] = $options;

    $questions[] = $question;
}

mysqli_stmt_close($stmt);

$total_questions = count($questions);

if ($total_questions === 0) {
    die("Belum ada pertanyaan aktif untuk tes ini.");
}

// Proses penyimpanan jawaban
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($csrf_token, $posted_token)) {

        $errorMessage = "Sesi tidak valid. Silakan muat ulang halaman.";

    } else {

        $answers_json = $_POST["answers"] ?? "";

        $answers = json_decode($answers_json, true);

        if (!is_array($answers) || count($answers) !== $total_questions) {

            $errorMessage = "Semua pertanyaan wajib dijawab.";

        } else {

            $question_ids = [];

            foreach ($questions as $question) {
                $question_ids[] = (int) $question["id"];
            }

            // Pastikan seluruh pertanyaan dijawab
            $valid_answers = true;

            foreach ($question_ids as $question_id) {

                if (
                    !isset($answers[$question_id]) ||
                    !is_numeric($answers[$question_id])
                ) {
                    $valid_answers = false;
                    break;
                }
            }

            if (!$valid_answers) {

                $errorMessage = "Pastikan semua pertanyaan sudah dijawab.";

            } else {

                $totals = [];

                $validated_answers = [];

                // Validasi pilihan jawaban dan hitung skor
                foreach ($question_ids as $question_id) {

                    $option_id = (int) $answers[$question_id];

                    $check_stmt = mysqli_prepare(
                        $conn,
                        "SELECT o.id, o.result_key, o.score
                         FROM knowme_options o
                         INNER JOIN knowme_questions q
                            ON q.id = o.question_id
                         WHERE o.id = ?
                           AND q.id = ?
                           AND q.test_id = ?
                           AND q.is_active = 1
                         LIMIT 1"
                    );

                    mysqli_stmt_bind_param(
                        $check_stmt,
                        "iii",
                        $option_id,
                        $question_id,
                        $test_id
                    );

                    mysqli_stmt_execute($check_stmt);

                    $check_result = mysqli_stmt_get_result($check_stmt);

                    $selected_option = mysqli_fetch_assoc($check_result);

                    mysqli_stmt_close($check_stmt);

                    if (!$selected_option) {
                        $valid_answers = false;
                        break;
                    }

                    $result_key = $selected_option["result_key"];

                    if (!isset($totals[$result_key])) {
                        $totals[$result_key] = [
                            "score" => 0,
                            "count" => 0,
                            "first" => count($totals)
                        ];
                    }

                    $totals[$result_key]["score"] +=
                        (int) $selected_option["score"];

                    $totals[$result_key]["count"]++;

                    $validated_answers[] = [
                        "question_id" => $question_id,
                        "option_id" => $option_id
                    ];
                }

                if (!$valid_answers) {

                    $errorMessage = "Pilihan jawaban tidak valid. Silakan coba lagi.";

                } else {

                    // Tentukan hasil dengan skor tertinggi
                    $result_keys = array_keys($totals);

                    usort($result_keys, function ($a, $b) use ($totals) {

                        if ($totals[$a]["score"] !== $totals[$b]["score"]) {
                            return $totals[$b]["score"] <=> $totals[$a]["score"];
                        }

                        if ($totals[$a]["count"] !== $totals[$b]["count"]) {
                            return $totals[$b]["count"] <=> $totals[$a]["count"];
                        }

                        return $totals[$a]["first"] <=> $totals[$b]["first"];
                    });

                    $result_key = $result_keys[0];

                    // Cari profil hasil di database
                    $profile_stmt = mysqli_prepare(
                        $conn,
                        "SELECT result_title, result_description
                         FROM knowme_result_profiles
                         WHERE test_id = ? AND result_key = ?
                         LIMIT 1"
                    );

                    mysqli_stmt_bind_param(
                        $profile_stmt,
                        "is",
                        $test_id,
                        $result_key
                    );

                    mysqli_stmt_execute($profile_stmt);

                    $profile_result = mysqli_stmt_get_result($profile_stmt);

                    $result_profile = mysqli_fetch_assoc($profile_result);

                    mysqli_stmt_close($profile_stmt);

                    // Judul hasil sementara jika profil hasil belum diisi
                    $result_titles = [
                        "introvert" => "Introvert",
                        "extrovert" => "Extrovert",
                        "ambivert" => "Ambivert",
                        "analytical" => "Analytical",
                        "adaptable" => "Adaptable",

                        "creative" => "Creative",
                        "social" => "Social",
                        "practical" => "Practical",
                        "explorative" => "Explorative",

                        "communication" => "Communication",
                        "leadership" => "Leadership",
                        "problem-solving" => "Problem Solver",

                        "words" => "Words of Affirmation",
                        "quality-time" => "Quality Time",
                        "gifts" => "Receiving Gifts",
                        "acts-of-service" => "Acts of Service",
                        "physical-touch" => "Physical Touch"
                    ];

                    $result_title = $result_profile["result_title"]
                        ?? ($result_titles[$result_key] ?? ucfirst($result_key));

                    $result_description = $result_profile["result_description"]
                        ?? "Hasil ini merupakan gambaran awal berdasarkan pilihan jawabanmu. Gunakan sebagai bahan refleksi untuk lebih mengenali diri sendiri.";

                    // Simpan hasil dan seluruh jawaban
                    try {

                        mysqli_begin_transaction($conn);

                        $insert_result = mysqli_prepare(
                            $conn,
                            "INSERT INTO knowme_results
                            (user_id, test_id, result_key, result_title, result_description, completed_at)
                            VALUES (?, ?, ?, ?, ?, NOW())"
                        );

                        mysqli_stmt_bind_param(
                            $insert_result,
                            "iisss",
                            $user_id,
                            $test_id,
                            $result_key,
                            $result_title,
                            $result_description
                        );

                        mysqli_stmt_execute($insert_result);

                        $result_id = mysqli_insert_id($conn);

                        mysqli_stmt_close($insert_result);

                        // Simpan jawaban satu per satu
                        $insert_answer = mysqli_prepare(
                            $conn,
                            "INSERT INTO knowme_answers
                            (result_id, question_id, option_id)
                            VALUES (?, ?, ?)"
                        );

                        foreach ($validated_answers as $answer) {

                            mysqli_stmt_bind_param(
                                $insert_answer,
                                "iii",
                                $result_id,
                                $answer["question_id"],
                                $answer["option_id"]
                            );

                            mysqli_stmt_execute($insert_answer);
                        }

                        mysqli_stmt_close($insert_answer);

                        mysqli_commit($conn);

                        // Kembali ke halaman Know Me
                        header("Location: know_me.php?success=1");
                        exit;

                    } catch (Throwable $e) {

                        mysqli_rollback($conn);

                        $errorMessage = "Terjadi kesalahan saat menyimpan jawaban. Silakan coba kembali.";
                    }
                }
            }
        }
    }
}

// Judul halaman
$page_title = htmlspecialchars($test["title"], ENT_QUOTES, "UTF-8");
$page_description = htmlspecialchars($test["description"], ENT_QUOTES, "UTF-8");
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $page_title ?> | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fff9fc;
            color: #49404a;
            min-height: 100vh;
        }

        .test-container {
            width: 100%;
            max-width: 850px;
            margin: auto;
            padding: 35px 20px 60px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            text-decoration: none;
            color: #9c7892;
            font-size: 14px;
            margin-bottom: 30px;
            transition: .2s;
        }

        .back-link:hover {
            color: #d47ca9;
        }

        .test-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .test-icon {
            width: 65px;
            height: 65px;
            border-radius: 22px;
            background: #fce5f0;
            color: #d47ca9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            margin: 0 auto 18px;
        }

        .test-label {
            color: #d47ca9;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .test-header h1 {
            font-size: 30px;
            font-weight: 750;
            margin: 10px 0;
            color: #514451;
        }

        .test-header p {
            color: #9b909b;
            font-size: 14px;
            line-height: 1.7;
        }

        .test-card {
            background: white;
            padding: 35px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(190, 130, 160, .09);
            border: 1px solid #f7eaf1;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 13px;
            color: #9b8797;
        }

        .progress-info strong {
            color: #d47ca9;
        }

        .progress-track {
            height: 8px;
            background: #f8edf3;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 35px;
        }

        .progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #e9a9c9, #c58bbd);
            border-radius: 20px;
            transition: width .3s ease;
        }

        .question-step {
            display: none;
        }

        .question-step.active {
            display: block;
            animation: fadeIn .25s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .question-number {
            display: inline-block;
            background: #fff0f6;
            color: #d47ca9;
            padding: 7px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .question-title {
            font-size: 21px;
            font-weight: 700;
            line-height: 1.5;
            color: #514451;
            margin-bottom: 25px;
        }

        .option-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option-item {
            position: relative;
        }

        .option-item input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .option-label {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border: 1px solid #f0e5ec;
            border-radius: 15px;
            cursor: pointer;
            transition: .2s;
            background: white;
            color: #706572;
            font-size: 14px;
            line-height: 1.6;
        }

        .option-label:hover {
            border-color: #e6a8c8;
            background: #fff9fc;
        }

        .option-circle {
            width: 21px;
            height: 21px;
            flex-shrink: 0;
            border: 2px solid #e6cfdd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .option-item input:checked + .option-label {
            background: #fff2f8;
            border-color: #d98bb5;
            color: #a65e88;
        }

        .option-item input:checked + .option-label .option-circle {
            border-color: #d47ca9;
        }

        .option-item input:checked + .option-label .option-circle::after {
            content: "";
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #d47ca9;
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #f5edf2;
        }

        .nav-button {
            border: none;
            padding: 13px 23px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 650;
            cursor: pointer;
            transition: .2s;
        }

        .previous-button {
            background: #f7f0f4;
            color: #8c7888;
        }

        .previous-button:hover {
            background: #f0e4ec;
        }

        .next-button,
        .submit-button {
            background: #d98bb5;
            color: white;
        }

        .next-button:hover,
        .submit-button:hover {
            background: #c777a2;
        }

        .nav-button:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .test-note {
            text-align: center;
            color: #a89ba6;
            font-size: 12px;
            margin-top: 25px;
            line-height: 1.7;
        }

        .error-message {
            background: #fff0f0;
            border: 1px solid #f2cccc;
            color: #b64f5a;
            padding: 14px 17px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        @media (max-width: 600px) {

            .test-container {
                padding: 22px 14px 40px;
            }

            .test-header h1 {
                font-size: 25px;
            }

            .test-card {
                padding: 23px 17px;
                border-radius: 19px;
            }

            .question-title {
                font-size: 18px;
            }

            .option-label {
                padding: 14px;
                font-size: 13px;
            }

            .nav-button {
                padding: 12px 16px;
                font-size: 13px;
            }

        }

    </style>

</head>

<body>

<div class="test-container">

    <!-- KEMBALI -->
    <a href="know_me.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Kembali ke Know Me
    </a>


    <!-- HEADER -->
    <header class="test-header">

        <div class="test-icon">
            <i class="fa-solid fa-brain"></i>
        </div>

        <span class="test-label">KNOW YOURSELF BETTER</span>

        <h1><?= $page_title ?></h1>

        <p><?= $page_description ?></p>

    </header>


    <!-- FORM TES -->
    <div class="test-card">

        <?php if ($errorMessage): ?>

            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
            </div>

        <?php endif; ?>


        <form method="POST" id="testForm">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, "UTF-8") ?>"
            >

            <input
                type="hidden"
                name="answers"
                id="answersInput"
            >


            <!-- PROGRESS -->
            <div class="progress-info">

                <span id="questionCounter">
                    Pertanyaan 1 dari <?= $total_questions ?>
                </span>

                <strong id="progressPercentage">0%</strong>

            </div>

            <div class="progress-track">

                <div class="progress-fill" id="progressFill"></div>

            </div>


            <!-- DAFTAR PERTANYAAN -->
            <?php foreach ($questions as $index => $question): ?>

                <div
                    class="question-step <?= $index === 0 ? "active" : "" ?>"
                    data-index="<?= $index ?>"
                    data-question-id="<?= (int) $question["id"] ?>"
                >

                    <span class="question-number">
                        QUESTION <?= $index + 1 ?>
                    </span>

                    <h2 class="question-title">
                        <?= htmlspecialchars($question["question_text"], ENT_QUOTES, "UTF-8") ?>
                    </h2>


                    <div class="option-list">

                        <?php foreach ($question["options"] as $option): ?>

                            <div class="option-item">

                                <input
                                    type="radio"
                                    id="option_<?= (int) $option["id"] ?>"
                                    name="question_<?= (int) $question["id"] ?>"
                                    value="<?= (int) $option["id"] ?>"
                                >

                                <label
                                    for="option_<?= (int) $option["id"] ?>"
                                    class="option-label"
                                >

                                    <span class="option-circle"></span>

                                    <span>
                                        <?= htmlspecialchars($option["option_text"], ENT_QUOTES, "UTF-8") ?>
                                    </span>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endforeach; ?>


            <!-- NAVIGASI -->
            <div class="navigation-buttons">

                <button
                    type="button"
                    class="nav-button previous-button"
                    id="previousButton"
                    disabled
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Sebelumnya
                </button>


                <button
                    type="button"
                    class="nav-button next-button"
                    id="nextButton"
                >
                    Selanjutnya
                    <i class="fa-solid fa-arrow-right"></i>
                </button>


                <button
                    type="submit"
                    class="nav-button submit-button"
                    id="submitButton"
                    style="display: none;"
                >
                    Selesaikan Tes
                    <i class="fa-solid fa-check"></i>
                </button>

            </div>

        </form>

    </div>


    <!-- CATATAN -->
    <p class="test-note">
        Jawablah setiap pertanyaan sesuai dengan dirimu sendiri.
        Tidak ada jawaban benar atau salah.
        <br>
        Your journey to knowing yourself starts here. ♡
    </p>

</div>


<script>

const questionSteps = document.querySelectorAll(".question-step");

const previousButton = document.getElementById("previousButton");
const nextButton = document.getElementById("nextButton");
const submitButton = document.getElementById("submitButton");

const questionCounter = document.getElementById("questionCounter");
const progressPercentage = document.getElementById("progressPercentage");
const progressFill = document.getElementById("progressFill");

const answersInput = document.getElementById("answersInput");
const testForm = document.getElementById("testForm");

const totalQuestions = questionSteps.length;

let currentQuestion = 0;


// Menampilkan pertanyaan
function showQuestion(index) {

    questionSteps.forEach((step, i) => {
        step.classList.toggle("active", i === index);
    });

    currentQuestion = index;

    // Update progress
    const percentage = Math.round(
        ((index + 1) / totalQuestions) * 100
    );

    questionCounter.textContent =
        "Pertanyaan " + (index + 1) + " dari " + totalQuestions;

    progressPercentage.textContent = percentage + "%";

    progressFill.style.width = percentage + "%";

    // Tombol sebelumnya
    previousButton.disabled = index === 0;

    // Tombol selanjutnya atau selesai
    if (index === totalQuestions - 1) {

        nextButton.style.display = "none";
        submitButton.style.display = "inline-block";

    } else {

        nextButton.style.display = "inline-block";
        submitButton.style.display = "none";

    }

}


// Memeriksa jawaban pada pertanyaan aktif
function hasAnswered(index) {

    const step = questionSteps[index];

    return step.querySelector("input[type='radio']:checked") !== null;

}


// Tombol selanjutnya
nextButton.addEventListener("click", function() {

    if (!hasAnswered(currentQuestion)) {

        alert("Silakan pilih jawaban terlebih dahulu ya!");

        return;
    }

    if (currentQuestion < totalQuestions - 1) {

        showQuestion(currentQuestion + 1);

    }

});


// Tombol sebelumnya
previousButton.addEventListener("click", function() {

    if (currentQuestion > 0) {

        showQuestion(currentQuestion - 1);

    }

});


// Simpan semua jawaban sebelum dikirim
testForm.addEventListener("submit", function(event) {

    event.preventDefault();

    const answers = {};

    for (let i = 0; i < totalQuestions; i++) {

        const step = questionSteps[i];

        const questionId = step.dataset.questionId;

        const selected = step.querySelector(
            "input[type='radio']:checked"
        );

        if (!selected) {

            alert(
                "Kamu belum menjawab pertanyaan nomor " + (i + 1) + "."
            );

            showQuestion(i);

            return;
        }

        answers[questionId] = selected.value;

    }

    // Masukkan jawaban ke input tersembunyi
    answersInput.value = JSON.stringify(answers);

    // Kirim form ke PHP
    testForm.submit();

});


// Tampilkan pertanyaan pertama
showQuestion(0);

</script>

</body>
</html>
