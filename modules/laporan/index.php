<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Laporan';
$active_page = 'laporan';

include '../../includes/header.php';
?>
<?php include '../../includes/sidebar.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">

        <?php displayMessage(); ?>

        <div class="card">

            <div class="card-body">
                <div class="row">
                    <!-- Kartu Laporan -->
                    <div class="col-md-3 mb-4">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-cash-register fa-2x text-primary mb-2"></i>
                                <h6 class="card-title mt-2">Laporan Penjualan</h6>
                                <p class="text-muted small">Transaksi penjualan per periode</p>
                                <a href="penjualan.php" class="btn btn-sm btn-primary">Buka</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-industry fa-2x text-success mb-2"></i>
                                <h6 class="card-title mt-2">Laporan Produksi</h6>
                                <p class="text-muted small">Produksi kue per periode</p>
                                <a href="produksi.php" class="btn btn-sm btn-success">Buka</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-boxes fa-2x text-warning mb-2"></i>
                                <h6 class="card-title mt-2">Laporan Stok Bahan</h6>
                                <p class="text-muted small">Stok bahan & peringatan</p>
                                <a href="bahan.php" class="btn btn-sm btn-warning">Buka</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-box fa-2x text-info mb-2"></i>
                                <h6 class="card-title mt-2">Laporan Penggunaan</h6>
                                <p class="text-muted small">Penggunaan bahan baku</p>
                                <a href="penggunaan-bahan.php" class="btn btn-sm btn-info">Buka</a>
                            </div>
                        </div>
                    </div>

                    <!-- Laporan Pelanggan -->
                    <!-- <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Laporan Pelanggan</h5>
                                <p class="card-text">Laporan transaksi dan poin pelanggan</p>
                                <a href="pelanggan.php" class="btn btn-secondary">Buka Laporan</a>
                            </div>
                        </div>
                    </div> -->

                    <!-- Laporan Pembelian Bahan -->
                    <!-- <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-shopping-cart fa-3x text-danger mb-3"></i>
                                <h5 class="card-title">Laporan Pembelian</h5>
                                <p class="card-text">Laporan pembelian bahan baku per periode</p>
                                <a href="pembelian.php" class="btn btn-danger">Buka Laporan</a>
                            </div>
                        </div>
                    </div> -->
                </div>

                <!-- Quick Report Card -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Quick Report</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Total Penjualan Bulan Ini -->
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h6>Total Penjualan Bulan Ini</h6>
                                                <h3 class="text-primary">
                                                    <?php
                                                    $start = date('Y-m-01');
                                                    $end = date('Y-m-t');
                                                    $stmt = $db->prepare("SELECT SUM(total_bayar) as total FROM penjualan 
                                                                WHERE tanggal_penjualan BETWEEN ? AND ?
                                                                AND status_pembayaran = 'lunas'");
                                                    $stmt->execute([$start, $end]);
                                                    $total = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    echo rupiah($total['total'] ?? 0);
                                                    ?>
                                                </h3>
                                                <small><?= tgl_indo($start) ?> - <?= tgl_indo($end) ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bahan Hampir Habis -->
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h6>Bahan Stok Minimal</h6>
                                                <h3 class="text-warning">
                                                    <?php
                                                    $stmt = $db->query("SELECT COUNT(*) as total FROM bahan_baku b
                                                               WHERE (SELECT COALESCE(SUM(jumlah), 0) FROM stok_bahan 
                                                               WHERE id_bahan = b.id_bahan) <= b.stok_minimal");
                                                    $total = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    echo $total['total'] ?? 0;
                                                    ?>
                                                </h3>
                                                <small>Bahan perlu restock</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kue Akan Kadaluarsa -->
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h6>Kue Akan Kadaluarsa</h6>
                                                <h3 class="text-danger">
                                                    <?php
                                                    $today = date('Y-m-d');
                                                    $next_week = date('Y-m-d', strtotime('+7 days'));
                                                    $stmt = $db->prepare("SELECT SUM(jumlah) as total FROM stok_kue
                                                                WHERE tanggal_kadaluarsa BETWEEN ? AND ?");
                                                    $stmt->execute([$today, $next_week]);
                                                    $total = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    echo $total['total'] ?? 0;
                                                    ?>
                                                </h3>
                                                <small>Dalam 7 hari ke depan</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>