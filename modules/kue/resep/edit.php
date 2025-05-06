<?php
require_once '../../../config/config.php';
require_once '../../../includes/functions.php';
checkAuth();

$page_title = 'Edit Resep Kue';
$active_page = 'resep';

// Ambil ID resep dari URL
$id_resep = $_GET['id'] ?? 0;

// Ambil data resep
$stmt = $db->prepare("SELECT r.*, k.nama_kue 
                     FROM resep_kue r
                     JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
                     WHERE r.id_resep = ?");
$stmt->execute([$id_resep]);
$resep = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resep) {
    redirectWithMessage('../resep/index.php', 'danger', 'Resep tidak ditemukan');
}

// Ambil detail bahan resep
$stmt = $db->prepare("SELECT dr.*, b.nama_bahan, k.nama_kategori, s.nama_satuan 
                     FROM detail_resep dr
                     JOIN bahan_baku b ON dr.id_bahan = b.id_bahan
                     JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                     JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                     WHERE dr.id_resep = ?");
$stmt->execute([$id_resep]);
$detail_resep = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    // $versi = $_POST['versi'];
    // $instruksi = $_POST['instruksi'];
    $porsi = is_numeric($_POST['porsi']) ? (int)$_POST['porsi'] : 1;
    $aktif = $_POST['aktif'] ?? 0;
    $bahan_resep = $_POST['bahan'] ?? [];

    try {
        $db->beginTransaction();

        // 1. Update data resep
        $stmt = $db->prepare("UPDATE resep_kue 
                             SET id_jenis_kue = ?, nama_resep = ?, porsi = ?, aktif = ?
                             WHERE id_resep = ?");
        $stmt->execute([$id_jenis_kue, $nama_resep, $porsi, $aktif, $id_resep]);

        // 2. Hapus semua detail resep lama
        $stmt = $db->prepare("DELETE FROM detail_resep WHERE id_resep = ?");
        $stmt->execute([$id_resep]);

        // 3. Insert detail bahan resep baru
        foreach ($bahan_resep as $id_bahan => $detail) {
            $jumlah = $detail['jumlah'];
            $catatan = $detail['catatan'] ?? '';

            if ($jumlah > 0) {
                $stmt = $db->prepare("INSERT INTO detail_resep 
                                     (id_resep, id_bahan, jumlah, catatan) 
                                     VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_resep, $id_bahan, $jumlah, $catatan]);
            }
        }

        $db->commit();
        redirectWithMessage('../resep/index.php', 'success', 'Resep berhasil diperbarui');
    } catch (PDOException $e) {
        $db->rollBack();
        redirectWithMessage('edit.php?id=' . $id_resep, 'danger', 'Error: ' . $e->getMessage());
    }
}

include '../../../includes/header.php';
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


    <!-- [ Sidebar Menu ] start -->
    <?php include '../../../includes/sidebar2.php'; ?>

    <!-- [ Sidebar Menu ] end -->

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
                                <li class="breadcrumb-item"><a href="index.php">Resep Kue</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit</li>
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
                        <h3>Edit Resep Kue</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formResep">
                            <div class="row">
                                <div class="col-md-6">


                                    <div class="form-group">
                                        <label>Nama Resep Kue</label>
                                        <select name="id_jenis_kue" class="form-control" required>
                                            <option value="">-- Pilih Nama Kue --</option>
                                            <?php
                                            // Ambil semua jenis kue yang sudah dipakai resep LAIN (bukan resep yang sedang diedit)
                                            $stmt = $db->prepare("SELECT id_jenis_kue FROM resep_kue WHERE id_resep != ?");
                                            $stmt->execute([$id_resep]);
                                            $jenis_kue_terpakai = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                            ?>
                                            <?php foreach ($jenis_kue as $jk): ?>
                                                <?php
                                                // Kalau jenis kue sudah dipakai resep lain DAN bukan jenis kue saat ini, jangan tampilkan
                                                if (in_array($jk['id_jenis_kue'], $jenis_kue_terpakai) && $jk['id_jenis_kue'] != $resep['id_jenis_kue']) {
                                                    continue;
                                                }
                                                ?>
                                                <option value="<?= $jk['id_jenis_kue'] ?>" <?= ($jk['id_jenis_kue'] == $resep['id_jenis_kue']) ? 'selected' : '' ?>>
                                                    <?= $jk['nama_kue'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>


                                    <div class="form-group">
                                        <label>Deskripsi Resep Kue</label>
                                        <input type="text" name="nama_resep" class="form-control" value="<?= $resep['nama_resep'] ?>" required>
                                    </div>

                                    <!-- <div class="form-group">
                                    <label>Versi Resep</label>
                                    <input type="text" name="versi" class="form-control" value="<?= $resep['versi'] ?>" required>
                                </div> -->

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="aktif" id="aktifCheckbox" class="form-check-input"
                                                value="1" <?= $resep['aktif'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="aktifCheckbox">Status Aktif</label>
                                        </div>
                                        <small class="form-text text-muted">Centang untuk mengaktifkan resep ini</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jumlah Porsi</label>
                                        <input type="number" name="porsi" class="form-control" min="1" value="<?= $resep['porsi'] ?>" required>
                                        <small class="text-muted">Jumlah kue yang dihasilkan dari resep ini</small>
                                    </div>

                                    <!-- <div class="form-group">
                                    <label>Instruksi Pembuatan</label>
                                    <textarea name="instruksi" class="form-control" rows="5" required><?= $resep['instruksi'] ?></textarea>
                                </div> -->
                                </div>
                            </div>

                            <hr>

                            <h5>Bahan-bahan</h5>

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
                                        <?php
                                        // Buat array untuk memudahkan pencarian bahan yang sudah ada di resep
                                        $bahan_resep = [];
                                        foreach ($detail_resep as $dr) {
                                            $bahan_resep[$dr['id_bahan']] = $dr;
                                        }

                                        foreach ($all_bahan as $b):
                                            $detail = $bahan_resep[$b['id_bahan']] ?? null;
                                        ?>
                                            <tr>
                                                <td><?= $b['nama_bahan'] ?></td>
                                                <td><?= $b['nama_kategori'] ?></td>
                                                <td>
                                                    <input type="number" name="bahan[<?= $b['id_bahan'] ?>][jumlah]"
                                                        class="form-control" min="0" step="0.01"
                                                        value="<?= $detail ? $detail['jumlah'] : 0 ?>">
                                                </td>
                                                <td><?= $b['nama_satuan'] ?></td>
                                                <td>
                                                    <input type="text" name="bahan[<?= $b['id_bahan'] ?>][catatan]"
                                                        class="form-control" placeholder="Opsional"
                                                        value="<?= $detail ? $detail['catatan'] : '' ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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