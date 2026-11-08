<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

if (empty($_SESSION["finance_csrf"])) {
    $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function rp($value) {
    return "Rp " . number_format((float)$value, 0, ",", ".");
}

$month = isset($_GET["month"]) ? (int)$_GET["month"] : (int)date("n");
$year = isset($_GET["year"]) ? (int)$_GET["year"] : (int)date("Y");

if ($month < 1 || $month > 12) $month = (int)date("n");
if ($year < 2000 || $year > 2100) $year = (int)date("Y");

$errors = [];
$success = "";

function redirectFinance($month, $year, $message) {
    header("Location: finance_tools.php?month=" . $month . "&year=" . $year . "&message=" . urlencode($message));
    exit;
}

/* =========================
   PROSES FORM
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) || !hash_equals($_SESSION["finance_csrf"], $token)) {
        $errors[] = "Permintaan tidak valid. Muat ulang halaman.";
    } else {
        $action = $_POST["action"] ?? "";

        // SIMPAN BUDGET
        if ($action === "save_budget") {
            $category = trim($_POST["category"] ?? "");
            $amount = $_POST["budget_amount"] ?? "";
            $budget_month = (int)($_POST["budget_month"] ?? $month);
            $budget_year = (int)($_POST["budget_year"] ?? $year);

            if ($category === "" || mb_strlen($category) > 100) {
                $errors[] = "Kategori wajib diisi, maksimal 100 karakter.";
            }

            if (!is_numeric($amount) || (float)$amount <= 0) {
                $errors[] = "Nominal budget harus lebih dari nol.";
            }

            if ($budget_month < 1 || $budget_month > 12 || $budget_year < 2000 || $budget_year > 2100) {
                $errors[] = "Periode budget tidak valid.";
            }

            if (!$errors) {
                $amount = (float)$amount;

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO finance_budgets
                    (user_id, category, budget_amount, budget_month, budget_year)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE budget_amount = VALUES(budget_amount)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "isdii",
                    $user_id,
                    $category,
                    $amount,
                    $budget_month,
                    $budget_year
                );

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));
                    redirectFinance($budget_month, $budget_year, "Budget berhasil disimpan.");
                }

                $errors[] = "Budget gagal disimpan.";
                mysqli_stmt_close($stmt);
            }
        }

        // HAPUS BUDGET
        elseif ($action === "delete_budget") {
            $id = (int)($_POST["budget_id"] ?? 0);

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM finance_budgets WHERE id = ? AND user_id = ?"
            );

            mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                mysqli_stmt_close($stmt);
                $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));
                redirectFinance($month, $year, "Budget berhasil dihapus.");
            }

            $errors[] = "Budget tidak ditemukan.";
            mysqli_stmt_close($stmt);
        }

        // TAMBAH UTANG / PIUTANG
        elseif ($action === "add_debt") {
            $type = $_POST["debt_type"] ?? "";
            $person = trim($_POST["person_name"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $amount = $_POST["total_amount"] ?? "";
            $date = $_POST["transaction_date"] ?? "";
            $due_date = $_POST["due_date"] ?? "";
            $notes = trim($_POST["notes"] ?? "");

            if (!in_array($type, ["payable", "receivable"], true)) {
                $errors[] = "Jenis catatan tidak valid.";
            }

            if ($person === "" || mb_strlen($person) > 150) {
                $errors[] = "Nama orang wajib diisi.";
            }

            if (!is_numeric($amount) || (float)$amount <= 0) {
                $errors[] = "Nominal harus lebih dari nol.";
            }

            $date_object = DateTime::createFromFormat("!Y-m-d", $date);
            if (!$date_object || $date_object->format("Y-m-d") !== $date) {
                $errors[] = "Tanggal transaksi tidak valid.";
            }

            if ($due_date !== "") {
                $due_object = DateTime::createFromFormat("!Y-m-d", $due_date);
                if (!$due_object || $due_object->format("Y-m-d") !== $due_date) {
                    $errors[] = "Tanggal jatuh tempo tidak valid.";
                }
            } else {
                $due_date = null;
            }

            if (!$errors) {
                $amount = (float)$amount;

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO finance_debts
                    (user_id, debt_type, person_name, description,
                     total_amount, remaining_amount, transaction_date,
                     due_date, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "isssddsss",
                    $user_id,
                    $type,
                    $person,
                    $description,
                    $amount,
                    $amount,
                    $date,
                    $due_date,
                    $notes
                );

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));
                    redirectFinance($month, $year, "Catatan utang/piutang berhasil ditambahkan.");
                }

                $errors[] = "Data gagal disimpan.";
                mysqli_stmt_close($stmt);
            }
        }

        // PEMBAYARAN CICILAN
        elseif ($action === "pay_debt") {
            $debt_id = (int)($_POST["debt_id"] ?? 0);
            $payment = $_POST["payment_amount"] ?? "";
            $payment_date = $_POST["payment_date"] ?? date("Y-m-d");
            $payment_notes = trim($_POST["payment_notes"] ?? "");

            if (!is_numeric($payment) || (float)$payment <= 0) {
                $errors[] = "Nominal pembayaran tidak valid.";
            }

            $date_object = DateTime::createFromFormat("!Y-m-d", $payment_date);
            if (!$date_object || $date_object->format("Y-m-d") !== $payment_date) {
                $errors[] = "Tanggal pembayaran tidak valid.";
            }

            if (!$errors) {
                $payment = (float)$payment;

                mysqli_begin_transaction($conn);

                try {
                    $stmt = mysqli_prepare(
                        $conn,
                        "SELECT remaining_amount, debt_type, status
                         FROM finance_debts
                         WHERE id = ? AND user_id = ?
                         FOR UPDATE"
                    );

                    mysqli_stmt_bind_param($stmt, "ii", $debt_id, $user_id);
                    mysqli_stmt_execute($stmt);
                    $debt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                    mysqli_stmt_close($stmt);

                    if (!$debt || $debt["status"] === "paid") {
                        throw new Exception("Data tidak ditemukan atau sudah lunas.");
                    }

                    $remaining = (float)$debt["remaining_amount"];

                    if ($payment > $remaining) {
                        throw new Exception("Pembayaran melebihi sisa tagihan.");
                    }

                    $new_remaining = $remaining - $payment;
                    $new_status = $new_remaining <= 0 ? "paid" : "partial";

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO finance_debt_payments
                         (debt_id, user_id, payment_amount, payment_date, notes)
                         VALUES (?, ?, ?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iidss",
                        $debt_id,
                        $user_id,
                        $payment,
                        $payment_date,
                        $payment_notes
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Riwayat pembayaran gagal disimpan.");
                    }

                    mysqli_stmt_close($stmt);

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE finance_debts
                         SET remaining_amount = ?, status = ?
                         WHERE id = ? AND user_id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "dsii",
                        $new_remaining,
                        $new_status,
                        $debt_id,
                        $user_id
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Status pembayaran gagal diperbarui.");
                    }

                    mysqli_stmt_close($stmt);

                    mysqli_commit($conn);

                    $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));
                    redirectFinance($month, $year, "Pembayaran berhasil dicatat.");

                } catch (Throwable $exception) {
                    mysqli_rollback($conn);
                    $errors[] = $exception->getMessage();
                }
            }
        }

        // HAPUS CATATAN UTANG / PIUTANG
        elseif ($action === "delete_debt") {
            $debt_id = (int)($_POST["debt_id"] ?? 0);

            mysqli_begin_transaction($conn);

            try {
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM finance_debt_payments
                     WHERE debt_id = ? AND user_id = ?"
                );

                mysqli_stmt_bind_param($stmt, "ii", $debt_id, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM finance_debts
                     WHERE id = ? AND user_id = ?"
                );

                mysqli_stmt_bind_param($stmt, "ii", $debt_id, $user_id);
                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) <= 0) {
                    throw new Exception("Data tidak ditemukan.");
                }

                mysqli_stmt_close($stmt);
                mysqli_commit($conn);

                $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));
                redirectFinance($month, $year, "Catatan berhasil dihapus.");

            } catch (Throwable $exception) {
                mysqli_rollback($conn);
                $errors[] = $exception->getMessage();
            }
        }
    }
}

if (isset($_GET["message"])) {
    $success = $_GET["message"];
}

/* =========================
   DATA BUDGET
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        b.id,
        b.category,
        b.budget_amount,
        COALESCE(SUM(t.amount), 0) AS used_amount
     FROM finance_budgets b
     LEFT JOIN finance_transactions t
        ON t.user_id = b.user_id
        AND t.category = b.category
        AND t.transaction_type = 'expense'
        AND MONTH(t.transaction_date) = b.budget_month
        AND YEAR(t.transaction_date) = b.budget_year
     WHERE b.user_id = ?
       AND b.budget_month = ?
       AND b.budget_year = ?
     GROUP BY b.id, b.category, b.budget_amount
     ORDER BY b.category"
);

mysqli_stmt_bind_param($stmt, "iii", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$budgets = mysqli_stmt_get_result($stmt);

/* =========================
   DATA UTANG / PIUTANG
========================= */

$stmt_debts = mysqli_prepare(
    $conn,
    "SELECT *
     FROM finance_debts
     WHERE user_id = ?
     ORDER BY
        CASE status
            WHEN 'unpaid' THEN 1
            WHEN 'partial' THEN 2
            ELSE 3
        END,
        due_date ASC,
        id DESC"
);

mysqli_stmt_bind_param($stmt_debts, "i", $user_id);
mysqli_stmt_execute($stmt_debts);
$debts = mysqli_stmt_get_result($stmt_debts);

$stmt_summary = mysqli_prepare(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN debt_type = 'payable' THEN remaining_amount ELSE 0 END), 0) AS payable,
        COALESCE(SUM(CASE WHEN debt_type = 'receivable' THEN remaining_amount ELSE 0 END), 0) AS receivable
     FROM finance_debts
     WHERE user_id = ? AND status != 'paid'"
);

mysqli_stmt_bind_param($stmt_summary, "i", $user_id);
mysqli_stmt_execute($stmt_summary);
$debt_summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_summary));
mysqli_stmt_close($stmt_summary);

$month_names = [
    1 => "Januari", 2 => "Februari", 3 => "Maret",
    4 => "April", 5 => "Mei", 6 => "Juni",
    7 => "Juli", 8 => "Agustus", 9 => "September",
    10 => "Oktober", 11 => "November", 12 => "Desember"
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget & Utang Piutang | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: #fff8fb;
            color: #493d46;
            font-family: 'DM Sans', sans-serif;
        }

        .page-wrap {
            max-width: 1150px;
            margin: auto;
            padding: 35px 22px 60px;
        }

        .page-title, .section-title {
            font-family: 'Playfair Display', serif;
            color: #75445f;
        }

        .page-title { font-size: 36px; }

        .section-card {
            background: white;
            border: 1px solid #f3e5ed;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 6px 22px rgba(119,72,97,.045);
        }

        .subtitle { color: #9b8793; }

        .back-link {
            color: #a65d83;
            text-decoration: none;
            font-weight: 600;
        }

        .form-control, .form-select {
            border: 1px solid #eedce6;
            border-radius: 11px;
            padding: 11px 13px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #d99ab8;
            box-shadow: 0 0 0 .2rem rgba(217,154,184,.15);
        }

        .btn-pink {
            background: #bd7197;
            color: white;
            border: none;
            border-radius: 11px;
            padding: 10px 18px;
            font-weight: 600;
        }

        .btn-pink:hover {
            background: #a85f84;
            color: white;
        }

        .budget-item, .debt-item {
            border-bottom: 1px solid #f5eaf0;
            padding: 18px 0;
        }

        .budget-item:last-child, .debt-item:last-child {
            border-bottom: 0;
        }

        .progress {
            height: 10px;
            background: #f6edf1;
            border-radius: 20px;
        }

        .progress-bar {
            background: #bd7197;
            border-radius: 20px;
        }

        .summary-box {
            background: #fff;
            border: 1px solid #f3e5ed;
            border-radius: 17px;
            padding: 20px;
            height: 100%;
        }

        .summary-value {
            color: #75445f;
            font-size: 23px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        @media(max-width:576px) {
            .page-title { font-size: 30px; }
            .section-card { padding: 18px; }
        }
    </style>
</head>

<body>
<div class="page-wrap">

    <a href="my_finance.php" class="back-link">
        <i class="fa-solid fa-arrow-left me-2"></i>
        Kembali ke My Finance
    </a>

    <div class="mt-4 mb-4">
        <h1 class="page-title">Budget & Financial Obligations ♡</h1>
        <p class="subtitle">Atur anggaran dan pantau utang maupun piutangmu.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- RINGKASAN UTANG PIUTANG -->

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="summary-box">
                <div class="subtitle mb-2">Total utang yang perlu dibayar</div>
                <div class="summary-value text-danger">
                    <?= rp($debt_summary["payable"]) ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="summary-box">
                <div class="subtitle mb-2">Total piutang yang perlu diterima</div>
                <div class="summary-value text-success">
                    <?= rp($debt_summary["receivable"]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- BUDGET -->

    <section class="section-card">
        <h2 class="section-title">Monthly Budget</h2>
        <p class="subtitle">Atur batas pengeluaran untuk setiap kategori.</p>

        <form method="GET" class="row g-2 align-items-end mb-4">
            <div class="col-6 col-md-3">
                <label class="form-label">Bulan</label>
                <select name="month" class="form-select">
                    <?php foreach ($month_names as $number => $name): ?>
                        <option value="<?= $number ?>" <?= $month === $number ? "selected" : "" ?>>
                            <?= e($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label">Tahun</label>
                <input type="number" name="year" class="form-control" min="2000" max="2100" value="<?= $year ?>">
            </div>

            <div class="col-12 col-md-auto">
                <button class="btn btn-pink" type="submit">Lihat Periode</button>
            </div>
        </form>

        <h5 class="mb-3">
            Budget <?= e($month_names[$month]) ?> <?= $year ?>
        </h5>

        <form method="POST" class="row g-3 mb-4">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["finance_csrf"]) ?>">
            <input type="hidden" name="action" value="save_budget">
            <input type="hidden" name="budget_month" value="<?= $month ?>">
            <input type="hidden" name="budget_year" value="<?= $year ?>">

            <div class="col-12 col-md-5">
                <label class="form-label">Kategori pengeluaran</label>
                <input type="text" name="category" class="form-control" maxlength="100" placeholder="Contoh: Makanan" required>
            </div>

            <div class="col-12 col-md-5">
                <label class="form-label">Batas budget (Rp)</label>
                <input type="number" name="budget_amount" class="form-control" min="1" step="0.01" placeholder="Contoh: 1000000" required>
            </div>

            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-pink w-100">
                    <i class="fa-solid fa-plus me-1"></i> Simpan
                </button>
            </div>
        </form>

        <?php if (mysqli_num_rows($budgets) > 0): ?>
            <?php while ($budget = mysqli_fetch_assoc($budgets)): ?>
                <?php
                    $limit = (float)$budget["budget_amount"];
                    $used = (float)$budget["used_amount"];
                    $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;
                    $bar_width = min(100, $percentage);
                    $remaining = $limit - $used;
                    $bar_color = $percentage >= 100 ? "#d47786" : "#bd7197";
                ?>

                <div class="budget-item">
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <div>
                            <strong><?= e($budget["category"]) ?></strong>
                            <div class="small subtitle">
                                Terpakai <?= rp($used) ?> dari <?= rp($limit) ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <strong><?= number_format($percentage, 1, ",", ".") ?>%</strong>
                            <div class="small <?= $remaining < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= $remaining < 0 ? 'Melebihi ' . rp(abs($remaining)) : 'Sisa ' . rp($remaining) ?>
                            </div>
                        </div>
                    </div>

                    <div class="progress mb-3">
                        <div class="progress-bar"
                             style="width: <?= $bar_width ?>%; background: <?= $bar_color ?>;">
                        </div>
                    </div>

                    <form method="POST" onsubmit="return confirm('Hapus budget ini?');">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION["finance_csrf"]) ?>">
                        <input type="hidden" name="action" value="delete_budget">
                        <input type="hidden" name="budget_id" value="<?= (int)$budget["id"] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fa-regular fa-trash-can me-1"></i> Hapus budget
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="subtitle text-center py-4 mb-0">
                Belum ada budget untuk periode ini.
            </p>
        <?php endif; ?>
    </section>

    <!-- TAMBAH UTANG / PIUTANG -->

    <section class="section-card">
        <h2 class="section-title">Tambah Utang / Piutang</h2>
        <p class="subtitle">Catat uang yang kamu pinjam atau pinjamkan.</p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["finance_csrf"]) ?>">
            <input type="hidden" name="action" value="add_debt">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Jenis catatan</label>
                    <select name="debt_type" class="form-select" required>
                        <option value="">Pilih jenis</option>
                        <option value="payable">Utang — aku harus membayar</option>
                        <option value="receivable">Piutang — orang lain harus membayar</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nama orang</label>
                    <input type="text" name="person_name" class="form-control" maxlength="150" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nominal (Rp)</label>
                    <input type="number" name="total_amount" class="form-control" min="0.01" step="0.01" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="description" class="form-control" maxlength="255" placeholder="Contoh: Pinjaman pribadi">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tanggal transaksi</label>
                    <input type="date" name="transaction_date" class="form-control" value="<?= date("Y-m-d") ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tanggal jatuh tempo (opsional)</label>
                    <input type="date" name="due_date" class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label">Catatan tambahan</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-pink">
                        <i class="fa-solid fa-plus me-2"></i>Simpan Catatan
                    </button>
                </div>
            </div>
        </form>
    </section>

    <!-- DAFTAR UTANG / PIUTANG -->

    <section class="section-card">
        <h2 class="section-title">Daftar Utang & Piutang</h2>
        <p class="subtitle">Pantau sisa pembayaran dan catat cicilan.</p>

        <?php if (mysqli_num_rows($debts) > 0): ?>

            <?php while ($debt = mysqli_fetch_assoc($debts)): ?>
                <?php
                    $is_payable = $debt["debt_type"] === "payable";
                    $type_label = $is_payable ? "Utang" : "Piutang";
                    $status_label = [
                        "unpaid" => "Belum dibayar",
                        "partial" => "Dibayar sebagian",
                        "paid" => "Lunas"
                    ][$debt["status"]];
                ?>

                <div class="debt-item">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <div class="fw-bold">
                                <?= e($debt["person_name"]) ?>
                                <span class="badge <?= $is_payable ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $type_label ?>
                                </span>
                            </div>

                            <div class="small subtitle">
                                <?= e($debt["description"]) ?>
                            </div>

                            <div class="small text-muted mt-1">
                                Tanggal: <?= date("d M Y", strtotime($debt["transaction_date"])) ?>
                                <?php if ($debt["due_date"]): ?>
                                    · Jatuh tempo: <?= date("d M Y", strtotime($debt["due_date"])) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-md-end">
                            <div class="fw-bold fs-5">
                                <?= rp($debt["remaining_amount"]) ?>
                            </div>

                            <div class="small text-muted">
                                dari <?= rp($debt["total_amount"]) ?>
                            </div>

                            <span class="badge bg-light text-dark mt-1">
                                <?= $status_label ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($debt["notes"])): ?>
                        <div class="small subtitle mt-2">
                            Catatan: <?= e($debt["notes"]) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($debt["status"] !== "paid"): ?>
                        <form method="POST" class="row g-2 mt-3">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["finance_csrf"]) ?>">
                            <input type="hidden" name="action" value="pay_debt">
                            <input type="hidden" name="debt_id" value="<?= (int)$debt["id"] ?>">

                            <div class="col-12 col-md-4">
                                <input
                                    type="number"
                                    name="payment_amount"
                                    class="form-control"
                                    min="0.01"
                                    max="<?= e($debt["remaining_amount"]) ?>"
                                    step="0.01"
                                    placeholder="Nominal pembayaran"
                                    required
                                >
                            </div>

                            <div class="col-12 col-md-3">
                                <input
                                    type="date"
                                    name="payment_date"
                                    class="form-control"
                                    value="<?= date("Y-m-d") ?>"
                                    required
                                >
                            </div>

                            <div class="col-12 col-md-3">
                                <input
                                    type="text"
                                    name="payment_notes"
                                    class="form-control"
                                    maxlength="255"
                                    placeholder="Catatan pembayaran"
                                >
                            </div>

                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-pink w-100">
                                    Bayar
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end mt-2">
                        <form method="POST" onsubmit="return confirm('Hapus catatan ini beserta riwayat pembayarannya?');">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["finance_csrf"]) ?>">
                            <input type="hidden" name="action" value="delete_debt">
                            <input type="hidden" name="debt_id" value="<?= (int)$debt["id"] ?>">

                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fa-regular fa-trash-can me-1"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="text-center subtitle py-4">
                Belum ada catatan utang atau piutang.
            </div>
        <?php endif; ?>
    </section>

</div>
</body>
</html>

<?php
mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt_debts);
?>