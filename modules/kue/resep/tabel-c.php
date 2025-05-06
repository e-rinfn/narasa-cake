<?php
require_once '../../../config/config.php';
require_once '../../../includes/functions.php';
checkAuth();

$page_title = 'Tambah Resep Kue';
$active_page = 'resep';

// Ambil semua bahan baku
$stmt = $db->query("SELECT b.*, k.nama_kategori, s.nama_satuan 
                   FROM bahan_baku b
                   JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                   JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                   WHERE b.aktif = 1
                   ORDER BY b.nama_bahan");
$all_bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data jenis kue
$stmt = $db->query("SELECT * FROM jenis_kue WHERE aktif = 1 ORDER BY nama_kue");
$jenis_kue = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_jenis_kue = $_POST['id_jenis_kue'];
    $nama_resep = $_POST['nama_resep'];
    $porsi = $_POST['porsi'];
    $aktif = $_POST['aktif'] ?? 0;
    $bahan_resep = $_POST['bahan'] ?? [];

    try {
        $db->beginTransaction();

        // 1. Insert data resep baru
        $stmt = $db->prepare("INSERT INTO resep_kue (id_jenis_kue, nama_resep, porsi, aktif) 
                             VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_jenis_kue, $nama_resep, $porsi, $aktif]);
        $id_resep = $db->lastInsertId(); // Ambil ID resep yang baru saja dimasukkan

        // 2. Insert detail bahan resep baru
        foreach ($bahan_resep as $id_bahan => $detail) {
            $jumlah = $detail['jumlah'];
            $catatan = $detail['catatan'] ?? '';

            if ($jumlah > 0) {
                $stmt = $db->prepare("INSERT INTO detail_resep (id_resep, id_bahan, jumlah, catatan) 
                                     VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_resep, $id_bahan, $jumlah, $catatan]);
            }
        }

        $db->commit();
        redirectWithMessage('../resep/index.php', 'success', 'Resep berhasil ditambahkan');
    } catch (PDOException $e) {
        $db->rollBack();
        redirectWithMessage('add.php', 'danger', 'Error: ' . $e->getMessage());
    }
}

include '../../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title><?= $page_title ?></title>
    <style>
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .pagination-info {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .page-link {
            color: #0d6efd;
        }

        .per-page-selector {
            width: 80px;
            display: inline-block;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .badge {
            font-weight: 500;
        }
    </style>
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <?php include '../../../includes/sidebar2.php'; ?>

    <?php include '../../../includes/navbar.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Manajemen</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item active" aria-current="page">Resep Kue</li>
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
                        <h3>Tambah Resep Kue</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formResep">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jenis Kue</label>
                                        <select name="id_jenis_kue" class="form-control" required>
                                            <option value="">-- Pilih Jenis Kue --</option>
                                            <?php foreach ($jenis_kue as $jk): ?>
                                                <option value="<?= $jk['id_jenis_kue'] ?>"><?= $jk['nama_kue'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nama Resep</label>
                                        <input type="text" name="nama_resep" class="form-control" required>
                                    </div>

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="aktif" id="aktifCheckbox" class="form-check-input"
                                                value="1">
                                            <label class="form-check-label" for="aktifCheckbox">Status Aktif</label>
                                        </div>
                                        <small class="form-text text-muted">Centang untuk mengaktifkan resep ini</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jumlah Porsi</label>
                                        <input type="number" name="porsi" class="form-control" min="1" required>
                                        <small class="text-muted">Jumlah kue yang dihasilkan dari resep ini</small>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h5>Bahan-bahan</h5>
                            <div class="form-group">
                                <input type="text" id="filterBahan" class="form-control mb-3" placeholder="Cari bahan...">
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabelBahan">
                                    <thead>
                                        <tr>
                                            <th>Nama Bahan</th>
                                            <th>Kategori</th>
                                            <th width="15%">Jumlah</th>
                                            <th width="15%">Satuan</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_bahan as $b): ?>
                                            <tr>
                                                <td><?= $b['nama_bahan'] ?></td>
                                                <td><?= $b['nama_kategori'] ?></td>
                                                <td>
                                                    <input type="number" name="bahan[<?= $b['id_bahan'] ?>][jumlah]"
                                                        class="form-control" min="0" step="0.01" value="0">
                                                </td>
                                                <td><?= $b['nama_satuan'] ?></td>
                                                <td>
                                                    <input type="text" name="bahan[<?= $b['id_bahan'] ?>][catatan]"
                                                        class="form-control" placeholder="Opsional">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">Simpan Resep</button>
                                <a href="../resep/index.php" class="btn btn-secondary">Kembali</a>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- [ Main Content ] end -->
            </div>
        </div>
    </div>

    <?php include '../../../includes/footer.php'; ?>
</body>

</html>