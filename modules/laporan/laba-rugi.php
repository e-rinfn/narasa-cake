<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Laporan Laba Rugi';
$active_page = 'laporan';

// Default periode: bulan ini
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Hitung total pemasukan dari penjualan
$stmt = $db->prepare("SELECT SUM(total_bayar) as total_pemasukan 
                     FROM penjualan 
                     WHERE tanggal_penjualan BETWEEN ? AND ? 
                     AND status_pembayaran = 'lunas'");
$stmt->execute([$start_date, $end_date]);
$pemasukan = $stmt->fetch(PDO::FETCH_ASSOC);

// Hitung total pengeluaran dari pembelian bahan
$stmt = $db->prepare("SELECT SUM(total_harga) as total_pengeluaran 
                     FROM pembelian_bahan 
                     WHERE tanggal_pembelian BETWEEN ? AND ? 
                     AND status_pembayaran = 'lunas'");
$stmt->execute([$start_date, $end_date]);
$pengeluaran = $stmt->fetch(PDO::FETCH_ASSOC);

// Hitung HPP (Harga Pokok Produksi)
$stmt = $db->prepare("SELECT SUM(pb.jumlah_digunakan * sb.harga_per_satuan) as hpp
                     FROM penggunaan_bahan pb
                     JOIN stok_bahan sb ON pb.id_stok = sb.id_stok
                     JOIN produksi p ON pb.id_produksi = p.id_produksi
                     WHERE p.tanggal_produksi BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$hpp = $stmt->fetch(PDO::FETCH_ASSOC);

// Hitung laba rugi
$total_pemasukan = $pemasukan['total_pemasukan'] ?? 0;
$total_pengeluaran = $pengeluaran['total_pengeluaran'] ?? 0;
$total_hpp = $hpp['hpp'] ?? 0;
$laba_kotor = $total_pemasukan - $total_hpp;
$laba_bersih = $laba_kotor - ($total_pengeluaran - $total_hpp); // Pengeluaran non-HPP

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
                                <h5 class="m-b-10">Home</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../dashboard/index.html">Home</a></li>
                                <li class="breadcrumb-item"><a href="javascript: void(0)">Dashboard</a></li>
                                <li class="breadcrumb-item" aria-current="page">Home</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->
            <div class="row">

                <!-- [ Main Content ] start -->

                <div class="card-header">
                    <h3>Laporan Laba Rugi</h3>
                    <form method="GET" class="form-inline float-right">
                        <div class="form-group mr-2">
                            <label>Dari</label>
                            <input type="date" name="start_date" class="form-control ml-2" value="<?= $start_date ?>">
                        </div>
                        <div class="form-group mr-2">
                            <label>Sampai</label>
                            <input type="date" name="end_date" class="form-control ml-2" value="<?= $end_date ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Periode Laporan</h5>
                                    <p class="card-text">
                                        <?= tgl_indo($start_date) ?> s/d <?= tgl_indo($end_date) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Keterangan</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Pemasukan dari Penjualan</strong></td>
                                    <td class="text-right"><?= rupiah($total_pemasukan) ?></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;&nbsp;&nbsp;<em>Harga Pokok Produksi (HPP)</em></td>
                                    <td class="text-right"><?= rupiah($total_hpp) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Laba Kotor</strong></td>
                                    <td class="text-right"><?= rupiah($laba_kotor) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Pengeluaran Operasional</strong></td>
                                    <td class="text-right"><?= rupiah($total_pengeluaran - $total_hpp) ?></td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>Laba Bersih</strong></td>
                                    <td class="text-right"><strong><?= rupiah($laba_bersih) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <canvas id="grafikLabaRugi" height="100"></canvas>
                    </div>
                </div>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Grafik Laba Rugi
        const ctx = document.getElementById('grafikLabaRugi').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pemasukan', 'HPP', 'Laba Kotor', 'Pengeluaran', 'Laba Bersih'],
                datasets: [{
                    label: 'Jumlah (Rp)',
                    data: [
                        <?= $total_pemasukan ?>,
                        -<?= $total_hpp ?>,
                        <?= $laba_kotor ?>,
                        -<?= $total_pengeluaran - $total_hpp ?>,
                        <?= $laba_bersih ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(255, 159, 64, 0.5)',
                        'rgba(153, 102, 255, 0.5)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: false,
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

    <?php include '../../includes/footer.php'; ?>