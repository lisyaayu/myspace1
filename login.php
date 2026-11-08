
<?php
session_start();
require_once "config/database.php";

// Fungsi untuk menentukan halaman berdasarkan role
function redirectByRole($role) {
    if ($role === "admin") {
        header("Location: admin/index.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

// Jika sudah login, arahkan sesuai role
if (isset($_SESSION["user_id"])) {
    redirectByRole($_SESSION["user_role"] ?? "user");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identity = trim($_POST["identity"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($identity === "" || $password === "") {
        $error = "Username/email dan password wajib diisi.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, username, email, password, role
             FROM users
             WHERE email = ? OR username = ?
             LIMIT 1"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $identity, $identity);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_username"] = $user["username"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                redirectByRole($user["role"]);
            } else {
                $error = "Username/email atau password salah.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = "Terjadi kesalahan pada sistem. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>

<div class="auth-container">
    <div class="auth-card">

        <h2>Welcome Back!</h2>
        <p class="text-muted">
            Login to continue your journey with MySpace.
        </p>

        <?php if (isset($_GET["registered"])): ?>
            <div class="alert alert-success">
                Registrasi berhasil! Silakan login.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["logout"]) && $_GET["logout"] === "success"): ?>
            <div class="alert alert-success">
                Kamu berhasil logout dari MySpace.
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Username / Email</label>

                <input
                    type="text"
                    name="identity"
                    class="form-control"
                    placeholder="Masukkan username atau email"
                    value="<?= htmlspecialchars($_POST['identity'] ?? '') ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        name="password"
                        id="loginPassword"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('loginPassword', this)"
                        aria-label="Tampilkan password"
                    >
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Login
            </button>

        </form>

        <p class="mt-4 text-center">
            Don't have an account?
            <a href="register.php">Create Account</a>
        </p>

    </div>
</div>

<script src="assets/js/script.js"></script>

</body>
</html>
