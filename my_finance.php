<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

if (empty($_SESSION["finance_csrf"])) {
    $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));
}

function rupiah($value) {
    return "Rp " . number_format((float)$value, 0, ",", ".");
}

$months = [
    "Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
    "Jul", "Agu", "Sep", "Okt", "Nov", "Des"
];

$current_year = (int) date("Y");
$selected_year = isset($_GET["year"]) ? (int) $_GET["year"] : $current_year;

if ($selected_year < 2000 || $selected_year > 2100) {
    $selected_year = $current_year;
}

$errors = [];
$success = "";

/* =========================
   PROSES TAMBAH / HAPUS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) || !hash_equals($_SESSION["finance_csrf"], $token)) {
        $errors[] = "Permintaan tidak valid. Silakan muat ulang halaman.";
    } else {
        $action = $_POST["action"] ?? "";

        if ($action === "add_transaction") {
            $type = $_POST["transaction_type"] ?? "";
            $category = trim($_POST["category"] ?? "");
            $amount_input = $_POST["amount"] ?? "";
            $description = trim($_POST["description"] ?? "");
            $date = $_POST["transaction_date"] ?? "";

            $allowed_types = ["income", "expense", "saving"];

            if (!in_array($type, $allowed_types, true)) {
                $errors[] = "Jenis transaksi tidak valid.";
            }

            if ($category === "" || mb_strlen($category) > 100) {
                $errors[] = "Kategori wajib diisi, maksimal 100 karakter.";
            }

            if (!is_numeric($amount_input) || (float)$amount_input <= 0) {
                $errors[] = "Nominal harus lebih dari 0.";
            }

            if (strlen($description) > 1000) {
                $errors[] = "Catatan maksimal 1000 karakter.";
            }

            $date_object = DateTime::createFromFormat("!Y-m-d", $date);

            if (!$date_object || $date_object->format("Y-m-d") !== $date) {
                $errors[] = "Tanggal transaksi tidak valid.";
            }

            if (empty($errors)) {
                $amount = (float)$amount_input;

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO finance_transactions
                    (user_id, transaction_type, category, amount, description, transaction_date)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "issdss",
                    $user_id,
                    $type,
                    $category,
                    $amount,
                    $description,
                    $date
                );

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);

                    $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));

                    header("Location: my_finance.php?year=" . (int)substr($date, 0, 4) . "&added=1");
                    exit;
                } else {
                    $errors[] = "Transaksi gagal disimpan. Silakan coba lagi.";
                }

                mysqli_stmt_close($stmt);
            }
        } elseif ($action === "delete_transaction") {
            $transaction_id = (int)($_POST["transaction_id"] ?? 0);

            if ($transaction_id <= 0) {
                $errors[] = "Transaksi tidak valid.";
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM finance_transactions
                     WHERE id = ? AND user_id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ii",
                    $transaction_id,
                    $user_id
                );

                if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                    mysqli_stmt_close($stmt);

                    $_SESSION["finance_csrf"] = bin2hex(random_bytes(32));

                    header("Location: my_finance.php?year=" . $selected_year . "&deleted=1");
                    exit;
                } else {
                    $errors[] = "Transaksi tidak ditemukan atau gagal dihapus.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}

if (isset($_GET["added"])) {
    $success = "Transaksi berhasil ditambahkan!";
} elseif (isset($_GET["deleted"])) {
    $success = "Transaksi berhasil dihapus!";
}

/* =========================
   RINGKASAN KEUANGAN
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense,
        COALESCE(SUM(CASE WHEN transaction_type = 'saving' THEN amount ELSE 0 END), 0) AS total_saving
     FROM finance_transactions
     WHERE user_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$total_income = (float)$summary["total_income"];
$total_expense = (float)$summary["total_expense"];
$total_saving = (float)$summary["total_saving"];

$current_balance = $total_income - $total_expense - $total_saving;


/* =========================
   DATA GRAFIK JANUARI-DESEMBER
========================= */

$chart_data = [
    "income" => array_fill(0, 12, 0),
    "expense" => array_fill(0, 12, 0),
    "saving" => array_fill(0, 12, 0)
];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        MONTH(transaction_date) AS month_number,
        transaction_type,
        SUM(amount) AS total
     FROM finance_transactions
     WHERE user_id = ?
       AND YEAR(transaction_date) = ?
     GROUP BY MONTH(transaction_date), transaction_type
     ORDER BY MONTH(transaction_date)"
);

mysqli_stmt_bind_param($stmt, "ii", $user_id, $selected_year);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $index = (int)$row["month_number"] - 1;
    $type = $row["transaction_type"];

    if (isset($chart_data[$type]) && $index >= 0 && $index < 12) {
        $chart_data[$type][$index] = (float)$row["total"];
    }
}

mysqli_stmt_close($stmt);

/* =========================
   RIWAYAT TRANSAKSI
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        transaction_type,
        category,
        amount,
        description,
        transaction_date
     FROM finance_transactions
     WHERE user_id = ?
     ORDER BY transaction_date DESC, id DESC
     LIMIT 100"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$transactions = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Finance | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #fff8fb;
            color: #493d46;
            font-family: 'DM Sans', sans-serif;
        }

        .page-wrap {
            max-width: 1200px;
            margin: auto;
            padding: 35px 22px 60px;
        }

        .back-link {
            color: #a65d83;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            color: #75445f;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            color: #75445f;
            font-size: 38px;
            margin-top: 22px;
        }

        .subtitle {
            color: #9b8793;
        }

        .finance-card,
        .section-card {
            background: #fff;
            border: 1px solid #f3e5ed;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 6px 22px rgba(119, 72, 97, .045);
            height: 100%;
        }

        .summary-label {
            color: #9b8793;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .summary-value {
            font-size: 25px;
            font-weight: 700;
            color: #65465a;
            overflow-wrap: anywhere;
        }

        .summary-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            margin-bottom: 17px;
            background: #fbeaf2;
            color: #bd7197;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            color: #75445f;
            font-size: 23px;
            margin-bottom: 5px;
        }

        .form-control,
        .form-select {
            border: 1px solid #eedce6;
            border-radius: 11px;
            padding: 11px 13px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #d99ab8;
            box-shadow: 0 0 0 .2rem rgba(217,154,184,.15);
        }

        .btn-pink {
            background: #bd7197;
            color: white;
            border: 0;
            border-radius: 11px;
            padding: 11px 20px;
            font-weight: 600;
        }

        .btn-pink:hover {
            background: #a85f84;
            color: white;
        }

        .chart-wrap {
            position: relative;
            height: 290px;
            margin-top: 20px;
        }

        .transaction-row {
            padding: 16px 0;
            border-bottom: 1px solid #f5eaf0;
        }

        .transaction-row:last-child {
            border-bottom: 0;
        }

        .transaction-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: #fbeaf2;
            color: #b96c91;
            flex-shrink: 0;
        }

        .text-income {
            color: #39977a;
        }

        .text-expense {
            color: #d47786;
        }

        .text-saving {
            color: #8b79bd;
        }

        .year-select {
            max-width: 130px;
        }

        .empty-state {
            text-align: center;
            padding: 45px 15px;
            color: #9b8793;
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 31px;
            }

            .summary-value {
                font-size: 21px;
            }

            .finance-card,
            .section-card {
                padding: 19px;
            }

            .chart-wrap {
                height: 240px;
            }
        }
    </style>
</head>

<body>

<div class="page-wrap">

    <a href="dashboard.php" class="back-link">
        <i class="fa-solid fa-arrow-left me-2"></i>
        Kembali ke Dashboard
    </a>

    <div class="mt-3 mb-4">
        <h1 class="page-title">My Finance ♡</h1>
        <p class="subtitle">
            Kelola uangmu, pahami kebiasaan finansialmu, dan capai tujuanmu.
        </p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- RINGKASAN -->

    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6 col-lg-3">
            <div class="finance-card">
                <div class="summary-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>

                <div class="summary-label">Saldo saat ini</div>

                <div class="summary-value">
                    <?= rupiah($current_balance) ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="finance-card">
                <div class="summary-icon">
                    <i class="fa-solid fa-arrow-down"></i>
                </div>

                <div class="summary-label">Total pemasukan</div>

                <div class="summary-value text-income">
                    <?= rupiah($total_income) ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="finance-card">
                <div class="summary-icon">
                    <i class="fa-solid fa-arrow-up"></i>
                </div>

                <div class="summary-label">Total pengeluaran</div>

                <div class="summary-value text-expense">
                    <?= rupiah($total_expense) ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="finance-card">
                <div class="summary-icon">
                    <i class="fa-solid fa-piggy-bank"></i>
                </div>

                <div class="summary-label">Total tabungan</div>

                <div class="summary-value text-saving">
                    <?= rupiah($total_saving) ?>
                </div>
            </div>
        </div>

    </div>

    <!-- FORM TRANSAKSI -->

    <div class="section-card mb-4">

        <h2 class="section-title">Tambah Transaksi</h2>

        <p class="subtitle mb-4">
            Catat pemasukan, pengeluaran, atau tabunganmu.
        </p>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["finance_csrf"]) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="add_transaction"
            >

            <div class="row g-3">

                <div class="col-12 col-md-4">
                    <label class="form-label">Jenis transaksi</label>

                    <select name="transaction_type" class="form-select" required>
                        <option value="">Pilih jenis</option>
                        <option value="income">Pemasukan</option>
                        <option value="expense">Pengeluaran</option>
                        <option value="saving">Tabungan</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Kategori</label>

                    <input
                        type="text"
                        name="category"
                        class="form-control"
                        placeholder="Contoh: Makanan, Gaji, Belanja"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Nominal (Rp)</label>

                    <input
                        type="number"
                        name="amount"
                        class="form-control"
                        min="0.01"
                        step="0.01"
                        placeholder="Contoh: 50000"
                        required
                    >
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Tanggal</label>

                    <input
                        type="date"
                        name="transaction_date"
                        class="form-control"
                        value="<?= date("Y-m-d") ?>"
                        required
                    >
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Catatan (opsional)</label>

                    <input
                        type="text"
                        name="description"
                        class="form-control"
                        maxlength="1000"
                        placeholder="Tambahkan keterangan transaksi"
                    >
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-pink">
                        <i class="fa-solid fa-plus me-2"></i>
                        Simpan Transaksi
                    </button>
                </div>

            </div>
        </form>

    </div>

    <!-- LAPORAN GRAFIK -->

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

        <div>
            <h2 class="section-title mb-1">Laporan Keuangan</h2>

            <p class="subtitle mb-0">
                Perkembangan keuangan Januari sampai Desember.
            </p>
        </div>

        <form method="GET">
            <select
                name="year"
                class="form-select year-select"
                onchange="this.form.submit()"
            >
                <?php
                for (
                    $year = $current_year - 5;
                    $year <= $current_year + 5;
                    $year++
                ):
                ?>
                    <option
                        value="<?= $year ?>"
                        <?= $selected_year === $year ? "selected" : "" ?>
                    >
                        <?= $year ?>
                    </option>
                <?php endfor; ?>
            </select>
        </form>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-12">
            <div class="section-card">
                <h3 class="section-title">Pengeluaran</h3>

                <p class="subtitle">
                    Total pengeluaran setiap bulan.
                </p>

                <div class="chart-wrap">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="section-card">
                <h3 class="section-title">Pemasukan</h3>

                <p class="subtitle">
                    Total pemasukan setiap bulan.
                </p>

                <div class="chart-wrap">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="section-card">
                <h3 class="section-title">Tabungan</h3>

                <p class="subtitle">
                    Total uang yang dialokasikan ke tabungan setiap bulan.
                </p>

                <div class="chart-wrap">
                    <canvas id="savingChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- RIWAYAT TRANSAKSI -->

    <div class="section-card">

        <h2 class="section-title">Riwayat Transaksi</h2>

        <p class="subtitle mb-3">
            100 transaksi terakhir.
        </p>

        <?php if (mysqli_num_rows($transactions) > 0): ?>

            <?php while ($transaction = mysqli_fetch_assoc($transactions)): ?>

                <?php
                $type = $transaction["transaction_type"];

                if ($type === "income") {
                    $icon = "fa-arrow-down";
                    $type_label = "Pemasukan";
                    $text_class = "text-income";
                    $sign = "+";
                } elseif ($type === "expense") {
                    $icon = "fa-arrow-up";
                    $type_label = "Pengeluaran";
                    $text_class = "text-expense";
                    $sign = "-";
                } else {
                    $icon = "fa-piggy-bank";
                    $type_label = "Tabungan";
                    $text_class = "text-saving";
                    $sign = "-";
                }
                ?>

                <div class="transaction-row d-flex align-items-center gap-3">

                    <div class="transaction-icon">
                        <i class="fa-solid <?= $icon ?>"></i>
                    </div>

                    <div class="flex-grow-1">

                        <div class="fw-bold">
                            <?= htmlspecialchars($transaction["category"]) ?>
                        </div>

                        <small class="text-muted">
                            <?= $type_label ?>
                            ·
                            <?= date("d M Y", strtotime($transaction["transaction_date"])) ?>
                        </small>

                        <?php if (!empty($transaction["description"])): ?>
                            <div class="small text-muted">
                                <?= htmlspecialchars($transaction["description"]) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="text-end">

                        <div class="fw-bold <?= $text_class ?>">
                            <?= $sign . rupiah($transaction["amount"]) ?>
                        </div>

                        <form
                            method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus transaksi ini?');"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION["finance_csrf"]) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="delete_transaction"
                            >

                            <input
                                type="hidden"
                                name="transaction_id"
                                value="<?= (int)$transaction["id"] ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-sm text-danger p-0 mt-1"
                            >
                                <i class="fa-regular fa-trash-can"></i>
                                Hapus
                            </button>
                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-state">
                <i class="fa-solid fa-receipt fa-2x mb-3"></i>

                <p class="mb-0">
                    Belum ada transaksi. Yuk, catat transaksi pertamamu!
                </p>
            </div>

        <?php endif; ?>

    </div>

</div>

<script>
const months = <?= json_encode($months) ?>;

const chartData = {
    expense: <?= json_encode(array_values($chart_data["expense"])) ?>,
    income: <?= json_encode(array_values($chart_data["income"])) ?>,
    saving: <?= json_encode(array_values($chart_data["saving"])) ?>
};

function createFinanceChart(elementId, values, lineColor, fillColor) {
    const canvas = document.getElementById(elementId);

    new Chart(canvas, {
        type: "line",

        data: {
            labels: months,

            datasets: [{
                data: values,
                borderColor: lineColor,
                backgroundColor: fillColor,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: lineColor,
                borderWidth: 3
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return "Rp " +
                                new Intl.NumberFormat("id-ID").format(context.raw);
                        }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,

                    ticks: {
                        callback: function(value) {
                            return "Rp " +
                                new Intl.NumberFormat("id-ID", {
                                    notation: "compact"
                                }).format(value);
                        }
                    },

                    grid: {
                        color: "#f5edf1"
                    }
                },

                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

createFinanceChart(
    "expenseChart",
    chartData.expense,
    "#d98291",
    "rgba(217,130,145,.12)"
);

createFinanceChart(
    "incomeChart",
    chartData.income,
    "#68ad98",
    "rgba(104,173,152,.12)"
);

createFinanceChart(
    "savingChart",
    chartData.saving,
    "#9a87c7",
    "rgba(154,135,199,.12)"
);
</script>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>