<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Manajemen Jenis Kue';
$active_page = 'kue';

// Konfigurasi paginasi
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$per_page = in_array($per_page, [5, 10, 25, 50, 100]) ? $per_page : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Pastikan tidak kurang dari 1

// Hitung offset
$offset = ($page - 1) * $per_page;

// Query untuk menghitung total data
$count_query = "SELECT COUNT(*) as total FROM jenis_kue";
$stmt = $db->query($count_query);
$total_data = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_data / $per_page);

// Validasi halaman
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Query utama dengan LIMIT
$query = "SELECT * FROM jenis_kue ORDER BY nama_kue LIMIT :offset, :per_page";
$stmt = $db->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$jenis_kue = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
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

    <?php include '../../includes/sidebar.php'; ?>
    <?php include '../../includes/navbar.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">

            <?php displayMessage(); ?>

            <div class="row">
                <!-- [ Main Content ] start -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Daftar Jenis Kue</h3>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Jenis Kue
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Filter dan Paginasi Atas -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <form method="get" class="form-inline">
                                    <div class="input-group">
                                        <span class="input-group-text">Tampilkan</span>
                                        <select name="per_page" class="form-select per-page-selector" onchange="this.form.submit()">
                                            <option value="5" <?= $per_page == 5 ? 'selected' : '' ?>>5</option>
                                            <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                                            <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                                            <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                                            <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                                        </select>
                                        <span class="input-group-text">data jenis kue</span>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kue</th>
                                        <th>± Harga Jual</th>
                                        <!-- <th>Deskripsi</th> -->
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($jenis_kue)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada data jenis kue</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($jenis_kue as $i => $kue): ?>
                                            <tr>
                                                <td><?= $offset + $i + 1 ?></td>
                                                <td><?= htmlspecialchars($kue['nama_kue']) ?></td>
                                                <td><?= rupiah($kue['harga_jual']) ?></td>
                                                <!-- <td><?= htmlspecialchars($kue['deskripsi']) ?></td> -->
                                                <td class="text-center">
                                                    <span class="badge bg-<?= $kue['aktif'] ? 'success' : 'danger' ?>">
                                                        <?= $kue['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="edit.php?id=<?= $kue['id_jenis_kue'] ?>" class="btn btn-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete.php?id=<?= $kue['id_jenis_kue'] ?>"
                                                            class="btn btn-danger btn-delete-kue"
                                                            data-id="<?= $kue['id_jenis_kue'] ?>"
                                                            title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </a>

                                                        <a href="stok.php?id=<?= $kue['id_jenis_kue'] ?>" class="btn btn-info" title="Kelola Stok">
                                                            <i class="fas fa-boxes"> | Lihat Stok</i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginasi Bawah -->
                        <div class="pagination-container">
                            <div class="pagination-info">
                                Menampilkan <?= ($offset + 1) ?> sampai <?= min($offset + $per_page, $total_data) ?> dari <?= $total_data ?> entri
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm">
                                    <!-- First Page -->
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=1&per_page=<?= $per_page ?>">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>

                                    <!-- Previous Page -->
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&per_page=<?= $per_page ?>">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    </li>

                                    <!-- Page Numbers -->
                                    <?php
                                    $visible_pages = 5;
                                    $half = floor($visible_pages / 2);
                                    $start_page = max(1, $page - $half);
                                    $end_page = min($total_pages, $start_page + $visible_pages - 1);

                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1&per_page=' . $per_page . '">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $active = $i == $page ? 'active' : '';
                                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '&per_page=' . $per_page . '">' . $i . '</a></li>';
                                    }

                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&per_page=' . $per_page . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>

                                    <!-- Next Page -->
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&per_page=<?= $per_page ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>

                                    <!-- Last Page -->
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $total_pages ?>&per_page=<?= $per_page ?>">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
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
            const deleteKueButtons = document.querySelectorAll('.btn-delete-kue');

            deleteKueButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');

                    Swal.fire({
                        title: 'Yakin ingin menghapus?',
                        text: "Jenis kue ini akan dihapus permanen!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = href;
                        }
                    });
                });
            });
        });
    </script>


    <?php include '../../includes/footer.php'; ?>
</body>

</html>