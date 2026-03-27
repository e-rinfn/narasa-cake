<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Laporan Penjualan';
$active_page = 'laporan';

// Default periode: bulan ini
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$id_pelanggan = $_GET['id_pelanggan'] ?? null;
$id_jenis_kue = isset($_GET['id_jenis_kue']) ? (is_array($_GET['id_jenis_kue']) ? $_GET['id_jenis_kue'] : [$_GET['id_jenis_kue']]) : [];
$id_jenis_kue = array_filter($id_jenis_kue); // Remove empty values

// Query untuk laporan penjualan dengan detail kue
$sql = "SELECT p.id_penjualan, p.tanggal_penjualan, 
               pl.nama_pelanggan,
               GROUP_CONCAT(DISTINCT jk.nama_kue ORDER BY jk.nama_kue SEPARATOR ', ') as daftar_kue,
               COUNT(DISTINCT d.id_detail_penjualan) as jumlah_item,
               SUM(d.jumlah) as total_kue,
         SUM(d.subtotal) as total_harga_item,
         MAX(p.total_bayar) as total_bayar_transaksi
        FROM penjualan p
        LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
        JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan
        JOIN jenis_kue jk ON d.id_jenis_kue = jk.id_jenis_kue
        WHERE p.tanggal_penjualan BETWEEN :start_date AND :end_date
        AND p.status_pembayaran = 'lunas'";

// Base query untuk total kue (pcs) yang akan menyesuaikan dengan filter
$sql_total_kue = "SELECT SUM(d.jumlah) as total_kue_pcs
                  FROM penjualan p
                  JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan
                  WHERE p.tanggal_penjualan BETWEEN :start_date AND :end_date
                  AND p.status_pembayaran = 'lunas'";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

// Parameters untuk total kue (sama dengan params utama)
$params_total = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

if ($id_pelanggan) {
    $sql .= " AND p.id_pelanggan = :id_pelanggan";
    $params[':id_pelanggan'] = $id_pelanggan;

    $sql_total_kue .= " AND p.id_pelanggan = :id_pelanggan";
    $params_total[':id_pelanggan'] = $id_pelanggan;
}

if (!empty($id_jenis_kue)) {
    $placeholders = [];
    foreach ($id_jenis_kue as $idx => $kue_id) {
        $key = ':id_jenis_kue_' . $idx;
        $placeholders[] = $key;
        $params[$key] = $kue_id;
        $params_total[$key] = $kue_id;
    }
    $in_clause = implode(',', $placeholders);
    $sql .= " AND d.id_jenis_kue IN ($in_clause)";
    $sql_total_kue .= " AND d.id_jenis_kue IN ($in_clause)";
}

$sql .= " GROUP BY p.id_penjualan ORDER BY p.tanggal_penjualan DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$penjualan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil detail item agar tampilan laporan bisa seperti invoice (qty x harga = subtotal)
$detail_by_penjualan = [];
if (!empty($penjualan)) {
    $params_detail = [];
    $id_placeholders = [];

    foreach ($penjualan as $index => $row) {
        $key = ':id_penjualan_' . $index;
        $id_placeholders[] = $key;
        $params_detail[$key] = $row['id_penjualan'];
    }

    $sql_detail = "SELECT d.id_penjualan, d.id_jenis_kue, d.jumlah, d.harga_satuan, d.subtotal, jk.nama_kue
                   FROM detail_penjualan d
                   JOIN jenis_kue jk ON d.id_jenis_kue = jk.id_jenis_kue
                   WHERE d.id_penjualan IN (" . implode(',', $id_placeholders) . ")";

    if (!empty($id_jenis_kue)) {
        $kue_placeholders_detail = [];
        foreach ($id_jenis_kue as $idx => $kue_id) {
            $key = ':detail_id_jenis_kue_' . $idx;
            $kue_placeholders_detail[] = $key;
            $params_detail[$key] = $kue_id;
        }
        $sql_detail .= " AND d.id_jenis_kue IN (" . implode(',', $kue_placeholders_detail) . ")";
    }

    $sql_detail .= " ORDER BY d.id_penjualan, d.id_detail_penjualan ASC";

    $stmt_detail = $db->prepare($sql_detail);
    $stmt_detail->execute($params_detail);
    $detail_rows = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

    foreach ($detail_rows as $item) {
        $detail_by_penjualan[$item['id_penjualan']][] = $item;
    }
}

// Total kue terjual (menyesuaikan dengan filter)
$stmt_total = $db->prepare($sql_total_kue);
$stmt_total->execute($params_total);
$total_kue_pcs = $stmt_total->fetch(PDO::FETCH_ASSOC)['total_kue_pcs'] ?? 0;

// Total penjualan item (dari hasil query yang sudah difilter)
$total_penjualan = array_sum(array_column($penjualan, 'total_harga_item'));


// Ambil data pelanggan untuk filter
$stmt = $db->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan");
$pelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data jenis kue untuk filter
$stmt = $db->query("SELECT * FROM jenis_kue ORDER BY nama_kue");
$jenis_kue = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

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
                                <h5 class="m-b-10">Laporan</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Laporan</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Penjualan</li>
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
                        <h3>Laporan Penjualan</h3>
                    </div>

                    <!-- Informasi Filter Aktif -->
                    <?php if ($id_pelanggan || !empty($id_jenis_kue)): ?>
                        <div class="alert alert-info m-3">
                            <i class="fas fa-filter"></i> <strong>Filter Aktif:</strong>
                            <?php if ($id_pelanggan):
                                $filter_pelanggan = array_filter($pelanggan, function ($p) use ($id_pelanggan) {
                                    return $p['id_pelanggan'] == $id_pelanggan;
                                });
                                $nama_pelanggan_filter = !empty($filter_pelanggan) ? reset($filter_pelanggan)['nama_pelanggan'] : '';
                            ?>
                                <span class="badge bg-primary">Pelanggan: <?= htmlspecialchars($nama_pelanggan_filter) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($id_jenis_kue)):
                                $filter_kue = array_filter($jenis_kue, function ($k) use ($id_jenis_kue) {
                                    return in_array($k['id_jenis_kue'], $id_jenis_kue);
                                });
                                $nama_kue_filter = implode(', ', array_column($filter_kue, 'nama_kue'));
                            ?>
                                <span class="badge bg-success">Kue: <?= htmlspecialchars($nama_kue_filter) ?></span>
                            <?php endif; ?>
                            <a href="penjualan.php" class="btn btn-sm btn-link">Hapus Filter</a>
                        </div>
                    <?php endif; ?>

                    <div class="row p-4 g-3 align-items-stretch">
                        <div class="col-md-6">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Periode Laporan</h5>
                                    <p class="card-text">
                                        <?= tgl_indo($start_date) ?> s/d <?= tgl_indo($end_date) ?>
                                    </p>
                                    <?php if ($id_pelanggan || $id_jenis_kue): ?>
                                        <hr>
                                        <small class="text-muted">
                                            <i class="fas fa-chart-line"></i> Data ditampilkan berdasarkan filter yang dipilih
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Total Penjualan</h5>
                                    <h3 class="card-text"><?= rupiah($total_penjualan) ?></h3>
                                    <hr>
                                    <h6 class="mt-2">Total Kue Terjual</h6>
                                    <h4><?= number_format($total_kue_pcs, 0, ',', '.') ?> pcs</h4>
                                    <?php if (!empty($id_jenis_kue)): ?>
                                        <small><i class="fas fa-info-circle"></i> Total kue untuk jenis yang dipilih</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="GET" class="p-4" id="filterForm">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label for="start_date" class="form-label">Dari</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $start_date ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="end_date" class="form-label">Sampai</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $end_date ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="id_pelanggan" class="form-label">Pelanggan</label>
                                <select id="id_pelanggan" name="id_pelanggan" class="form-control">
                                    <option value="">Semua Pelanggan</option>
                                    <?php foreach ($pelanggan as $p): ?>
                                        <option value="<?= $p['id_pelanggan'] ?>" <?= $id_pelanggan == $p['id_pelanggan'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nama_pelanggan']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Di bagian head, tambahkan CSS Select2 -->
                            <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
                            <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

                            <!-- Di bagian bawah sebelum footer, tambahkan JS Select2 -->
                            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
                            <script>
                                $(document).ready(function() {
                                    $('#id_jenis_kue').select2({
                                        theme: 'bootstrap-5',
                                        placeholder: 'Pilih jenis kue',
                                        allowClear: true,
                                        width: '100%'
                                    });
                                });
                            </script>

                            <div class="col-md-3 mb-2 text-end">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="penjualan.php" class="btn btn-secondary w-100"><i class="fas fa-sync-alt"></i> Reset</a>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Jenis Kue</label>
                            <div class="border rounded p-2" style="min-height: 80px; background-color: #f8f9fa;" id="kueSelector">
                                <div class="d-flex flex-wrap gap-1" id="selectedKueBadges">
                                    <?php if (!empty($id_jenis_kue)): ?>
                                        <?php foreach ($jenis_kue as $k): ?>
                                            <?php if (in_array($k['id_jenis_kue'], $id_jenis_kue)): ?>
                                                <span class="badge bg-primary" data-id="<?= $k['id_jenis_kue'] ?>">
                                                    <?= htmlspecialchars($k['nama_kue']) ?>
                                                    <i class="fas fa-times ms-1" style="cursor: pointer;"></i>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Belum ada kue dipilih</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-bs-toggle="modal" data-bs-target="#modalPilihKue">
                                <i class="fas fa-plus"></i> Pilih Kue
                            </button>

                            <!-- Input hidden untuk menyimpan nilai yang dipilih -->
                            <?php foreach ($id_jenis_kue as $index => $kue_id): ?>
                                <input type="hidden" name="id_jenis_kue[]" value="<?= $kue_id ?>" class="selected-kue-input">
                            <?php endforeach; ?>
                            <input type="hidden" name="id_jenis_kue_count" id="id_jenis_kue_count" value="<?= count($id_jenis_kue) ?>">
                        </div>

                        <!-- Modal untuk memilih kue -->
                        <div class="modal fade" id="modalPilihKue" tabindex="-1">
                            <div class="modal-dialog modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Pilih Jenis Kue</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <input type="text" id="searchKue" class="form-control" placeholder="Cari kue...">
                                        </div>
                                        <div id="kueList">
                                            <?php foreach ($jenis_kue as $k): ?>
                                                <div class="form-check mb-2 kue-item" data-nama="<?= strtolower(htmlspecialchars($k['nama_kue'])) ?>">
                                                    <input class="form-check-input kue-checkbox" type="checkbox"
                                                        value="<?= $k['id_jenis_kue'] ?>"
                                                        data-nama="<?= htmlspecialchars($k['nama_kue']) ?>"
                                                        id="modal_kue_<?= $k['id_jenis_kue'] ?>"
                                                        <?= in_array($k['id_jenis_kue'], $id_jenis_kue) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="modal_kue_<?= $k['id_jenis_kue'] ?>">
                                                        <?= htmlspecialchars($k['nama_kue']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-warning" id="btnResetPilihan">Reset</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        <button type="button" class="btn btn-primary" id="simpanPilihanKue">Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const simpanBtn = document.getElementById('simpanPilihanKue');
                                const resetBtn = document.getElementById('btnResetPilihan');
                                const selectedKueBadges = document.getElementById('selectedKueBadges');
                                const searchInput = document.getElementById('searchKue');
                                const kueItems = document.querySelectorAll('.kue-item');

                                // Fungsi untuk update hidden inputs
                                function updateHiddenInputs(selectedIds) {
                                    // Hapus semua input hidden yang ada
                                    const existingInputs = document.querySelectorAll('.selected-kue-input');
                                    existingInputs.forEach(input => input.remove());

                                    // Tambahkan input hidden baru untuk setiap ID yang dipilih
                                    selectedIds.forEach(id => {
                                        const hiddenInput = document.createElement('input');
                                        hiddenInput.type = 'hidden';
                                        hiddenInput.name = 'id_jenis_kue[]';
                                        hiddenInput.value = id;
                                        hiddenInput.className = 'selected-kue-input';
                                        document.getElementById('kueSelector').appendChild(hiddenInput);
                                    });

                                    // Update counter
                                    document.getElementById('id_jenis_kue_count').value = selectedIds.length;
                                }

                                // Fungsi untuk update badges
                                function updateBadges(selectedIds, selectedNames) {
                                    selectedKueBadges.innerHTML = '';
                                    if (selectedIds.length > 0) {
                                        selectedIds.forEach((id, index) => {
                                            const badge = document.createElement('span');
                                            badge.className = 'badge bg-primary me-1 mb-1';
                                            badge.setAttribute('data-id', id);
                                            badge.innerHTML = `${selectedNames[index]} <i class="fas fa-times ms-1" style="cursor: pointer;"></i>`;
                                            selectedKueBadges.appendChild(badge);

                                            // Add click event to remove badge
                                            const removeIcon = badge.querySelector('.fa-times');
                                            removeIcon.addEventListener('click', function(e) {
                                                e.stopPropagation();
                                                removeKue(id);
                                            });
                                        });
                                    } else {
                                        selectedKueBadges.innerHTML = '<span class="text-muted">Belum ada kue dipilih</span>';
                                    }
                                }

                                // Fungsi untuk mendapatkan nilai yang dipilih dari checkbox
                                function getSelectedFromModal() {
                                    const checkboxes = document.querySelectorAll('.kue-checkbox:checked');
                                    const selectedIds = [];
                                    const selectedNames = [];

                                    checkboxes.forEach(cb => {
                                        selectedIds.push(cb.value);
                                        selectedNames.push(cb.dataset.nama);
                                    });

                                    return {
                                        selectedIds,
                                        selectedNames
                                    };
                                }

                                // Fungsi untuk remove kue
                                window.removeKue = function(kueId) {
                                    // Uncheck checkbox di modal
                                    const checkbox = document.querySelector(`.kue-checkbox[value="${kueId}"]`);
                                    if (checkbox) checkbox.checked = false;

                                    // Get current selected
                                    const {
                                        selectedIds,
                                        selectedNames
                                    } = getSelectedFromModal();

                                    // Update badges
                                    updateBadges(selectedIds, selectedNames);

                                    // Update hidden inputs
                                    updateHiddenInputs(selectedIds);
                                }

                                // Event simpan
                                simpanBtn.addEventListener('click', function() {
                                    const {
                                        selectedIds,
                                        selectedNames
                                    } = getSelectedFromModal();

                                    // Update badges
                                    updateBadges(selectedIds, selectedNames);

                                    // Update hidden inputs
                                    updateHiddenInputs(selectedIds);

                                    // Tutup modal
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalPilihKue'));
                                    if (modal) {
                                        modal.hide();
                                    } else {
                                        // Fallback jika modal tidak ditemukan
                                        document.getElementById('modalPilihKue').style.display = 'none';
                                        document.querySelector('.modal-backdrop').remove();
                                    }
                                });

                                // Event reset
                                resetBtn.addEventListener('click', function() {
                                    // Uncheck all checkboxes
                                    const checkboxes = document.querySelectorAll('.kue-checkbox');
                                    checkboxes.forEach(cb => cb.checked = false);

                                    // Update badges
                                    updateBadges([], []);

                                    // Update hidden inputs
                                    updateHiddenInputs([]);

                                    // Tutup modal
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalPilihKue'));
                                    if (modal) {
                                        modal.hide();
                                    }
                                });

                                // Fitur pencarian
                                if (searchInput) {
                                    searchInput.addEventListener('keyup', function() {
                                        const searchTerm = this.value.toLowerCase();
                                        kueItems.forEach(item => {
                                            const namaKue = item.dataset.nama;
                                            if (namaKue.includes(searchTerm)) {
                                                item.style.display = '';
                                            } else {
                                                item.style.display = 'none';
                                            }
                                        });
                                    });
                                }

                                // Inisialisasi badge click events untuk remove
                                document.querySelectorAll('#selectedKueBadges .badge').forEach(badge => {
                                    const removeIcon = badge.querySelector('.fa-times');
                                    if (removeIcon) {
                                        const kueId = badge.dataset.id;
                                        removeIcon.addEventListener('click', function(e) {
                                            e.stopPropagation();
                                            window.removeKue(kueId);
                                        });
                                    }
                                });
                            });
                        </script>

                        <style>
                            #selectedKueBadges .badge {
                                font-size: 0.85rem;
                                padding: 5px 10px;
                            }

                            #selectedKueBadges .badge .fa-times {
                                font-size: 0.75rem;
                                margin-left: 5px;
                            }

                            #selectedKueBadges .badge .fa-times:hover {
                                opacity: 0.7;
                            }

                            .kue-item {
                                transition: background-color 0.2s;
                            }

                            .kue-item:hover {
                                background-color: #f8f9fa;
                            }
                        </style>
                    </form>
                    <div class="card-body">

                        <?php if (empty($penjualan)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> Tidak ada data penjualan untuk periode yang dipilih.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-dark">
                                        <tr class="text-center">
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Pelanggan</th>
                                            <th>Detail Kue (Invoice)</th>
                                            <th>Total Kue</th>
                                            <th>Total Harga Item</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($penjualan as $i => $row): ?>
                                            <tr>
                                                <td class="text-center"><?= $i + 1 ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_penjualan'])) ?></td>
                                                <td><?= htmlspecialchars($row['nama_pelanggan'] ?: 'Umum') ?></td>
                                                <td>
                                                    <?php if (!empty($detail_by_penjualan[$row['id_penjualan']])): ?>
                                                        <?php foreach ($detail_by_penjualan[$row['id_penjualan']] as $detail_item): ?>
                                                            <div>
                                                                <strong><?= htmlspecialchars($detail_item['nama_kue']) ?></strong><br>
                                                                <small class="text-muted">
                                                                    <?= number_format($detail_item['jumlah'], 0, ',', '.') ?> x <?= rupiah($detail_item['harga_satuan']) ?>
                                                                    = <?= rupiah($detail_item['subtotal']) ?>
                                                                </small>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Tidak ada detail</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?= number_format($row['total_kue'], 0, ',', '.') ?></td>
                                                <td class="text-end"><?= rupiah($row['total_harga_item']) ?></td>
                                                <td class="text-center">
                                                    <a href="../penjualan/invoice.php?id=<?= $row['id_penjualan'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="fas fa-file-invoice"></i> Invoice
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <td colspan="4" class="text-end fw-bold">Total Keseluruhan:</td>
                                            <td class="text-center fw-bold"><?= number_format($total_kue_pcs, 0, ',', '.') ?> pcs</td>
                                            <td class="text-end fw-bold"><?= rupiah($total_penjualan) ?></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <a href="cetak_penjualan.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-success">
                                <i class="fas fa-print"></i> Cetak Laporan
                            </a>
                            <button onclick="window.print()" class="btn btn-secondary">
                                <i class="fas fa-print"></i> Print Halaman
                            </button>
                        </div>
                    </div>

                    <!-- [ Main Content ] end -->

                </div>
            </div>
        </div>
    </div>

    <!-- jQuery dan Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2 untuk multi-select tanpa perlu Ctrl+Click
            $('#id_jenis_kue').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih jenis kue (klik untuk memilih)',
                allowClear: true,
                width: '100%',
                closeOnSelect: false, // Biarkan dropdown tetap terbuka setelah memilih
                language: {
                    noResults: function() {
                        return 'Tidak ada kue ditemukan';
                    },
                    searching: function() {
                        return 'Mencari...';
                    }
                }
            });

            // Optional: Tambahkan tombol untuk memilih semua
            // Uncomment jika ingin menambahkan fitur pilih semua
            /*
            $('<button type="button" class="btn btn-sm btn-outline-primary mt-1" id="selectAllKue">Pilih Semua</button>')
                .insertAfter('#id_jenis_kue');
            
            $('#selectAllKue').on('click', function() {
                $('#id_jenis_kue option').prop('selected', true);
                $('#id_jenis_kue').trigger('change');
            });
            */
        });
    </script>

    <?php include '../../includes/footer.php'; ?>