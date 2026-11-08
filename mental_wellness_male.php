
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];

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
<title>Mental Wellness | MySpace</title>
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Montserrat:wght@500;600;700;800&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f3f6fa;
    color: #26364a;
    font-family: 'DM Sans', sans-serif;
}

.topbar {
    height: 70px;
    padding: 0 7%;
    background: #fff;
    border-bottom: 1px solid #e4eaf1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.back-link {
    text-decoration: none;
    color: #78899d;
    font-size: 13px;
    font-weight: 700;
}

.brand {
    color: #294b70;
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    font-size: 21px;
}

.brand i {
    color: #5d91c5;
}

.container {
    max-width: 1100px;
    width: 90%;
    margin: auto;
    padding: 38px 0 60px;
}

.hero {
    padding: 45px;
    border-radius: 24px;
    background: linear-gradient(120deg, #203d5d, #416b91);
    color: white;
}

.eyebrow {
    color: #a9c9e8;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.hero h1 {
    font-family: 'Montserrat', sans-serif;
    font-size: clamp(30px, 5vw, 43px);
    margin: 14px 0;
}

.hero p {
    max-width: 600px;
    color: #d2e1ef;
    font-size: 13px;
    line-height: 1.9;
}

.section-title {
    margin: 30px 0 15px;
}

.section-title span {
    color: #6e91b3;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.section-title h2 {
    color: #294663;
    font-family: 'Montserrat', sans-serif;
    font-size: 22px;
    margin-top: 6px;
}

.mood-card,
.reflection-card,
.breathing-card,
.history-card {
    background: white;
    border: 1px solid #e5ebf2;
    border-radius: 19px;
    padding: 25px;
}

.mood-card p,
.reflection-card p {
    color: #8999aa;
    font-size: 12px;
    line-height: 1.7;
}

.mood-options {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-top: 22px;
}

.mood-option {
    border: 1px solid #e6edf4;
    border-radius: 14px;
    padding: 17px 8px;
    background: #f9fbfd;
    color: #526a82;
    cursor: pointer;
    transition: .2s;
    font-family: inherit;
}

.mood-option i {
    display: block;
    font-size: 27px;
    margin-bottom: 10px;
    color: #6b91b5;
}

.mood-option span {
    font-size: 11px;
    font-weight: 700;
}

.mood-option:hover,
.mood-option.active {
    background: #eaf2fa;
    border-color: #6492bd;
    color: #294b70;
}

.save-mood {
    margin-top: 20px;
    padding: 12px 23px;
    background: #315b83;
    color: white;
    border: 0;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
}

.save-mood:hover {
    background: #244767;
}

.message {
    margin-top: 12px;
    color: #397c60;
    font-size: 12px;
}

.main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: stretch;
}

.reflection-card textarea {
    width: 100%;
    min-height: 130px;
    resize: vertical;
    margin-top: 16px;
    padding: 13px;
    border: 1px solid #e0e8f0;
    border-radius: 11px;
    background: #f9fbfd;
    color: #344b63;
    font-family: inherit;
    font-size: 12px;
    outline: none;
}

.reflection-card textarea:focus {
    border-color: #729bc0;
}

.primary-button {
    margin-top: 12px;
    padding: 12px 18px;
    border: 0;
    border-radius: 9px;
    background: #315b83;
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.breathing-card {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.breathing-circle {
    width: 135px;
    height: 135px;
    border-radius: 50%;
    background: #e4eff8;
    border: 8px solid #c6dcef;
    display: grid;
    place-items: center;
    margin: 18px auto;
    transition: transform 4s ease-in-out, background 4s;
}

.breathing-circle span {
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    color: #315b83;
    font-size: 13px;
}

.breathing-circle.inhale {
    transform: scale(1.2);
    background: #d1e7f7;
}

.breathing-circle.exhale {
    transform: scale(.85);
}

.breathing-card p {
    color: #8999aa;
    font-size: 12px;
    line-height: 1.7;
}

.breathing-button {
    margin-top: 15px;
    padding: 11px 20px;
    border: 0;
    border-radius: 9px;
    background: #315b83;
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.tip-card {
    background: white;
    border: 1px solid #e5ebf2;
    border-radius: 16px;
    padding: 22px;
}

.tip-icon {
    width: 43px;
    height: 43px;
    border-radius: 13px;
    display: grid;
    place-items: center;
    background: #eaf2fa;
    color: #5283b1;
    font-size: 19px;
    margin-bottom: 15px;
}

.tip-card h3 {
    font-size: 14px;
    color: #34516e;
    margin-bottom: 8px;
}

.tip-card p {
    color: #8999aa;
    font-size: 11px;
    line-height: 1.8;
}

.history-card {
    margin-top: 20px;
}

.history-item {
    padding: 15px 0;
    border-bottom: 1px solid #edf1f5;
}

.history-item:last-child {
    border-bottom: 0;
}

.history-item strong {
    color: #34516e;
    font-size: 13px;
}

.history-item small {
    display: block;
    color: #9aa8b7;
    margin-top: 4px;
    font-size: 10px;
}

.history-item p {
    color: #74879b;
    font-size: 12px;
    margin-top: 7px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.empty {
    text-align: center;
    color: #9aa8b7;
    font-size: 12px;
    padding: 25px 0;
}

.delete-history {
    margin-top: 12px;
    border: 0;
    background: #fff0f0;
    color: #b75d68;
    padding: 9px 13px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 11px;
    font-weight: 700;
}

.footer-note {
    margin-top: 22px;
    padding: 20px;
    border-radius: 15px;
    background: #e8f0f8;
    color: #64809b;
    font-size: 12px;
    line-height: 1.8;
}

@media(max-width: 750px) {
    .main-grid {
        grid-template-columns: 1fr;
    }

    .tips-grid {
        grid-template-columns: 1fr;
    }

    .mood-options {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media(max-width: 500px) {
    .container {
        width: 92%;
        padding-top: 25px;
    }

    .topbar {
        padding: 0 4%;
    }

    .hero {
        padding: 28px;
    }

    .mood-options {
        grid-template-columns: repeat(2, 1fr);
    }

    .mood-card,
    .reflection-card,
    .breathing-card,
    .history-card {
        padding: 20px;
    }
}
</style>
</head>

<body>

<header class="topbar">
    <a href="health_male.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Health Center
    </a>

    <span class="brand">
        MySpace <i class="fa-solid fa-brain"></i>
    </span>
</header>

<main class="container">

    <section class="hero">
        <span class="eyebrow">MEN'S MENTAL WELLNESS</span>
        <h1>Take a Moment for Yourself.</h1>
        <p>
            Hi, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>!
            Your mental well-being matters.
            Take time to understand your feelings, slow down,
            and give yourself space to recharge.
        </p>
    </section>

    <div class="section-title">
        <span>UNDERSTAND YOURSELF</span>
        <h2>How Are You Feeling Today?</h2>
    </div>

    <section class="mood-card">
        <p>
            Check in with yourself. There is no right or wrong answer.
            Every feeling deserves attention.
        </p>

        <div class="mood-options">
            <button type="button" class="mood-option" data-mood="Great">
                <i class="fa-solid fa-face-laugh-beam"></i>
                <span>Great</span>
            </button>

            <button type="button" class="mood-option" data-mood="Good">
                <i class="fa-solid fa-face-smile"></i>
                <span>Good</span>
            </button>

            <button type="button" class="mood-option" data-mood="Okay">
                <i class="fa-solid fa-face-meh"></i>
                <span>Okay</span>
            </button>

            <button type="button" class="mood-option" data-mood="Stressed">
                <i class="fa-solid fa-face-frown"></i>
                <span>Stressed</span>
            </button>

            <button type="button" class="mood-option" data-mood="Low">
                <i class="fa-solid fa-face-sad-tear"></i>
                <span>Low</span>
            </button>
        </div>

        <button type="button" class="save-mood" id="saveMood">
            <i class="fa-solid fa-check"></i> Save Mood
        </button>

        <p class="message" id="moodMessage"></p>
    </section>

    <div class="section-title">
        <span>BUILD HEALTHY ROUTINES</span>
        <h2>Take Care of Your Mind</h2>
    </div>

    <section class="main-grid">

        <div class="reflection-card">
            <h3><i class="fa-solid fa-pen-to-square"></i> Daily Reflection</h3>
            <p>
                Write down what's on your mind.
                You can use this space to reflect on your day.
            </p>

            <textarea id="reflection"
                placeholder="What has been on your mind today?"></textarea>

            <button type="button" class="primary-button" id="saveReflection">
                <i class="fa-solid fa-floppy-disk"></i> Save Reflection
            </button>

            <p class="message" id="reflectionMessage"></p>
        </div>

        <div class="breathing-card">
            <h3><i class="fa-solid fa-wind"></i> Breathing Exercise</h3>
            <p>Take a few moments to slow down and focus on your breathing.</p>

            <div class="breathing-circle" id="breathingCircle">
                <span id="breathingText">Ready?</span>
            </div>

            <p id="breathingInstruction">
                Follow the circle as it expands and contracts.
            </p>

            <button type="button" class="breathing-button" id="breathingButton">
                Start Exercise
            </button>
        </div>

    </section>

    <div class="section-title">
        <span>SMALL STEPS MATTER</span>
        <h2>Daily Mental Wellness Tips</h2>
    </div>

    <section class="tips-grid">

        <article class="tip-card">
            <div class="tip-icon">
                <i class="fa-solid fa-person-walking"></i>
            </div>
            <h3>Stay Active</h3>
            <p>
                Make time for movement. A walk, stretch,
                or light exercise can help you reset.
            </p>
        </article>

        <article class="tip-card">
            <div class="tip-icon">
                <i class="fa-solid fa-bed"></i>
            </div>
            <h3>Get Enough Rest</h3>
            <p>
                Create a relaxing bedtime routine and
                give your body time to recover.
            </p>
        </article>

        <article class="tip-card">
            <div class="tip-icon">
                <i class="fa-solid fa-comments"></i>
            </div>
            <h3>Talk It Out</h3>
            <p>
                Share what you are experiencing with
                someone you trust when you feel ready.
            </p>
        </article>

        <article class="tip-card">
            <div class="tip-icon">
                <i class="fa-solid fa-mobile-screen"></i>
            </div>
            <h3>Take a Digital Break</h3>
            <p>
                Step away from screens sometimes.
                Give yourself space to recharge.
            </p>
        </article>

        <article class="tip-card">
            <div class="tip-icon">
                <i class="fa-solid fa-bullseye"></i>
            </div>
            <h3>Set Realistic Goals</h3>
            <p>
                Break big tasks into smaller steps.
                Progress does not have to be perfect.
            </p>
        </article>

        <article class="tip-card">
            <div class="tip-icon">
                <i class="fa-solid fa-heart"></i>
            </div>
            <h3>Be Kind to Yourself</h3>
            <p>
                You do not have to have everything figured out.
                Give yourself room to grow.
            </p>
        </article>

    </section>

    <div class="section-title">
        <span>YOUR PERSONAL JOURNEY</span>
        <h2>Mood & Reflection History</h2>
    </div>

    <section class="history-card">
        <div id="historyList">
            <div class="empty">
                <i class="fa-solid fa-book-open"></i>
                <p>Your saved mood and reflections will appear here.</p>
            </div>
        </div>

        <button type="button" class="delete-history" id="clearHistory">
            <i class="fa-solid fa-trash"></i> Clear History
        </button>
    </section>

    <div class="footer-note">
        <i class="fa-solid fa-circle-info"></i>
        Mental wellness is part of overall health.
        This page is for personal reflection and general well-being,
        not a substitute for professional mental health support.
        If you are struggling, consider reaching out to someone you trust
        or a qualified professional.
    </div>

</main>

<script>
const moodButtons = document.querySelectorAll('.mood-option');
const saveMoodButton = document.getElementById('saveMood');
const moodMessage = document.getElementById('moodMessage');
const reflection = document.getElementById('reflection');
const reflectionMessage = document.getElementById('reflectionMessage');
const historyList = document.getElementById('historyList');

const storageKey = 'myspace_male_mental_wellness';
let selectedMood = '';

function getHistory() {
    try {
        return JSON.parse(localStorage.getItem(storageKey)) || [];
    } catch (error) {
        return [];
    }
}

function saveHistory(history) {
    localStorage.setItem(storageKey, JSON.stringify(history));
}

function renderHistory() {
    const history = getHistory();

    if (history.length === 0) {
        historyList.innerHTML = `
            <div class="empty">
                <i class="fa-solid fa-book-open"></i>
                <p>Your saved mood and reflections will appear here.</p>
            </div>
        `;
        return;
    }

    historyList.innerHTML = '';

    history.forEach(item => {
        const article = document.createElement('article');
        article.className = 'history-item';

        const title = document.createElement('strong');
        title.textContent = item.mood
            ? 'Mood: ' + item.mood
            : 'Daily Reflection';

        const date = document.createElement('small');
        date.textContent = item.date;

        article.appendChild(title);
        article.appendChild(date);

        if (item.reflection) {
            const content = document.createElement('p');
            content.textContent = item.reflection;
            article.appendChild(content);
        }

        historyList.appendChild(article);
    });
}

moodButtons.forEach(button => {
    button.addEventListener('click', () => {
        moodButtons.forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        selectedMood = button.dataset.mood;
        moodMessage.textContent = '';
    });
});

saveMoodButton.addEventListener('click', () => {
    if (!selectedMood) {
        moodMessage.textContent = 'Please select your mood first.';
        return;
    }

    const history = getHistory();

    history.unshift({
        mood: selectedMood,
        reflection: '',
        date: new Date().toLocaleString()
    });

    saveHistory(history);
    moodMessage.textContent = 'Your mood has been saved!';
    selectedMood = '';

    moodButtons.forEach(button => button.classList.remove('active'));
    renderHistory();
});

document.getElementById('saveReflection').addEventListener('click', () => {
    const text = reflection.value.trim();

    if (!text) {
        reflectionMessage.textContent = 'Please write something first.';
        return;
    }

    const history = getHistory();

    history.unshift({
        mood: '',
        reflection: text,
        date: new Date().toLocaleString()
    });

    saveHistory(history);
    reflection.value = '';
    reflectionMessage.textContent = 'Your reflection has been saved!';
    renderHistory();
});

document.getElementById('clearHistory').addEventListener('click', () => {
    if (confirm('Are you sure you want to clear your history?')) {
        localStorage.removeItem(storageKey);
        renderHistory();
        moodMessage.textContent = '';
        reflectionMessage.textContent = '';
    }
});

// Breathing exercise: inhale 4 seconds, exhale 4 seconds.
let breathingInterval = null;
let breathingRunning = false;
let breathingPhase = 'inhale';
let breathingCount = 0;

const breathingCircle = document.getElementById('breathingCircle');
const breathingText = document.getElementById('breathingText');
const breathingInstruction = document.getElementById('breathingInstruction');
const breathingButton = document.getElementById('breathingButton');

function stopBreathing() {
    clearInterval(breathingInterval);
    breathingRunning = false;
    breathingButton.textContent = 'Start Exercise';
    breathingText.textContent = 'Ready?';
    breathingInstruction.textContent = 'Take a moment for yourself.';
    breathingCircle.className = 'breathing-circle';
}

breathingButton.addEventListener('click', () => {
    if (breathingRunning) {
        stopBreathing();
        return;
    }

    breathingRunning = true;
    breathingPhase = 'inhale';
    breathingCount = 0;

    breathingButton.textContent = 'Stop Exercise';

    function updateBreathing() {
        if (breathingPhase === 'inhale') {
            breathingCircle.className = 'breathing-circle inhale';
            breathingText.textContent = 'Breathe In';
            breathingInstruction.textContent = 'Slowly breathe in...';
        } else {
            breathingCircle.className = 'breathing-circle exhale';
            breathingText.textContent = 'Breathe Out';
            breathingInstruction.textContent = 'Slowly breathe out...';
        }

        breathingPhase = breathingPhase === 'inhale' ? 'exhale' : 'inhale';
        breathingCount++;

        if (breathingCount >= 10) {
            stopBreathing();
            breathingText.textContent = 'Well Done!';
            breathingInstruction.textContent = 'Take a moment before continuing your day.';
        }
    }

    updateBreathing();
    breathingInterval = setInterval(updateBreathing, 4000);
});

renderHistory();
</script>

</body>
</html>
