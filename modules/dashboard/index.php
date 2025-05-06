<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Dashboard';
$active_page = 'dashboard';

// Hitung total penjualan bulan ini
$stmt = $db->prepare("SELECT SUM(total_bayar) as total 
                     FROM penjualan 
                     WHERE MONTH(tanggal_penjualan) = MONTH(CURRENT_DATE())
                     AND YEAR(tanggal_penjualan) = YEAR(CURRENT_DATE())
                     AND status_pembayaran = 'lunas'");
$stmt->execute();
$total_penjualan = $stmt->fetch(PDO::FETCH_ASSOC);

// Hitung total produksi bulan ini
$stmt = $db->prepare("SELECT SUM(total_kue) as total 
                     FROM produksi 
                     WHERE MONTH(tanggal_produksi) = MONTH(CURRENT_DATE())
                     AND YEAR(tanggal_produksi) = YEAR(CURRENT_DATE())");
$stmt->execute();
$total_produksi = $stmt->fetch(PDO::FETCH_ASSOC);

// Hitung bahan yang hampir habis (di bawah stok minimal)
$stmt = $db->query("SELECT b.nama_bahan, b.stok_minimal, SUM(s.jumlah) as stok_aktual
                   FROM bahan_baku b
                   JOIN stok_bahan s ON b.id_bahan = s.id_bahan
                   GROUP BY b.id_bahan
                   HAVING stok_aktual < b.stok_minimal");
$bahan_habis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung kue yang hampir kadaluarsa (3 hari lagi)
$stmt = $db->prepare("SELECT k.nama_kue, s.jumlah, s.tanggal_kadaluarsa 
                     FROM stok_kue s
                     JOIN jenis_kue k ON s.id_jenis_kue = k.id_jenis_kue
                     WHERE s.tanggal_kadaluarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                     AND s.jumlah > 0");
$stmt->execute();
$kue_kadaluarsa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah total kue yang diproduksi bulan ini
$stmt = $db->prepare("SELECT SUM(total_kue) as total_kue 
                     FROM produksi 
                     WHERE MONTH(tanggal_produksi) = MONTH(CURRENT_DATE())
                     AND YEAR(tanggal_produksi) = YEAR(CURRENT_DATE())");
$stmt->execute();
$total_kue = $stmt->fetch(PDO::FETCH_ASSOC);


// Data untuk grafik penjualan 6 bulan terakhir
$stmt = $db->query("SELECT DATE_FORMAT(tanggal_penjualan, '%Y-%m') as bulan, 
                   SUM(total_bayar) as total
                   FROM penjualan
                   WHERE tanggal_penjualan >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
                   AND status_pembayaran = 'lunas'
                   GROUP BY DATE_FORMAT(tanggal_penjualan, '%Y-%m')
                   ORDER BY bulan");
$grafik_penjualan = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                <h5 class="m-b-10">Dashboard</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Beranda Admin</a></li>
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
                            <h3 class="mb-0">Dashboard</h3>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Penjualan Bulan Ini -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card bg-primary text-white mb-3 shadow-sm">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3">Penjualan Bulan Ini</h6>
                                    <div class="h5 card-text"><?= rupiah($total_penjualan['total'] ?? 0) ?></div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light shadow">
                                    <a href="../penjualan/" class="text-dark stretched-link small">Lihat Detail <i class="ti ti-arrow-right small"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Produksi Bulan Ini -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card bg-success text-white mb-3 shadow-sm">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3">Produksi Bulan Ini</h6>
                                    <div class="h5 card-text"><?= number_format($total_produksi['total'] ?? 0, 0, ',', '.') ?> Kue</div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light shadow">
                                    <a href="../produksi/" class="text-dark stretched-link small">Lihat Detail <i class="ti ti-arrow-right small"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Bahan Hampir Habis -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card bg-danger text-white mb-3 shadow-sm">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3">Bahan Hampir Habis</h6>
                                    <div class="h5 card-text"><?= count($bahan_habis) ?> Jenis</div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light shadow">
                                    <a href="../bahan/" class="text-dark stretched-link small">Lihat Detail <i class="ti ti-arrow-right text-dark small"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Stok Kue -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card bg-info text-white mb-3 shadow-sm">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3">Jenis Kue</h6>
                                    <div class="h5 card-text">Kelola Stok Kue Real</div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light shadow">
                                    <a href="../kue/" class="text-dark stretched-link small">Lihat Detail <i class="ti ti-arrow-right small"></i></a>
                                </div>
                            </div>
                        </div>



                        <!-- <div class="col-md-6 col-lg-3">
                        <div class="card bg-danger text-white mb-3 shadow-sm">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-2">Kue Hampir Kadaluarsa</h6>
                                <div class="h5 card-text"><?= count($kue_kadaluarsa) ?> Jenis</div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between py-2 bg-light shadow">
                                <a href="../penjualan/add.php" class="text-white stretched-link small">Lihat Detail</a>
                                <i class="fas fa-arrow-right small"></i>
                            </div>
                        </div>
                    </div> -->
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-warning">
                                    <h5 class="text-white">Grafik Penjualan 6 Bulan Terakhir</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartPenjualan" height="100"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header bg-danger shadow-sm ">
                                    <h5 class="text-white">Tabel Bahan Hampir Habis</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (count($bahan_habis) >= 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>No</th> <!-- Tambahkan kolom No -->
                                                        <th>Bahan</th>
                                                        <th>Stok</th>
                                                        <th>Minimal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($bahan_habis) > 0): ?>
                                                        <?php foreach ($bahan_habis as $index => $bahan): ?>
                                                            <tr>
                                                                <td><?= $index + 1 ?></td> <!-- Tampilkan nomor urut -->
                                                                <td><?= $bahan['nama_bahan'] ?></td>
                                                                <td><?= $bahan['stok_aktual'] ?></td>
                                                                <td><?= $bahan['stok_minimal'] ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center">Tidak ada bahan yang stoknya di bawah minimal</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    // Grafik Penjualan
                    const ctx = document.getElementById('chartPenjualan').getContext('2d');
                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: [<?= implode(',', array_map(function ($item) {
                                            return "'" . date('M Y', strtotime($item['bulan'] . '-01')) . "'";
                                        }, $grafik_penjualan)) ?>],
                            datasets: [{
                                label: 'Total Penjualan (Rp)',
                                data: [<?= implode(',', array_column($grafik_penjualan, 'total')) ?>],
                                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'Rp ' + value.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                </script>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>