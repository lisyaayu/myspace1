
<?php
session_start();
require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $name === "" ||
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $error = "Semua kolom wajib diisi.";

    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,50}$/', $username)) {
        $error = "Username harus 4-50 karakter dan hanya boleh menggunakan huruf, angka, atau underscore.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";

    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";

    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";

    } else {
        // Periksa email dan username
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users
             WHERE email = ? OR username = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($check, "ss", $email, $username);
        mysqli_stmt_execute($check);

        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email atau username sudah digunakan.";

        } else {
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            mysqli_begin_transaction($conn);

            try {
                // Simpan akun pengguna
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO users
                    (name, username, email, password, role)
                    VALUES (?, ?, ?, ?, 'user')"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssss",
                    $name,
                    $username,
                    $email,
                    $hashed_password
                );

                mysqli_stmt_execute($stmt);

                $user_id = mysqli_insert_id($conn);

                // Buat profil pengguna
                $profile = mysqli_prepare(
                    $conn,
                    "INSERT INTO profiles (user_id)
                     VALUES (?)"
                );

                mysqli_stmt_bind_param($profile, "i", $user_id);
                mysqli_stmt_execute($profile);

                // Buat preferensi pengguna
                $preferences = mysqli_prepare(
                    $conn,
                    "INSERT INTO user_preferences (user_id)
                     VALUES (?)"
                );

                mysqli_stmt_bind_param(
                    $preferences,
                    "i",
                    $user_id
                );

                mysqli_stmt_execute($preferences);

                mysqli_commit($conn);

                header("Location: login.php?registered=1");
                exit;

            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = "Registrasi gagal. Silakan coba kembali.";
            }
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>

<div class="auth-container">
    <div class="auth-card">

        <h2>Join MySpace</h2>
        <p class="text-muted">
            Create your account and start your journey.
        </p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="Masukkan nama lengkap"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="Contoh: lisyaayu"
                    minlength="4"
                    maxlength="50"
                    pattern="[a-zA-Z0-9_]+"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                >

                <small class="text-muted">
                    Minimal 4 karakter, gunakan huruf, angka, atau underscore.
                </small>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Masukkan email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        name="password"
                        id="registerPassword"
                        class="form-control"
                        placeholder="Minimal 8 karakter"
                        minlength="8"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('registerPassword', this)"
                        aria-label="Tampilkan password"
                    >
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        name="confirm_password"
                        id="confirmPassword"
                        class="form-control"
                        placeholder="Masukkan ulang password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('confirmPassword', this)"
                        aria-label="Tampilkan password"
                    >
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Create Account
            </button>

        </form>

        <p class="mt-4 text-center">
            Already have an account?
            <a href="login.php">Login</a>
        </p>

    </div>
</div>

<script src="assets/js/script.js"></script>

</body>
</html>
