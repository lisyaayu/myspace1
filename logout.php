<?php
session_start();

// Logout hanya boleh dilakukan melalui POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

// Periksa token keamanan.
$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

// Hapus seluruh data sesi.
$_SESSION = [];

// Hapus cookie sesi jika digunakan.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan sesi.
session_destroy();

// Kembali ke halaman login.
header("Location: login.php");
exit;