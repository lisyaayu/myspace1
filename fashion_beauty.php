<?php
session_start();
require_once "../config/database.php";

// Pemeriksaan login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Pemeriksaan admin
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit("Akses ditolak.");
}

// Buat token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Ambil kategori
$categories = [];

$query = mysqli_query(
    $conn,
    "SELECT * FROM fashion_beauty_categories
     ORDER BY sort_order ASC, id ASC"
);

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $categories[] = $row;
    }
}

// Ambil tautan
$links = [];

$queryLinks = mysqli_query(
    $conn,
    "SELECT * FROM fashion_beauty_links
     ORDER BY category_id ASC"
);

if ($queryLinks) {
    while ($row = mysqli_fetch_assoc($queryLinks)) {
        $links[$row['category_id']][$row['link_type']] = $row;
    }
}

// Fungsi keamanan output
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Pesan notifikasi
$messages = [
    'success'  => 'Data tautan berhasil disimpan.',
    'deleted'  => 'Tautan berhasil dihapus.',
    'notfound' => 'Tautan tidak ditemukan atau sudah dihapus.',
    'invalid'  => 'Permintaan tidak valid.',
    'error'    => 'Terjadi kesalahan. Silakan periksa kembali data.'
];

$status = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fashion & Beauty | Admin MySpace</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #fff8fb;
            color: #493b42;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background: white;
            border-right: 1px solid #f2dce6;
            padding: 30px 20px;
        }

        .brand {
            color: #d47a9b;
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .menu-link {
            display: block;
            padding: 13px 15px;
            margin-bottom: 10px;
            color: #715c67;
            text-decoration: none;
            border-radius: 12px;
        }

        .menu-link:hover,
        .menu-link.active {
            background: #fdeaf1;
            color: #c65f88;
        }

        .main-content {
            padding: 35px;
        }

        .category-card {
            background: white;
            border: 1px solid #f1dfe7;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 25px;
        }

        .link-box {
            background: #fffafd;
            border: 1px solid #f3e3eb;
            border-radius: 13px;
            padding: 18px;
            height: 100%;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px 12px;
            border-color: #ead9e1;
        }

        .form-control:focus {
            border-color: #d47a9b;
            box-shadow: 0 0 0 3px rgba(212,122,155,.12);
        }

        .btn-pink {
            background: #d47a9b;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
        }

        .btn-pink:hover {
            background: #bd6385;
            color: white;
        }

        .section-label {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #bd6385;
        }

        @media(max-width: 768px) {
            .sidebar {
                min-height: auto;
                border-right: none;
                border-bottom: 1px solid #f2dce6;
            }

            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="brand">MySpace.</div>

            <p class="text-muted small">ADMIN MENU</p>

            <a href="index.php" class="menu-link">
                Dashboard
            </a>

            <a href="fashion_beauty.php" class="menu-link active">
                Fashion & Beauty
            </a>

            <a href="../dashboard.php" class="menu-link">
                Halaman Pengguna
            </a>

            <hr>

            <a href="../logout.php" class="menu-link text-danger">
                Logout
            </a>
        </div>

        <!-- CONTENT -->
        <div class="col-md-9 col-lg-10 main-content">

            <div class="mb-4">
                <h3 class="fw-bold">Fashion & Beauty</h3>

                <p class="text-muted">
                    Atur website katalog, saluran WhatsApp,
                    dan kontak konsultasi untuk setiap kategori.
                </p>
            </div>

            <?php if (isset($messages[$status])): ?>
                <div class="alert <?= $status === 'success' || $status === 'deleted' ? 'alert-success' : 'alert-danger'; ?>">
                    <?= e($messages[$status]); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($categories)): ?>
                <div class="alert alert-warning">
                    Belum ada kategori Fashion & Beauty di database.
                </div>
            <?php endif; ?>

            <?php foreach ($categories as $category): ?>

                <?php
                    $categoryLinks = $links[$category['id']] ?? [];

                    $website = $categoryLinks['website'] ?? [];
                    $channel = $categoryLinks['whatsapp_channel'] ?? [];
                    $contact = $categoryLinks['whatsapp_contact'] ?? [];
                ?>

                <div class="category-card">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

                        <div>
                            <h5 class="fw-bold mb-1">
                                <?= e($category['name']); ?>
                            </h5>

                            <small class="text-muted">
                                <?= e($category['description'] ?? ''); ?>
                            </small>
                        </div>

                        <span class="badge <?= !empty($category['is_active']) ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                            <?= !empty($category['is_active']) ? 'Aktif' : 'Nonaktif'; ?>
                        </span>

                    </div>

                    <!-- FORM SIMPAN -->
                    <form action="save_fashion_beauty.php" method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrf_token); ?>"
                        >

                        <input
                            type="hidden"
                            name="category_id"
                            value="<?= (int)$category['id']; ?>"
                        >

                        <!-- WEBSITE -->
                        <div class="link-box mb-3">

                            <div class="section-label">
                                1. Website / Katalog
                            </div>

                            <label class="form-label">Nama tautan</label>

                            <input
                                type="text"
                                name="website_label"
                                class="form-control mb-3"
                                value="<?= e($website['link_label'] ?? 'Lihat Katalog'); ?>"
                                required
                            >

                            <label class="form-label">URL Website</label>

                            <input
                                type="url"
                                name="website_url"
                                class="form-control mb-3"
                                placeholder="https://contoh.com"
                                value="<?= e($website['url'] ?? ''); ?>"
                            >

                        </div>

                        <!-- CHANNEL -->
                        <div class="link-box mb-3">

                            <div class="section-label">
                                2. Saluran WhatsApp
                            </div>

                            <label class="form-label">Nama tautan</label>

                            <input
                                type="text"
                                name="channel_label"
                                class="form-control mb-3"
                                value="<?= e($channel['link_label'] ?? 'Gabung Saluran WhatsApp'); ?>"
                                required
                            >

                            <label class="form-label">Link Saluran WhatsApp</label>

                            <input
                                type="url"
                                name="channel_url"
                                class="form-control mb-3"
                                placeholder="https://whatsapp.com/channel/..."
                                value="<?= e($channel['url'] ?? ''); ?>"
                            >

                        </div>

                        <!-- CONTACT -->
                        <div class="link-box mb-3">

                            <div class="section-label">
                                3. Konsultasi WhatsApp
                            </div>

                            <label class="form-label">Nama tombol</label>

                            <input
                                type="text"
                                name="contact_label"
                                class="form-control mb-3"
                                value="<?= e($contact['link_label'] ?? 'Konsultasi Sekarang'); ?>"
                                required
                            >

                            <label class="form-label">Nomor WhatsApp</label>

                            <input
                                type="text"
                                name="whatsapp_number"
                                class="form-control mb-3"
                                placeholder="628123456789"
                                value="<?= e($contact['whatsapp_number'] ?? ''); ?>"
                            >

                            <label class="form-label">Pesan otomatis</label>

                            <textarea
                                name="whatsapp_message"
                                class="form-control mb-3"
                                rows="3"
                                placeholder="Tuliskan pesan otomatis..."
                            ><?= e($contact['whatsapp_message'] ?? ''); ?></textarea>

                            <small class="text-muted">
                                Gunakan format nomor internasional tanpa tanda +,
                                spasi, atau angka 0 di depan.
                            </small>

                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                            <button type="submit" class="btn btn-pink">
                                Simpan Perubahan
                            </button>

                            <a
                                href="../fashion_beauty.php"
                                class="text-muted small text-decoration-none"
                            >
                                Halaman Pengguna
                            </a>

                        </div>

                    </form>

                    <!-- TOMBOL HAPUS TAUTAN -->
                    <div class="mt-3 d-flex flex-wrap gap-2">

                        <?php
                        $deleteTypes = [
                            'website' => 'Website',
                            'whatsapp_channel' => 'Saluran WhatsApp',
                            'whatsapp_contact' => 'Kontak WhatsApp'
                        ];
                        ?>

                        <?php foreach ($deleteTypes as $type => $label): ?>

                            <?php if (isset($categoryLinks[$type])): ?>

                                <form
                                    action="delete_fashion_beauty.php"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus <?= e($label); ?>?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($csrf_token); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="link_id"
                                        value="<?= (int)$categoryLinks[$type]['id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        Hapus <?= e($label); ?>
                                    </button>

                                </form>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endforeach; ?>

            <a href="index.php" class="btn btn-light">
                Kembali ke Dashboard
            </a>

        </div>
    </div>
</div>

</body>
</html>