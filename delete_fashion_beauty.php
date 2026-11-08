<?php
session_start();

require_once "../config/database.php";

// =====================================
// 1. PEMERIKSAAN AKSES
// =====================================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit("Akses ditolak.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: fashion_beauty.php");
    exit;
}

// =====================================
// 2. VALIDASI CSRF
// =====================================

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrf_token) ||
    !hash_equals($_SESSION['csrf_token'], $csrf_token)
) {
    header("Location: fashion_beauty.php?status=invalid");
    exit;
}

// =====================================
// 3. VALIDASI LINK ID
// =====================================

$link_id = filter_input(
    INPUT_POST,
    'link_id',
    FILTER_VALIDATE_INT
);

if (!$link_id || $link_id < 1) {
    header("Location: fashion_beauty.php?status=invalid");
    exit;
}

// =====================================
// 4. HAPUS TAUTAN BERDASARKAN ID
// =====================================

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM fashion_beauty_links
     WHERE id = ?"
);

if (!$stmt) {
    header("Location: fashion_beauty.php?status=error");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $link_id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $status = "deleted";
    } else {
        $status = "notfound";
    }
} else {
    $status = "error";
}

mysqli_stmt_close($stmt);

header("Location: fashion_beauty.php?status=" . $status);
exit;