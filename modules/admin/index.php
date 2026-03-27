<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Hanya superadmin yang bisa mengakses
if ($_SESSION['level'] != 'superadmin') {
    redirectWithMessage('../../index.php', 'danger', 'Anda tidak memiliki akses ke halaman ini');
}

$page_title = 'Manajemen Admin';
$active_page = 'admin';

// Ambil data admin
$stmt = $db->query("SELECT * FROM admin ORDER BY level DESC, nama_lengkap ASC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <h3>Daftar Admin</h3>
                    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Admin</a>
                </div>
                <div class="card-body mt-3">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Level</th>
                                <th>Terakhir Login</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $i => $admin): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($admin['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($admin['username']) ?></td>
                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                    <td>
                                        <span class="badge text-dark
                            <?= $admin['level'] == 'superadmin' ? 'badge-danger' : ($admin['level'] == 'admin' ? 'badge-primary' : 'badge-secondary') ?>">
                                            <?= ucfirst($admin['level']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $admin['terakhir_login'] ?
                                            date('d/m/Y H:i', strtotime($admin['terakhir_login'])) : 'Belum pernah' ?>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?= $admin['id_admin'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($admin['id_admin'] != $_SESSION['user_id']): ?>
                                            <a href="delete.php?id=<?= $admin['id_admin'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Hapus admin ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>