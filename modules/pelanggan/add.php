<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Tambah Pelanggan';
$active_page = 'pelanggan';

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
            $stmt = $db->prepare("SELECT id_pelanggan FROM pelanggan WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("Email sudah digunakan oleh pelanggan lain");
            }
        }

        $stmt = $db->prepare("INSERT INTO pelanggan 
                            (nama_pelanggan, id_kategori_pelanggan, alamat, no_telepon, email) 
                            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $id_kategori, $alamat, $telepon, $email]);

        redirectWithMessage('index.php', 'success', 'Pelanggan berhasil ditambahkan');
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
                                <li class="breadcrumb-item"><a href="javascript: void(0)">Tambah Data Pelanggan</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->
            <div class="row">

                <!-- [ Main Content ] start -->
                <div class="card">
                    <div class="card-header">
                        <h3>Tambah Pelanggan</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Nama Pelanggan</label>
                                <input type="text" name="nama_pelanggan" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Kategori Pelanggan</label>
                                <select name="id_kategori_pelanggan" class="form-control">
                                    <option value="">-- Umum --</option>
                                    <?php foreach ($kategori as $k): ?>
                                        <option value="<?= $k['id_kategori_pelanggan'] ?>"><?= $k['nama_kategori'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>No. Telepon</label>
                                        <input type="text" name="no_telepon" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>
                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>


    <?php include '../../includes/footer.php'; ?>