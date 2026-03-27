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
                   HAVING COALESCE(SUM(s.jumlah), 0) <= b.stok_minimal
                    OR COALESCE(SUM(s.jumlah), 0) = 0
                    OR SUM(s.jumlah) IS NULL");
$bahan_habis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung kue yang hampir kadaluarsa (3 hari lagi)
$stmt = $db->prepare("SELECT k.nama_kue, s.jumlah, s.tanggal_kadaluarsa 
                     FROM stok_kue s
                     JOIN jenis_kue k ON s.id_jenis_kue = k.id_jenis_kue
                     WHERE s.tanggal_kadaluarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                     AND s.jumlah > 0");
$stmt->execute();
$kue_kadaluarsa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data untuk grafik penjualan 12 bulan berdasarkan tahun yang dipilih
$selected_year = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$current_year = date('Y');

// Ambil tahun-tahun yang tersedia untuk dropdown
$stmt = $db->query("SELECT DISTINCT YEAR(tanggal_penjualan) as tahun 
                   FROM penjualan 
                   WHERE status_pembayaran = 'lunas'
                   ORDER BY tahun DESC");
$available_years = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jika tahun yang dipilih tidak ada data, gunakan tahun terbaru yang tersedia
if (empty($available_years)) {
    $available_years = [['tahun' => $current_year]];
    $selected_year = $current_year;
} else if (!in_array($selected_year, array_column($available_years, 'tahun'))) {
    $selected_year = $available_years[0]['tahun'];
}

// Query untuk grafik penjualan 12 bulan
$stmt = $db->prepare("SELECT 
    DATE_FORMAT(tanggal_penjualan, '%Y-%m') as bulan,
    DATE_FORMAT(tanggal_penjualan, '%M') as nama_bulan,
    COALESCE(SUM(total_bayar), 0) as total
    FROM penjualan
    WHERE YEAR(tanggal_penjualan) = :tahun
    AND status_pembayaran = 'lunas'
    GROUP BY DATE_FORMAT(tanggal_penjualan, '%Y-%m'), DATE_FORMAT(tanggal_penjualan, '%M')
    ORDER BY bulan");

$stmt->execute([':tahun' => $selected_year]);
$grafik_penjualan_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buat array untuk 12 bulan penuh
$bulan_labels = [];
$bulan_data = [];
$all_months = [];

// Generate semua bulan dalam setahun
for ($i = 1; $i <= 12; $i++) {
    $month_number = str_pad($i, 2, '0', STR_PAD_LEFT);
    $bulan_labels[] = date('M', strtotime("$selected_year-$month_number-01"));
    $all_months[$selected_year . '-' . $month_number] = 0;
}

// Isi data dari database
foreach ($grafik_penjualan_raw as $data) {
    $all_months[$data['bulan']] = (float)$data['total'];
}

// Konversi ke format yang dibutuhkan Chart.js
$bulan_keys = array_keys($all_months);
$bulan_values = array_values($all_months);

// Hitung statistik tambahan
$stmt = $db->prepare("SELECT 
    COUNT(DISTINCT id_penjualan) as total_transaksi,
    AVG(total_bayar) as rata_rata_transaksi
    FROM penjualan
    WHERE YEAR(tanggal_penjualan) = :tahun
    AND status_pembayaran = 'lunas'");
$stmt->execute([':tahun' => $selected_year]);
$statistik_tahunan = $stmt->fetch(PDO::FETCH_ASSOC);

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
            <div class="row">
                <!-- Statistik Cards -->
                <div class="col-md-6 col-lg-3">
                    <div class="card bg-primary text-white mb-3">
                        <div class="card-body p-4">
                            <h6 class="card-title mb-3">Penjualan Bulan Ini</h6>
                            <div class="h5 card-text"><?= rupiah($total_penjualan['total'] ?? 0) ?></div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light shadow">
                            <a href="../penjualan/" class="text-dark stretched-link small">Lihat Detail <i class="ti ti-arrow-right small"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card bg-success text-white mb-3">
                        <div class="card-body p-4">
                            <h6 class="card-title mb-3">Produksi Bulan Ini</h6>
                            <div class="h5 card-text"><?= number_format($total_produksi['total'] ?? 0, 0, ',', '.') ?> Kue</div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light shadow">
                            <a href="../produksi/" class="text-dark stretched-link small">Lihat Detail <i class="ti ti-arrow-right small"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card bg-danger text-white mb-3">
                        <div class="card-body p-4">
                            <h6 class="card-title mb-3">Bahan Hampir Habis</h6>
                            <div class="h5 card-text"><?= count($bahan_habis) ?> Jenis</div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light shadow">
                            <a href="../bahan/" class="text-dark stretched-link small">Lihat Detail <i class="ti ti-arrow-right text-dark small"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card bg-info text-white mb-3">
                        <div class="card-body p-4">
                            <h6 class="card-title mb-3">Statistik <?= $selected_year ?></h6>
                            <div class="h6 card-text">
                                <?= number_format($statistik_tahunan['total_transaksi'] ?? 0) ?> Transaksi<br>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light shadow">
                            <a href="../penjualan/" class="text-dark stretched-link small">Detail Penjualan <i class="ti ti-arrow-right small"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                            <h5 class="text-white mb-0">Grafik Penjualan Tahun <?= $selected_year ?></h5>
                            <form method="GET" class="d-inline">
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <?php foreach ($available_years as $year_data): ?>
                                            <option value="<?= $year_data['tahun'] ?>"
                                                <?= $year_data['tahun'] == $selected_year ? 'selected' : '' ?>>
                                                <?= $year_data['tahun'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <canvas id="chartPenjualan" height="100"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-danger shadow-sm">
                            <h5 class="text-white mb-0">Tabel Bahan Hampir Habis</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Bahan</th>
                                            <th>Stok</th>
                                            <th>Minimal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($bahan_habis) > 0): ?>
                                            <?php foreach ($bahan_habis as $index => $bahan): ?>
                                                <tr class="<?= ($bahan['stok_aktual'] == 0) ? 'table-danger' : 'table-warning' ?>">
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($bahan['nama_bahan']) ?></td>
                                                    <td><?= number_format($bahan['stok_aktual'] ?? 0) ?></td>
                                                    <td><?= number_format($bahan['stok_minimal']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-success">
                                                    <i class="ti ti-check ti-lg me-2"></i>
                                                    Semua bahan stoknya aman
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
                                        return "'" . $item . "'";
                                    }, $bulan_labels)) ?>],
                        datasets: [{
                            label: 'Total Penjualan (Rp)',
                            data: [<?= implode(',', $bulan_values) ?>],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 159, 64, 0.7)',
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 159, 64, 0.7)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    drawBorder: false
                                },
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt';
                                        } else if (value >= 1000) {
                                            return 'Rp ' + (value / 1000).toFixed(0) + 'Rb';
                                        }
                                        return 'Rp ' + value;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(255, 193, 7, 0.5)',
                                borderWidth: 1,
                                padding: 10,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label = 'Penjualan: ';
                                        }
                                        label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                        return label;
                                    }
                                }
                            },
                            legend: {
                                labels: {
                                    font: {
                                        size: 12
                                    },
                                    padding: 20
                                }
                            }
                        }
                    }
                });
            </script>

            <style>
                .card-header .input-group {
                    min-width: 120px;
                }

                .card-header .form-select {
                    border-radius: 4px;
                    border: 1px solid #dee2e6;
                    background-color: white;
                }

                .card-header .form-select:focus {
                    box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
                    border-color: #ffc107;
                }

                #chartPenjualan {
                    min-height: 300px;
                }

                .table th {
                    font-weight: 600;
                    border-bottom: 2px solid #dee2e6;
                }

                .table td {
                    vertical-align: middle;
                }
            </style>

        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>