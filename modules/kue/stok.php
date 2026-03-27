<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$id_jenis_kue = $_GET['id'] ?? 0;
$page_title = 'Manajemen Stok Kue';
$active_page = 'kue';

// Ambil data jenis kue dengan validasi
$stmt = $db->prepare("SELECT * FROM jenis_kue WHERE id_jenis_kue = ?");
$stmt->execute([$id_jenis_kue]);
$jenis_kue = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika jenis kue tidak ditemukan, tampilkan error dan hentikan eksekusi
if (!$jenis_kue) {
    redirectWithMessage('../index.php', 'danger', 'Jenis kue tidak ditemukan');
}

// Ambil data stok
$stmt = $db->prepare("SELECT * FROM stok_kue 
                     WHERE id_jenis_kue = ? AND jumlah > 0
                     ORDER BY tanggal_kadaluarsa ASC");
$stmt->execute([$id_jenis_kue]);
$stok = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total stok
$total_stok = 0;
foreach ($stok as $item) {
    $total_stok += $item['jumlah'];
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
                            <h3>Informasi Stok Kue</h3>
                        </div>
                        <div class="card-body mt-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header text-center">
                                            <h4>Riwayat Produksi: <?= htmlspecialchars($jenis_kue['nama_kue']) ?></h4>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($stok)): ?>
                                                <div class="alert alert-info">Tidak ada stok tersedia untuk kue ini</div>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead class="text-center">
                                                            <tr>
                                                                <th>No</th>
                                                                <th>Stok ID</th>
                                                                <th>Jumlah</th>
                                                                <th>Tanggal Produksi</th>
                                                                <th>Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($stok as $i => $row):
                                                                // $sisa_hari = (strtotime($row['tanggal_kadaluarsa']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                                                            ?>
                                                                <tr class="<?= $sisa_hari <= 3 ? 'table-warning' : '' ?>">
                                                                    <td><?= $i + 1 ?></td>
                                                                    <td>ID - <?= $row['id_stok_kue'] ?></td>
                                                                    <td><?= $row['jumlah'] ?></td>
                                                                    <td><?= tgl_indo($row['tanggal_produksi']) ?></td>
                                                                    <td class="text-center">
                                                                        <button class="btn btn-sm btn-warning edit-stok-btn"
                                                                            data-id="<?= $row['id_stok_kue'] ?>"
                                                                            title="Edit"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editStokModal">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-danger btn-batalkan-produksi"
                                                                            data-id="<?= $row['id_stok_kue'] ?>"
                                                                            data-jumlah="<?= $row['jumlah'] ?>"
                                                                            title="Batalkan Produksi">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light shadow-sm">
                                        <div class="card-body text-center">
                                            <h5 class="text-muted mb-2 mt-3">
                                                Stok <span class="fw-bold text-dark"><?= htmlspecialchars($jenis_kue['nama_kue']) ?></span> Saat Ini
                                            </h5>
                                            <h2 class="text-center">± <?= $total_stok ?></h2>
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


    <!-- Modal Edit Stok -->
    <div class="modal fade" id="editStokModal" tabindex="-1" aria-labelledby="editStokModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStokModalLabel">Edit Stok Kue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editStokModalBody">
                    <!-- Konten akan diisi via AJAX -->
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="saveStokChanges">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Ketika tombol edit diklik
            $('.edit-stok-btn').click(function() {
                const idStok = $(this).data('id');

                // Load konten modal via AJAX
                $('#editStokModalBody').load('edit_stok_modal.php?id=' + idStok);
            });

            // Reset modal ketika ditutup
            $('#editStokModal').on('hidden.bs.modal', function() {
                $('#editStokModalBody').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);
            });

            // Pembatalan produksi
            $('.btn-batalkan-produksi').click(function() {
                const idStok = $(this).data('id');
                const jumlah = $(this).data('jumlah');

                Swal.fire({
                    title: 'Batalkan Produksi?',
                    text: 'Anda akan membatalkan produksi sebanyak ' + jumlah + ' kue. Data akan dihapus permanen!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Tidak',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'hapus_stok.php?id=' + idStok + '&id_jenis_kue=<?= $id_jenis_kue ?>';
                    }
                });
            });
        });
    </script>
    <?php include '../../includes/footer.php'; ?>