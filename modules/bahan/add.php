<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Tambah Bahan Baku';
$active_page = 'bahan';

// Ambil data kategori dan satuan
$kategori = $db->query("SELECT * FROM kategori_bahan ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);
$satuan = $db->query("SELECT * FROM satuan_bahan ORDER BY nama_satuan")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_bahan = $_POST['nama_bahan'];
    $id_kategori = $_POST['id_kategori'];
    $id_satuan = $_POST['id_satuan'];
    $stok_minimal = $_POST['stok_minimal'];
    $harga_per_satuan = $_POST['harga_per_satuan'] ?? null;
    $metode_penyimpanan = $_POST['metode_penyimpanan'] ?? null;

    try {
        $stmt = $db->prepare("INSERT INTO bahan_baku 
                             (nama_bahan, id_kategori, id_satuan, stok_minimal, harga_per_satuan, metode_penyimpanan) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama_bahan, $id_kategori, $id_satuan, $stok_minimal, $harga_per_satuan, $metode_penyimpanan]);

        redirectWithMessage('index.php', 'success', 'Bahan baku berhasil ditambahkan');
    } catch (PDOException $e) {
        redirectWithMessage('add.php', 'danger', 'Error: ' . $e->getMessage());
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

            <div class="row">
                <!-- [ Main Content ] start -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Tambah Bahan Baku</h3>
                        </div>
                    </div>

                    <div class="card-body">


                        <!-- Form Input -->
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nama_bahan">Nama Bahan</label>
                                        <input type="text" class="form-control" id="nama_bahan" name="nama_bahan" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="stok_minimal">Stok Minimal</label>
                                        <input type="number" class="form-control" id="stok_minimal" name="stok_minimal"
                                            min="0" step="0.01" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_kategori">Kategori</label>
                                        <select class="form-control" id="id_kategori" name="id_kategori" required>
                                            <option value="">-- Pilih Kategori --</option>
                                            <?php foreach ($kategori as $k): ?>
                                                <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_satuan">Satuan</label>
                                        <select class="form-control" id="id_satuan" name="id_satuan" required>
                                            <option value="">-- Pilih Satuan --</option>
                                            <?php foreach ($satuan as $s): ?>
                                                <option value="<?= $s['id_satuan'] ?>"><?= $s['nama_satuan'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>


                            <!-- <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="harga_per_satuan">Harga per Satuan</label>
                                        <input type="number" class="form-control" id="harga_per_satuan" name="harga_per_satuan"
                                            min="0" required>
                                    </div>
                                </div>
                            </div> -->

                            <!-- <div class="form-group">
                                <label for="metode_penyimpanan">Metode Penyimpanan</label>
                                <textarea class="form-control" id="metode_penyimpanan" name="metode_penyimpanan" rows="3"></textarea>
                            </div> -->

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                        </form>




                    </div>
                </div>
                <!-- [ Main Content ] end -->
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>