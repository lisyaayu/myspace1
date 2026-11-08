
<?php
session_start();

require_once "../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Pastikan hanya admin yang bisa masuk
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit("Akses ditolak. Halaman ini hanya untuk Admin MySpace.");
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #fff8fb;
            font-family: Arial, sans-serif;
            color: #493b42;
        }

        .sidebar {
            min-height: 100vh;
            background: #fff;
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

        .menu-link.active,
        .menu-link:hover {
            background: #fdeaf1;
            color: #c65f88;
        }

        .main-content {
            padding: 35px;
        }

        .welcome-card {
            background: linear-gradient(120deg, #f8dce8, #fff0f5);
            border-radius: 22px;
            padding: 35px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border: 1px solid #f3e2e9;
            border-radius: 18px;
            padding: 25px;
            height: 100%;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #d47a9b;
        }

        .feature-card {
            background: white;
            border: 1px solid #f3e2e9;
            border-radius: 18px;
            padding: 25px;
            height: 100%;
        }

        .btn-pink {
            background: #d47a9b;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 11px 20px;
        }

        .btn-pink:hover {
            background: #bd6385;
            color: white;
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

            <a href="index.php" class="menu-link active">
                Dashboard
            </a>

            <a href="fashion_beauty.php" class="menu-link">
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

        <!-- MAIN CONTENT -->
        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Admin Dashboard</h4>
                    <p class="text-muted mb-0">
                        Panel pengelolaan MySpace
                    </p>
                </div>

                <span class="badge rounded-pill text-bg-light p-2">
                    Administrator
                </span>
            </div>

            <!-- WELCOME -->
            <div class="welcome-card">
                <h2 class="fw-bold">
                    Halo, <?= htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?>!
                </h2>

                <p class="mb-0">
                    Selamat datang di halaman Admin MySpace.
                    Kelola dan atur berbagai informasi platform melalui halaman ini.
                </p>
            </div>

            <!-- STATISTIK -->
            <div class="row g-3 mb-4">

                <div class="col-md-6 col-xl-4">
                    <div class="stat-card">
                        <p class="text-muted mb-2">Status Akun</p>
                        <div class="stat-number">ADMIN</div>
                        <small class="text-muted">
                            Akses pengelolaan platform
                        </small>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4">
                    <div class="stat-card">
                        <p class="text-muted mb-2">Kategori Fashion & Beauty</p>
                        <div class="stat-number">3</div>
                        <small class="text-muted">
                            Fashion Perempuan, Fashion Laki-laki, Beauty
                        </small>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4">
                    <div class="stat-card">
                        <p class="text-muted mb-2">Pengelolaan</p>
                        <div class="stat-number">Aktif</div>
                        <small class="text-muted">
                            Kelola tautan dan informasi
                        </small>
                    </div>
                </div>

            </div>

            <!-- FITUR -->
            <h5 class="fw-bold mb-3">Pengelolaan Fitur</h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <div class="feature-card">
                        <h5 class="fw-bold">Fashion & Beauty</h5>

                        <p class="text-muted">
                            Kelola website katalog, saluran WhatsApp,
                            dan kontak konsultasi untuk setiap kategori.
                        </p>

                        <a href="fashion_beauty.php" class="btn btn-pink">
                            Kelola Sekarang
                        </a>
                    </div>
                </div>

            </div>

            <footer class="text-center text-muted mt-5 small">
                © <?= date('Y'); ?> MySpace. Admin Panel.
            </footer>

        </div>
    </div>
</div>

</body>
</html>
