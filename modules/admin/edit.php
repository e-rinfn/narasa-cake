<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Hanya superadmin yang bisa mengakses
if ($_SESSION['level'] != 'superadmin') {
    redirectWithMessage('../../index.php', 'danger', 'Anda tidak memiliki akses ke halaman ini');
}

$page_title = 'Edit Admin';
$active_page = 'admin';

// Ambil data admin yang akan diedit
$id_admin = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM admin WHERE id_admin = ?");
$stmt->execute([$id_admin]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    redirectWithMessage('../index.php', 'danger', 'Admin tidak ditemukan');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $no_telepon = $_POST['no_telepon'];
    $level = $_POST['level'];

    try {
        // Validasi username unik (kecuali untuk admin ini)
        $stmt = $db->prepare("SELECT id_admin FROM admin WHERE username = ? AND id_admin != ?");
        $stmt->execute([$username, $id_admin]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Username sudah digunakan');
        }

        // Validasi email unik (kecuali untuk admin ini)
        $stmt = $db->prepare("SELECT id_admin FROM admin WHERE email = ? AND id_admin != ?");
        $stmt->execute([$email, $id_admin]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Email sudah digunakan');
        }

        // Update data admin
        $sql = "UPDATE admin SET 
                username = ?, nama_lengkap = ?, email = ?, no_telepon = ?, level = ?";

        $params = [$username, $nama_lengkap, $email, $no_telepon, $level];

        // Jika password diubah
        if (!empty($_POST['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id_admin = ?";
        $params[] = $id_admin;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        redirectWithMessage('../index.php', 'success', 'Data admin berhasil diperbarui');
    } catch (Exception $e) {
        redirectWithMessage("edit.php?id=$id_admin", 'danger', 'Error: ' . $e->getMessage());
    }
}

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<!-- [Head] start -->

<!-- [Body] Start -->

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

            <div class="row">

                <!-- [ Main Content ] start -->

                <div class="card-header">
                    <h3>Edit Admin</h3>
                </div>
                <div class="card-body mt-3">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username *</label>
                                    <input type="text" name="username" class="form-control"
                                        value="<?= htmlspecialchars($admin['username']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Password (Kosongkan jika tidak diubah)</label>
                                    <input type="password" name="password" class="form-control" minlength="6">
                                </div>

                                <div class="form-group">
                                    <label>Nama Lengkap *</label>
                                    <input type="text" name="nama_lengkap" class="form-control"
                                        value="<?= htmlspecialchars($admin['nama_lengkap']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?= htmlspecialchars($admin['email']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>No. Telepon</label>
                                    <input type="text" name="no_telepon" class="form-control"
                                        value="<?= htmlspecialchars($admin['no_telepon']) ?>">
                                </div>

                                <div class="form-group">
                                    <label>Level *</label>
                                    <select name="level" class="form-control" required>
                                        <option value="superadmin" <?= $admin['level'] == 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                                        <option value="admin" <?= $admin['level'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="kasir" <?= $admin['level'] == 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>