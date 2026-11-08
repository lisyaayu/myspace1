
<?php
session_start();
require_once "config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$error = "";
$success = "";

// Token keamanan
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Ambil data pengguna dan profil
function getProfileData($conn, $user_id) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            u.name,
            u.username,
            u.email,
            p.photo,
            p.birth_date,
            p.gender,
            p.bio,
            p.interests,
            up.favorite_color,
            up.fashion_style
        FROM users u
        LEFT JOIN profiles p ON p.user_id = u.id
        LEFT JOIN user_preferences up ON up.user_id = u.id
        WHERE u.id = ?
        LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $data;
}

$user = getProfileData($conn, $user_id);

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Proses penyimpanan profil
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";
    } else {

        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $birth_date = $_POST["birth_date"] ?? "";
        $gender = $_POST["gender"] ?? "";
        $bio = trim($_POST["bio"] ?? "");
        $interests = trim($_POST["interests"] ?? "");
        $favorite_color = trim($_POST["favorite_color"] ?? "");
        $fashion_style = trim($_POST["fashion_style"] ?? "");

        // Validasi data
        if ($name === "" || $email === "") {
            $error = "Nama dan email wajib diisi.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid.";

        } elseif (strlen($name) > 100) {
            $error = "Nama maksimal 100 karakter.";

        } elseif (strlen($bio) > 1000) {
            $error = "Bio maksimal 1000 karakter.";

        } elseif (strlen($interests) > 2000) {
            $error = "Minat dan hobi maksimal 2000 karakter.";

        } elseif (
            $birth_date !== "" &&
            (
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date) ||
                !checkdate(
                    (int) substr($birth_date, 5, 2),
                    (int) substr($birth_date, 8, 2),
                    (int) substr($birth_date, 0, 4)
                ) ||
                $birth_date > date("Y-m-d")
            )
        ) {
            $error = "Tanggal lahir tidak valid.";

        } elseif (
            !in_array($gender, ["", "Perempuan", "Laki-laki", "Lainnya"], true)
        ) {
            $error = "Pilihan jenis kelamin tidak valid.";

        } else {

            // Periksa email agar tidak digunakan akun lain
            $check = mysqli_prepare(
                $conn,
                "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1"
            );

            mysqli_stmt_bind_param($check, "si", $email, $user_id);
            mysqli_stmt_execute($check);

            $check_result = mysqli_stmt_get_result($check);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "Email sudah digunakan akun lain.";
            }

            mysqli_stmt_close($check);

            // Foto profil saat ini
            $photo = $user["photo"] ?? null;
            $new_photo_path = null;

            // Proses upload foto jika ada
            if (
                $error === "" &&
                isset($_FILES["photo"]) &&
                $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {
                $file = $_FILES["photo"];

                if ($file["error"] !== UPLOAD_ERR_OK) {
                    $error = "Upload foto gagal. Silakan coba kembali.";

                } elseif ($file["size"] > 2 * 1024 * 1024) {
                    $error = "Ukuran foto maksimal 2 MB.";

                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file["tmp_name"]);

                    $allowed_types = [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp"
                    ];

                    if (!isset($allowed_types[$mime])) {
                        $error = "Foto harus berformat JPG, PNG, atau WEBP.";

                    } else {
                        $upload_dir = __DIR__ . "/assets/images/profiles/";

                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $file_name = bin2hex(random_bytes(16))
                            . "." . $allowed_types[$mime];

                        $destination = $upload_dir . $file_name;

                        if (move_uploaded_file($file["tmp_name"], $destination)) {
                            $new_photo_path = "assets/images/profiles/" . $file_name;
                            $photo = $new_photo_path;
                        } else {
                            $error = "Foto gagal disimpan ke folder.";
                        }
                    }
                }
            }

            // Simpan seluruh perubahan
            if ($error === "") {
                mysqli_begin_transaction($conn);

                try {
                    $update_user = mysqli_prepare(
                        $conn,
                        "UPDATE users SET name = ?, email = ? WHERE id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $update_user,
                        "ssi",
                        $name,
                        $email,
                        $user_id
                    );

                    mysqli_stmt_execute($update_user);
                    mysqli_stmt_close($update_user);

                    $update_profile = mysqli_prepare(
                        $conn,
                        "UPDATE profiles
                         SET photo = ?,
                             birth_date = NULLIF(?, ''),
                             gender = NULLIF(?, ''),
                             bio = ?,
                             interests = ?
                         WHERE user_id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $update_profile,
                        "sssssi",
                        $photo,
                        $birth_date,
                        $gender,
                        $bio,
                        $interests,
                        $user_id
                    );

                    mysqli_stmt_execute($update_profile);
                    mysqli_stmt_close($update_profile);

                    $update_preferences = mysqli_prepare(
                        $conn,
                        "UPDATE user_preferences
                         SET favorite_color = ?,
                             fashion_style = ?
                         WHERE user_id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $update_preferences,
                        "ssi",
                        $favorite_color,
                        $fashion_style,
                        $user_id
                    );

                    mysqli_stmt_execute($update_preferences);
                    mysqli_stmt_close($update_preferences);

                    mysqli_commit($conn);

                    // Hapus foto lama setelah penyimpanan berhasil
                    if (!empty($new_photo_path) && !empty($user["photo"])) {
                        $old_photo = $user["photo"];
                        $old_full_path = __DIR__ . "/" . $old_photo;
                        $profile_folder = realpath(
                            __DIR__ . "/assets/images/profiles"
                        );

                        if (
                            $profile_folder &&
                            is_file($old_full_path) &&
                            realpath($old_full_path) &&
                            strpos(
                                realpath($old_full_path),
                                $profile_folder . DIRECTORY_SEPARATOR
                            ) === 0
                        ) {
                            unlink($old_full_path);
                        }
                    }

                    $_SESSION["user_name"] = $name;
                    $_SESSION["user_email"] = $email;

                    $success = "Profil berhasil diperbarui!";
                    $user = getProfileData($conn, $user_id);

                } catch (Throwable $e) {
                    mysqli_rollback($conn);

                    // Hapus foto baru jika penyimpanan database gagal
                    if (!empty($new_photo_path)) {
                        $new_full_path = __DIR__ . "/" . $new_photo_path;

                        if (is_file($new_full_path)) {
                            unlink($new_full_path);
                        }
                    }

                    $error = "Profil gagal disimpan. Silakan coba kembali.";
                }
            }
        }
    }
}

function e($value) {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

$initial = strtoupper(substr($user["name"], 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | MySpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/profile.css">
</head>

<body>

<div class="profile-page">

    <header class="profile-topbar">
        <a href="dashboard.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="profile-brand">
            <i class="fa-solid fa-heart"></i>
            MySpace
        </div>
    </header>

    <main class="profile-container">

        <div class="page-heading">
            <span class="eyebrow">YOUR PERSONAL SPACE</span>
            <h1>My Profile</h1>
            <p>Kenali dan kelola informasi pribadimu di satu tempat.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert alert-success">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION["csrf_token"]) ?>"
            >

            <!-- PROFILE PHOTO -->
            <section class="profile-card photo-card">

                <div class="photo-section">

                    <div class="photo-preview">
                        <?php if (!empty($user["photo"])): ?>
                            <img
                                src="<?= e($user["photo"]) ?>"
                                alt="Foto profil"
                                id="photoPreview"
                            >
                        <?php else: ?>
                            <div class="photo-placeholder" id="photoPlaceholder">
                                <?= e($initial) ?>
                            </div>

                            <img
                                src=""
                                alt="Pratinjau foto profil"
                                id="photoPreview"
                                style="display:none;"
                            >
                        <?php endif; ?>
                    </div>

                    <div class="photo-information">
                        <h2><?= e($user["name"]) ?></h2>
                        <p>@<?= e($user["username"]) ?></p>

                        <label for="photo" class="upload-button">
                            <i class="fa-solid fa-camera"></i>
                            Change Photo
                        </label>

                        <input
                            type="file"
                            name="photo"
                            id="photo"
                            accept="image/jpeg,image/png,image/webp"
                            hidden
                        >

                        <small>JPG, PNG, WEBP. Maksimal 2 MB.</small>
                    </div>

                </div>

            </section>

            <!-- PERSONAL INFORMATION -->
            <section class="profile-card">

                <div class="card-heading">
                    <div class="heading-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div>
                        <h2>Personal Information</h2>
                        <p>Informasi dasar tentang dirimu.</p>
                    </div>
                </div>

                <div class="form-grid">

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input
                            type="text"
                            name="name"
                            value="<?= e($user["name"]) ?>"
                            maxlength="100"
                            placeholder="Masukkan nama lengkap"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input
                            type="text"
                            value="<?= e($user["username"]) ?>"
                            readonly
                        >
                        <small>Username tidak dapat diubah di halaman ini.</small>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input
                            type="email"
                            name="email"
                            value="<?= e($user["email"]) ?>"
                            placeholder="Masukkan email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input
                            type="date"
                            name="birth_date"
                            value="<?= e($user["birth_date"]) ?>"
                            max="<?= date("Y-m-d") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="gender">
                            <option value="">Pilih jenis kelamin</option>

                            <option value="Perempuan"
                                <?= ($user["gender"] ?? "") === "Perempuan" ? "selected" : "" ?>>
                                Perempuan
                            </option>

                            <option value="Laki-laki"
                                <?= ($user["gender"] ?? "") === "Laki-laki" ? "selected" : "" ?>>
                                Laki-laki
                            </option>

                            <option value="Lainnya"
                                <?= ($user["gender"] ?? "") === "Lainnya" ? "selected" : "" ?>>
                                Lainnya
                            </option>
                        </select>
                    </div>

                </div>

                <div class="form-group full-width">
                    <label>Bio</label>
                    <textarea
                        name="bio"
                        rows="4"
                        maxlength="1000"
                        placeholder="Ceritakan sedikit tentang dirimu..."
                    ><?= e($user["bio"]) ?></textarea>
                </div>

            </section>

            <!-- INTERESTS -->
            <section class="profile-card">

                <div class="card-heading">
                    <div class="heading-icon purple">
                        <i class="fa-solid fa-heart"></i>
                    </div>

                    <div>
                        <h2>My Interests</h2>
                        <p>Ceritakan minat dan hal yang kamu sukai.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Minat dan Hobi</label>

                    <textarea
                        name="interests"
                        rows="4"
                        maxlength="2000"
                        placeholder="Contoh: Fashion, memasak, musik, membaca, olahraga..."
                    ><?= e($user["interests"]) ?></textarea>

                    <small>Pisahkan setiap minat dengan tanda koma.</small>
                </div>

            </section>

            <!-- PREFERENCES -->
            <section class="profile-card">

                <div class="card-heading">
                    <div class="heading-icon peach">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>

                    <div>
                        <h2>Personal Preferences</h2>
                        <p>Sesuaikan preferensi dengan kepribadianmu.</p>
                    </div>
                </div>

                <div class="form-grid">

                    <div class="form-group">
                        <label>Warna Favorit</label>

                        <select name="favorite_color">
                            <option value="">Pilih warna favorit</option>

                            <?php
                            $colors = [
                                "Pink", "Putih", "Hitam", "Cream",
                                "Biru", "Ungu", "Hijau", "Merah",
                                "Cokelat", "Lainnya"
                            ];
                            ?>

                            <?php foreach ($colors as $color): ?>
                                <option
                                    value="<?= e($color) ?>"
                                    <?= ($user["favorite_color"] ?? "") === $color ? "selected" : "" ?>
                                >
                                    <?= e($color) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Gaya Fashion Favorit</label>

                        <select name="fashion_style">
                            <option value="">Pilih gaya fashion</option>

                            <?php
                            $styles = [
                                "Casual", "Korean Style", "Minimalist",
                                "Elegant", "Feminine", "Chic",
                                "Streetwear", "Sporty", "Lainnya"
                            ];
                            ?>

                            <?php foreach ($styles as $style): ?>
                                <option
                                    value="<?= e($style) ?>"
                                    <?= ($user["fashion_style"] ?? "") === $style ? "selected" : "" ?>
                                >
                                    <?= e($style) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

            </section>

            <div class="save-section">
                <a href="dashboard.php" class="cancel-button">
                    Cancel
                </a>

                <button type="submit" class="save-button">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>

        </form>

        <footer class="profile-footer">
            © <?= date("Y") ?> MySpace. Your Personal Life Companion.
        </footer>

    </main>

</div>

<script>
const photoInput = document.getElementById("photo");
const photoPreview = document.getElementById("photoPreview");
const photoPlaceholder = document.getElementById("photoPlaceholder");

photoInput.addEventListener("change", function () {
    const file = this.files[0];

    if (!file) return;

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!allowedTypes.includes(file.type)) {
        alert("Pilih foto JPG, PNG, atau WEBP.");
        this.value = "";
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert("Ukuran foto maksimal 2 MB.");
        this.value = "";
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        photoPreview.src = event.target.result;
        photoPreview.style.display = "block";

        if (photoPlaceholder) {
            photoPlaceholder.style.display = "none";
        }
    };

    reader.readAsDataURL(file);
});
</script>

</body>
</html>
