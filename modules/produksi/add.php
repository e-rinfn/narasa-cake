<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
checkAuth();

$page_title = 'Tambah Produksi Kue';
$active_page = 'produksi';

// Ambil data resep kue
$stmt = $db->query("SELECT r.id_resep, r.nama_resep, r.versi, k.nama_kue 
                   FROM resep_kue r
                   JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
                   WHERE r.aktif = 1");
$resep = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_resep = $_POST['id_resep'];
    $jumlah_batch = $_POST['jumlah_batch'];
    $tanggal_produksi = $_POST['tanggal_produksi'];
    $catatan = $_POST['catatan'];
    $id_admin = $_SESSION['user_id'];

    // Validasi dasar
    if (!is_numeric($jumlah_batch) || $jumlah_batch <= 0) {
        redirectWithMessage('add.php', 'danger', 'Jumlah batch harus lebih dari 0');
    }
    if (empty($tanggal_produksi)) {
        redirectWithMessage('add.php', 'danger', 'Tanggal produksi wajib diisi');
    }

    try {
        $db->beginTransaction();

        // 1. Ambil detail resep
        $stmt = $db->prepare("SELECT * FROM detail_resep WHERE id_resep = ?");
        $stmt->execute([$id_resep]);
        $detail_resep = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Ambil info resep
        $stmt = $db->prepare("SELECT porsi FROM resep_kue WHERE id_resep = ?");
        $stmt->execute([$id_resep]);
        $resep_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resep_info) {
            throw new Exception("Resep tidak ditemukan.");
        }

        $total_kue = $jumlah_batch * $resep_info['porsi'];

        // 3. Insert data produksi
        $stmt = $db->prepare("INSERT INTO produksi (id_resep, jumlah_batch, total_kue, tanggal_produksi, id_admin, catatan) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_resep, $jumlah_batch, $total_kue, $tanggal_produksi, $id_admin, $catatan]);
        $id_produksi = $db->lastInsertId();

        // 4. Cek stok semua bahan terlebih dahulu
        $stok_kurang = [];

        foreach ($detail_resep as $bahan) {
            $jumlah_dibutuhkan = $bahan['jumlah'] * $jumlah_batch;

            $stmt = $db->prepare("SELECT SUM(jumlah) FROM stok_bahan WHERE id_bahan = ?");
            $stmt->execute([$bahan['id_bahan']]);
            $stok_tersedia = $stmt->fetchColumn() ?? 0;

            if ($stok_tersedia < $jumlah_dibutuhkan) {
                $nama_bahan = getBahanName($db, $bahan['id_bahan']);
                $stok_kurang[] = "# $nama_bahan (dibutuhkan: $jumlah_dibutuhkan, tersedia: $stok_tersedia)";
            }
        }

        if (!empty($stok_kurang)) {
            $pesan = "Stok tidak mencukupi: " . implode(", ", $stok_kurang);
            throw new Exception(nl2br(htmlentities($pesan)));
        }

        // 5. Kurangi stok bahan dan catat penggunaan
        foreach ($detail_resep as $bahan) {
            $jumlah_dibutuhkan = $bahan['jumlah'] * $jumlah_batch;

            // Ambil stok dengan FIFO
            $stmt = $db->prepare("SELECT * FROM stok_bahan 
                         WHERE id_bahan = ? AND jumlah > 0 
                         ORDER BY tanggal_kadaluarsa ASC");
            $stmt->execute([$bahan['id_bahan']]);
            $stok = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sisa_kebutuhan = $jumlah_dibutuhkan;
            foreach ($stok as $stok_item) {
                if ($sisa_kebutuhan <= 0) break;

                $jumlah_dipakai = min($sisa_kebutuhan, $stok_item['jumlah']);

                // Insert ke penggunaan_bahan
                $stmt = $db->prepare("INSERT INTO penggunaan_bahan 
                             (id_produksi, id_bahan, id_stok, jumlah_digunakan) 
                             VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_produksi, $bahan['id_bahan'], $stok_item['id_stok'], $jumlah_dipakai]);

                // Update stok
                $stmt = $db->prepare("UPDATE stok_bahan SET jumlah = jumlah - ? WHERE id_stok = ?");
                $stmt->execute([$jumlah_dipakai, $stok_item['id_stok']]);

                $sisa_kebutuhan -= $jumlah_dipakai;
            }
        }

        // 7. Tambah stok kue hasil produksi
        $stmt = $db->prepare("SELECT id_jenis_kue FROM resep_kue WHERE id_resep = ?");
        $stmt->execute([$id_resep]);
        $jenis_kue = $stmt->fetch(PDO::FETCH_ASSOC);

        $tanggal_kadaluarsa = date('Y-m-d', strtotime($tanggal_produksi . ' +7 days'));

        $stmt = $db->prepare("INSERT INTO stok_kue 
                     (id_jenis_kue, jumlah, tanggal_produksi, tanggal_kadaluarsa) 
                     VALUES (?, ?, ?, ?)");
        $stmt->execute([$jenis_kue['id_jenis_kue'], $total_kue, $tanggal_produksi, $tanggal_kadaluarsa]);


        $db->commit();
        redirectWithMessage('add.php', 'success', 'Produksi berhasil dicatat');
    } catch (Exception $e) {
        $db->rollBack();
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
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Manajemen</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Produksi</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Tambah</li>
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
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Tambah Produksi Baru</h3>
                        </div>
                    </div>



                    <div class="card-body">
                        <?php displayMessage(); ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Pilih Resep</label>
                                <select name="id_resep" class="form-control" required>
                                    <option value="">-- Pilih Resep --</option>
                                    <?php foreach ($resep as $r): ?>
                                        <option value="<?= $r['id_resep'] ?>">
                                            <?= $r['nama_kue'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Jumlah Batch</label>
                                <input type="number" name="jumlah_batch" class="form-control" min="1" required>
                            </div>

                            <div class="form-group">
                                <label>Tanggal Produksi</label>
                                <input type="date" name="tanggal_produksi" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="catatan" class="form-control" rows="3"></textarea>
                            </div>

                            <button type="button" class="btn btn-primary" id="btn-simpan-produksi">Simpan Produksi</button>

                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>
                <!-- [ Main Content ] end -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const simpanBtn = document.getElementById("btn-simpan-produksi");

            if (simpanBtn) {
                simpanBtn.addEventListener("click", function() {
                    Swal.fire({
                        title: "Yakin ingin menyimpan produksi?",
                        text: "Pastikan data yang diisi sudah benar.",
                        icon: "question",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Simpan",
                        cancelButtonText: "Batal",
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#6c757d"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            simpanBtn.closest("form").submit();
                        }
                    });
                });
            }
        });
    </script>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>