<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Edit Pelanggan';
$active_page = 'pelanggan';

// Ambil ID pelanggan dari URL
$id_pelanggan = $_GET['id'] ?? 0;

// Ambil data pelanggan
$stmt = $db->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$stmt->execute([$id_pelanggan]);
$pelanggan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pelanggan) {
    redirectWithMessage('index.php', 'danger', 'Pelanggan tidak ditemukan');
}

// Ambil data kategori pelanggan
$stmt = $db->query("SELECT * FROM kategori_pelanggan ORDER BY nama_kategori");
$kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama_pelanggan'];
    $id_kategori = $_POST['id_kategori_pelanggan'] ?: NULL;
    $alamat = $_POST['alamat'];
    $telepon = $_POST['no_telepon'];
    $email = $_POST['email'];

    try {
        // Validasi email unik
        if (!empty($email)) {
            $stmt = $db->prepare("SELECT id_pelanggan FROM pelanggan WHERE email = ? AND id_pelanggan != ?");
            $stmt->execute([$email, $id_pelanggan]);
            if ($stmt->fetch()) {
                throw new Exception("Email sudah digunakan oleh pelanggan lain");
            }
        }

        $stmt = $db->prepare("UPDATE pelanggan 
                             SET nama_pelanggan = ?, 
                                 id_kategori_pelanggan = ?, 
                                 alamat = ?, 
                                 no_telepon = ?, 
                                 email = ?
                             WHERE id_pelanggan = ?");
        $stmt->execute([$nama, $id_kategori, $alamat, $telepon, $email, $id_pelanggan]);

        redirectWithMessage('index.php', 'success', 'Data pelanggan berhasil diperbarui');
    } catch (Exception $e) {
        redirectWithMessage('edit.php?id=' . $id_pelanggan, 'danger', 'Error: ' . $e->getMessage());
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
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Pelanggan</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Pelanggan</a></li>
                                <li class="breadcrumb-item"><a href="javascript: void(0)">Edit Data Pelanggan</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->
            <div class="row">
                <div class="card">
                    <!-- [ Main Content ] start -->

                    <div class="card-header">
                        <h3>Edit Pelanggan</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Nama Pelanggan</label>
                                <input type="text" name="nama_pelanggan" class="form-control" value="<?= htmlspecialchars($pelanggan['nama_pelanggan']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Kategori Pelanggan</label>
                                <select name="id_kategori_pelanggan" class="form-control">
                                    <option value="">-- Umum --</option>
                                    <?php foreach ($kategori as $k): ?>
                                        <option value="<?= $k['id_kategori_pelanggan'] ?>" <?= $k['id_kategori_pelanggan'] == $pelanggan['id_kategori_pelanggan'] ? 'selected' : '' ?>>
                                            <?= $k['nama_kategori'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($pelanggan['alamat']) ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>No. Telepon</label>
                                        <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($pelanggan['no_telepon']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($pelanggan['email']) ?>">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>
                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>


    <?php include '../../includes/footer.php'; ?>