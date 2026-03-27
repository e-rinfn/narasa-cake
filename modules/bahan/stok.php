<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Kelola Stok Bahan';
$active_page = 'bahan';

// Ambil ID dari URL
$id_bahan = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data bahan
$stmt = $db->prepare("SELECT b.*, s.nama_satuan 
                     FROM bahan_baku b
                     JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                     WHERE b.id_bahan = ?");
$stmt->execute([$id_bahan]);
$bahan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bahan) {
    redirectWithMessage('index.php', 'danger', 'Bahan baku tidak ditemukan');
}

// Ambil data stok bahan (FIFO)
$stmt = $db->prepare("SELECT * FROM stok_bahan 
                     WHERE id_bahan = ? 
                     ORDER BY tanggal_kadaluarsa ASC");
$stmt->execute([$id_bahan]);
$stok = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total stok (berdasarkan jumlah sisa)
$total_stok = array_sum(array_column($stok, 'jumlah'));

// Form tambah stok
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jumlah_masuk = $_POST['jumlah'];
    $batch_number = $_POST['batch_number'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'];
    $harga_per_satuan = $_POST['harga_per_satuan'];
    $keterangan = $_POST['keterangan'];

    try {
        $stmt = $db->prepare("INSERT INTO stok_bahan 
                             (id_bahan, jumlah, jumlah_masuk, batch_number, tanggal_masuk, 
                              tanggal_kadaluarsa, harga_per_satuan, keterangan) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id_bahan,
            $jumlah_masuk, // Jumlah awal sama dengan jumlah_masuk
            $jumlah_masuk,
            $batch_number,
            $tanggal_masuk,
            $tanggal_kadaluarsa,
            $harga_per_satuan,
            $keterangan
        ]);

        redirectWithMessage("index.php", 'success', 'Stok berhasil ditambahkan');
    } catch (PDOException $e) {
        redirectWithMessage("stok.php?id=$id_bahan", 'danger', 'Error: ' . $e->getMessage());
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

            <?php displayMessage(); ?>

            <div class="row">
                <!-- [ Main Content ] start -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>Kelola Stok Bahan: <?= htmlspecialchars($bahan['nama_bahan']) ?></h3>
                        </div>
                        <div class="card-body mt-3">
                            <div class="row mb-4">
                                <!-- Informasi Bahan -->
                                <div class="col-md-4">
                                    <div class="card bg-light h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">Informasi Bahan</h5>
                                            <p class="card-text">
                                                <strong>Satuan:</strong> <?= htmlspecialchars($bahan['nama_satuan']) ?><br>
                                                <strong>Stok Minimal:</strong> <?= $bahan['stok_minimal'] ?> <?= htmlspecialchars($bahan['nama_satuan']) ?><br>
                                                <strong>Stok Saat Ini:</strong>
                                                <span class="<?= $total_stok < $bahan['stok_minimal'] ? 'text-danger' : 'text-success' ?>">
                                                    <?= $total_stok ?> <?= htmlspecialchars($bahan['nama_satuan']) ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stok Saat Ini -->
                                <div class="col-md-4">
                                    <div class="card bg-light shadow-sm h-100">
                                        <div class="card-body text-center">
                                            <h5 class="text-muted mb-2 mt-3">
                                                Stok <span class="fw-bold text-dark"><?= htmlspecialchars($bahan['nama_bahan']) ?></span> Saat Ini
                                            </h5>
                                            <h2 class="<?= $total_stok < $bahan['stok_minimal'] ? 'text-danger' : 'text-success' ?> fw-bold">
                                                <?= $total_stok ?> <?= htmlspecialchars($bahan['nama_satuan']) ?>
                                            </h2>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Bahan Keluar -->
                                <div class="col-md-4">
                                    <?php
                                    $total_keluar = 0;
                                    foreach ($stok as $s) {
                                        $total_keluar += ($s['jumlah_masuk'] - $s['jumlah']);
                                    }
                                    ?>
                                    <div class="card text-white bg-light shadow-sm h-100">
                                        <div class="card-body text-center mb-2 mt-3">
                                            <h5 class="card-title text-dark">Total Bahan Keluar</h5>
                                            <p class="card-text text-dark" style="font-size: 28px; font-weight: bold;">
                                                <?= number_format($total_keluar) ?> <?= htmlspecialchars($bahan['nama_satuan']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Tambah Stok Baru</h4>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST">
                                                <input type="hidden" name="id_bahan" value="<?= $id_bahan ?>">

                                                <div class="form-group">
                                                    <label>Jumlah Masuk</label>
                                                    <input type="number" class="form-control" name="jumlah" min="0.01" step="0.01" required>
                                                    <small class="text-muted">Satuan: <?= htmlspecialchars($bahan['nama_satuan']) ?></small>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label>Tanggal Masuk</label>
                                                            <input type="date" class="form-control" name="tanggal_masuk"
                                                                value="<?= date('Y-m-d') ?>" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- <div class="form-group">
                                                    <label>Keterangan</label>
                                                    <textarea class="form-control" name="keterangan" rows="2"></textarea>
                                                </div> -->

                                                <button type="button" class="btn btn-primary" id="btn-simpan-stok">Simpan Stok</button>

                                                <a href="index.php" class="btn btn-secondary"> Kembali</a>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Riwayat Stok (FIFO)</h4>
                                            <small class="text-warning">*Yang pertama masuk yang pertama digunakan</small>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead class="text-center">
                                                        <tr>
                                                            <th>No</th>
                                                            <th>Jumlah Masuk</th>
                                                            <th>Keluar</th>
                                                            <th>Sisa Stok</th>
                                                            <th>Masuk</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php $no = 1; ?>
                                                        <?php foreach ($stok as $s): ?>
                                                            <tr>
                                                                <td><?= $no++ ?></td>
                                                                <td><?= htmlspecialchars($s['jumlah_masuk']) ?></td>
                                                                <td><?= htmlspecialchars($s['jumlah_masuk'] - $s['jumlah']) ?></td>
                                                                <td><?= htmlspecialchars($s['jumlah']) ?></td>
                                                                <td><?= tgl_indo($s['tanggal_masuk']) ?></td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-danger btn-sm btn-batalkan-stok"
                                                                        data-id="<?= $s['id_stok'] ?>"
                                                                        data-jumlah="<?= $s['jumlah_masuk'] ?>"
                                                                        data-keluar="<?= $s['jumlah_masuk'] - $s['jumlah'] ?>">
                                                                        <i class="ti ti-trash"></i> Batalkan
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>


                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- [ Main Content ] end -->
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const simpanStokBtn = document.getElementById("btn-simpan-stok");

            if (simpanStokBtn) {
                simpanStokBtn.addEventListener("click", function() {
                    Swal.fire({
                        title: "Yakin ingin menyimpan stok?",
                        text: "Pastikan data stok sudah benar.",
                        icon: "question",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Simpan",
                        cancelButtonText: "Batal",
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#6c757d"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            simpanStokBtn.closest("form").submit();
                        }
                    });
                });
            }

            // Pembatalan stok
            const batalkanBtns = document.querySelectorAll(".btn-batalkan-stok");
            batalkanBtns.forEach(function(btn) {
                btn.addEventListener("click", function() {
                    const idStok = this.getAttribute("data-id");
                    const jumlahMasuk = this.getAttribute("data-jumlah");
                    const jumlahKeluar = this.getAttribute("data-keluar");

                    if (parseFloat(jumlahKeluar) > 0) {
                        Swal.fire({
                            title: "Tidak Dapat Dibatalkan!",
                            text: "Stok ini sudah digunakan sebanyak " + jumlahKeluar + " dan tidak dapat dibatalkan.",
                            icon: "error",
                            confirmButtonText: "OK",
                            confirmButtonColor: "#6c757d"
                        });
                        return;
                    }

                    Swal.fire({
                        title: "Batalkan Stok?",
                        text: "Anda akan membatalkan stok sebanyak " + jumlahMasuk + ". Data akan dihapus permanen!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Batalkan",
                        cancelButtonText: "Tidak",
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#6c757d"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "hapus_stok.php?id=" + idStok + "&id_bahan=<?= $id_bahan ?>";
                        }
                    });
                });
            });
        });
    </script>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>