<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Profil Saya';
$active_page = 'profile';

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $db->prepare("SELECT * FROM admin WHERE id_admin = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirectWithMessage('/narasa-cake/dashboard/index.php', 'danger', 'Data pengguna tidak ditemukan');
}

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_profile') {
        $nama = trim($_POST['nama_lengkap']);
        $username = trim($_POST['username']);

        // Validasi
        if (empty($nama) || empty($username)) {
            redirectWithMessage('index.php', 'danger', 'Nama dan username wajib diisi');
        }

        // Cek username sudah dipakai user lain atau tidak
        $stmt = $db->prepare("SELECT id_admin FROM admin WHERE username = ? AND id_admin != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            redirectWithMessage('index.php', 'danger', 'Username sudah digunakan oleh pengguna lain');
        }

        try {
            $stmt = $db->prepare("UPDATE admin SET nama_lengkap = ?, username = ? WHERE id_admin = ?");
            $stmt->execute([$nama, $username, $user_id]);

            // Update session
            $_SESSION['nama_lengkap'] = $nama;
            $_SESSION['username'] = $username;

            redirectWithMessage('index.php', 'success', 'Profil berhasil diperbarui');
        } catch (PDOException $e) {
            redirectWithMessage('index.php', 'danger', 'Error: ' . $e->getMessage());
        }
    }

    if ($action == 'update_password') {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password = $_POST['konfirmasi_password'];

        // Validasi
        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
            redirectWithMessage('index.php', 'danger', 'Semua field password wajib diisi');
        }

        if ($password_baru !== $konfirmasi_password) {
            redirectWithMessage('index.php', 'danger', 'Konfirmasi password tidak cocok');
        }

        if (strlen($password_baru) < 6) {
            redirectWithMessage('index.php', 'danger', 'Password baru minimal 6 karakter');
        }

        // Verifikasi password lama
        if (!password_verify($password_lama, $user['password'])) {
            redirectWithMessage('index.php', 'danger', 'Password lama tidak sesuai');
        }

        try {
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
            $stmt->execute([$password_hash, $user_id]);

            redirectWithMessage('index.php', 'success', 'Password berhasil diperbarui');
        } catch (PDOException $e) {
            redirectWithMessage('index.php', 'danger', 'Error: ' . $e->getMessage());
        }
    }
}

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title><?= $page_title ?></title>
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <?php include '../../includes/sidebar.php'; ?>
    <?php include '../../includes/navbar.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">

            <?php displayMessage(); ?>

            <div class="row">
                <!-- Profil -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="ti ti-user me-2"></i>Informasi Profil</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="nama_lengkap"
                                        value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username"
                                        value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control"
                                        value="<?= htmlspecialchars(ucfirst($user['level'])) ?>" disabled>
                                    <small class="text-muted">Role tidak dapat diubah</small>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Ubah Password -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="ti ti-lock me-2"></i>Ubah Password</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_password">

                                <div class="form-group mb-3">
                                    <label class="form-label">Password Lama</label>
                                    <input type="password" class="form-control" name="password_lama" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" name="password_baru" required>
                                    <small class="text-muted">Minimal 6 karakter</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" name="konfirmasi_password" required>
                                </div>

                                <button type="submit" class="btn btn-warning">
                                    <i class="ti ti-key me-1"></i> Ubah Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>