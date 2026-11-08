
<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Hapus favorit
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_id"])) {
    $id = (int) $_POST["delete_id"];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM favorites WHERE id = ? AND user_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: my_favorites.php?deleted=1");
    exit;
}

// Ambil daftar favorit
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, item_type, item_title, item_link, created_at
     FROM favorites
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$favorites = [];

while ($row = mysqli_fetch_assoc($result)) {
    $favorites[] = $row;
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Favorites | MySpace</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #fff8fa;
    color: #493640;
    font-family: "DM Sans", sans-serif;
}

.container {
    max-width: 1100px;
    margin: auto;
    padding: 40px 25px;
}

.back {
    color: #a17d8d;
    text-decoration: none;
}

.header {
    margin: 35px 0;
}

.header h1 {
    font-family: "Playfair Display", serif;
    font-size: 34px;
}

.header p {
    color: #a18e98;
}

.favorite-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.favorite-card {
    background: white;
    border: 1px solid #f2e5eb;
    border-radius: 18px;
    padding: 23px;
    box-shadow: 0 8px 25px rgba(80, 45, 60, .04);
}

.favorite-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff0f5;
    color: #cf789a;
    border-radius: 14px;
    font-size: 20px;
}

.favorite-card h3 {
    font-family: "Playfair Display", serif;
    margin: 18px 0 8px;
}

.favorite-type {
    font-size: 12px;
    color: #c47796;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.favorite-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
}

.open-link {
    color: #c46e91;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
}

.delete-button {
    border: 0;
    background: #fff0f2;
    color: #c96a7d;
    border-radius: 9px;
    padding: 9px 12px;
    cursor: pointer;
}

.empty {
    text-align: center;
    background: white;
    border-radius: 18px;
    padding: 65px 20px;
    color: #a18e98;
}

.empty i {
    font-size: 45px;
    color: #e3a1b9;
}

.alert {
    background: #eaf8ef;
    color: #39774c;
    padding: 13px;
    border-radius: 10px;
    margin-bottom: 20px;
}

@media(max-width:600px) {
    .container {
        padding: 25px 15px;
    }

    .header h1 {
        font-size: 28px;
    }
}
</style>
</head>

<body>

<div class="container">

    <a href="dashboard.php" class="back">
        <i class="fa-solid fa-arrow-left"></i>
        Kembali ke Dashboard
    </a>

    <div class="header">
        <h1><i class="fa-solid fa-heart" style="color:#d77d9f"></i> My Favorites</h1>
        <p>Kumpulan hal-hal yang kamu sukai dan ingin kamu simpan.</p>
    </div>

    <?php if (isset($_GET["deleted"])): ?>
        <div class="alert">Favorit berhasil dihapus.</div>
    <?php endif; ?>

    <?php if (count($favorites) > 0): ?>

        <div class="favorite-grid">

            <?php foreach ($favorites as $favorite): ?>

                <article class="favorite-card">

                    <div class="favorite-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>

                    <div class="favorite-type">
                        <?= e($favorite["item_type"]) ?>
                    </div>

                    <h3><?= e($favorite["item_title"]) ?></h3>

                    <small>
                        Disimpan:
                        <?= e(date("d M Y", strtotime($favorite["created_at"]))) ?>
                    </small>

                    <div class="favorite-actions">

                        <a class="open-link"
                           href="<?= e($favorite["item_link"]) ?>">
                            Buka halaman
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                        <form method="POST"
                              onsubmit="return confirm('Hapus dari favorit?')">

                            <input type="hidden"
                                   name="delete_id"
                                   value="<?= (int)$favorite["id"] ?>">

                            <button class="delete-button" type="submit">
                                <i class="fa-solid fa-trash"></i>
                            </button>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty">
            <i class="fa-regular fa-heart"></i>
            <h3>Belum Ada Favorit</h3>
            <p>Konten yang kamu simpan akan muncul di halaman ini.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
