<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Hanya superadmin yang bisa mengakses
if ($_SESSION['level'] != 'superadmin') {
    redirectWithMessage('../../index.php', 'danger', 'Anda tidak memiliki akses ke halaman ini');
}

$page_title = 'Tambah Admin';
$active_page = 'admin';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $no_telepon = $_POST['no_telepon'];
    $level = $_POST['level'];

    try {
        // Validasi username unik
        $stmt = $db->prepare("SELECT id_admin FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Username sudah digunakan');
        }

        // Validasi email unik
        $stmt = $db->prepare("SELECT id_admin FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Email sudah digunakan');
        }

        // Insert admin baru
        $stmt = $db->prepare("INSERT INTO admin 
                            (username, password, nama_lengkap, email, no_telepon, level) 
                            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $nama_lengkap, $email, $no_telepon, $level]);

        redirectWithMessage('../index.php', 'success', 'Admin berhasil ditambahkan');
    } catch (Exception $e) {
        redirectWithMessage('add.php', 'danger', 'Error: ' . $e->getMessage());
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
                    <h3>Tambah Admin Baru</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username *</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label>Password *</label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>

                                <div class="form-group">
                                    <label>Nama Lengkap *</label>
                                    <input type="text" name="nama_lengkap" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label>No. Telepon</label>
                                    <input type="text" name="no_telepon" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label>Level *</label>
                                    <select name="level" class="form-control" required>
                                        <option value="admin">Admin</option>
                                        <option value="kasir">Kasir</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>