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
    exit("Akses ditolak. Khusus Admin MySpace.");
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
// 3. VALIDASI KATEGORI
// =====================================

$category_id = filter_input(
    INPUT_POST,
    'category_id',
    FILTER_VALIDATE_INT
);

if (!$category_id || $category_id < 1) {
    header("Location: fashion_beauty.php?status=error");
    exit;
}

$check = mysqli_prepare(
    $conn,
    "SELECT id FROM fashion_beauty_categories
     WHERE id = ? LIMIT 1"
);

if (!$check) {
    header("Location: fashion_beauty.php?status=error");
    exit;
}

mysqli_stmt_bind_param($check, "i", $category_id);
mysqli_stmt_execute($check);

$result = mysqli_stmt_get_result($check);
$category = mysqli_fetch_assoc($result);

mysqli_stmt_close($check);

if (!$category) {
    header("Location: fashion_beauty.php?status=error");
    exit;
}

// =====================================
// 4. AMBIL DATA FORM
// =====================================

$website_label = trim($_POST['website_label'] ?? '');
$website_url = trim($_POST['website_url'] ?? '');

$channel_label = trim($_POST['channel_label'] ?? '');
$channel_url = trim($_POST['channel_url'] ?? '');

$contact_label = trim($_POST['contact_label'] ?? '');
$whatsapp_number = trim($_POST['whatsapp_number'] ?? '');
$whatsapp_message = trim($_POST['whatsapp_message'] ?? '');

// =====================================
// 5. VALIDASI URL
// =====================================

function validUrl($url) {
    if ($url === '') {
        return true;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $scheme = strtolower(
        parse_url($url, PHP_URL_SCHEME) ?? ''
    );

    return in_array($scheme, ['http', 'https'], true);
}

if (
    $website_label === '' ||
    $channel_label === '' ||
    $contact_label === ''
) {
    header("Location: fashion_beauty.php?status=error");
    exit;
}

if (
    !validUrl($website_url) ||
    !validUrl($channel_url)
) {
    header("Location: fashion_beauty.php?status=error");
    exit;
}

// =====================================
// 6. VALIDASI NOMOR WHATSAPP
// =====================================

$whatsapp_number = preg_replace(
    '/[\s\-\(\)\+]/',
    '',
    $whatsapp_number
);

if (
    $whatsapp_number !== '' &&
    !preg_match('/^[0-9]{8,15}$/', $whatsapp_number)
) {
    header("Location: fashion_beauty.php?status=error");
    exit;
}

// =====================================
// 7. FUNGSI SIMPAN / UPDATE LINK
// =====================================

function saveLink(
    $conn,
    $category_id,
    $type,
    $label,
    $url,
    $number = null,
    $message = null
) {
    // Jika URL kosong, hapus tautan yang lama
    if ($url === '') {
        $delete = mysqli_prepare(
            $conn,
            "DELETE FROM fashion_beauty_links
             WHERE category_id = ? AND link_type = ?"
        );

        if (!$delete) {
            throw new Exception("Gagal menyiapkan penghapusan tautan.");
        }

        mysqli_stmt_bind_param(
            $delete,
            "is",
            $category_id,
            $type
        );

        if (!mysqli_stmt_execute($delete)) {
            mysqli_stmt_close($delete);
            throw new Exception("Gagal menghapus tautan lama.");
        }

        mysqli_stmt_close($delete);
        return;
    }

    // Periksa apakah tautan sudah ada
    $check = mysqli_prepare(
        $conn,
        "SELECT id FROM fashion_beauty_links
         WHERE category_id = ? AND link_type = ?
         LIMIT 1"
    );

    if (!$check) {
        throw new Exception("Gagal memeriksa tautan.");
    }

    mysqli_stmt_bind_param(
        $check,
        "is",
        $category_id,
        $type
    );

    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);
    $existing = mysqli_fetch_assoc($result);

    mysqli_stmt_close($check);

    if ($existing) {
        // UPDATE tautan yang sudah ada
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE fashion_beauty_links
             SET link_label = ?,
                 url = ?,
                 whatsapp_number = ?,
                 whatsapp_message = ?,
                 is_active = 1
             WHERE id = ?"
        );

        if (!$stmt) {
            throw new Exception("Gagal menyiapkan update tautan.");
        }

        $link_id = (int)$existing['id'];

        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $label,
            $url,
            $number,
            $message,
            $link_id
        );

    } else {
        // INSERT tautan baru
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO fashion_beauty_links
             (
                category_id,
                link_type,
                link_label,
                url,
                whatsapp_number,
                whatsapp_message,
                is_active
             )
             VALUES (?, ?, ?, ?, ?, ?, 1)"
        );

        if (!$stmt) {
            throw new Exception("Gagal menyiapkan penyimpanan tautan.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isssss",
            $category_id,
            $type,
            $label,
            $url,
            $number,
            $message
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception("Gagal menyimpan tautan.");
    }

    mysqli_stmt_close($stmt);
}

// =====================================
// 8. BENTUK URL KONTAK WHATSAPP
// =====================================

$contact_url = '';

if ($whatsapp_number !== '') {
    $contact_url = 'https://wa.me/' .
        $whatsapp_number .
        '?text=' .
        rawurlencode($whatsapp_message);
}

// =====================================
// 9. SIMPAN SEMUA DATA
// =====================================

try {
    mysqli_begin_transaction($conn);

    // Website katalog
    saveLink(
        $conn,
        $category_id,
        'website',
        $website_label,
        $website_url
    );

    // Saluran WhatsApp
    saveLink(
        $conn,
        $category_id,
        'whatsapp_channel',
        $channel_label,
        $channel_url
    );

    // Kontak konsultasi WhatsApp
    saveLink(
        $conn,
        $category_id,
        'whatsapp_contact',
        $contact_label,
        $contact_url,
        $whatsapp_number,
        $whatsapp_message
    );

    mysqli_commit($conn);

    header("Location: fashion_beauty.php?status=success");
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);

    error_log($e->getMessage());

    header("Location: fashion_beauty.php?status=error");
    exit;
}