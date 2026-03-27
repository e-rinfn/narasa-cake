<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Manajemen Bahan Baku';
$active_page = 'bahan';

// Konfigurasi paginasi
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$per_page = in_array($per_page, [5, 10, 25, 50, 100]) ? $per_page : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Pastikan tidak kurang dari 1

// Hitung offset
$offset = ($page - 1) * $per_page;

// Query untuk menghitung total data
$count_query = "SELECT COUNT(*) as total FROM bahan_baku b
                JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                JOIN satuan_bahan s ON b.id_satuan = s.id_satuan";
$stmt = $db->query($count_query);
$total_data = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_data / $per_page);

// Validasi halaman
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Query utama dengan LIMIT
$query = "SELECT b.*, k.nama_kategori, s.nama_satuan 
          FROM bahan_baku b
          JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
          JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
          ORDER BY b.nama_bahan
          LIMIT :offset, :per_page";

$stmt = $db->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                            <h3 class="mb-0">Daftar Bahan Baku</h3>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Jenis Bahan Baku
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
                                        <span class="input-group-text">data bahan baku</span>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Tabel -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Bahan</th>
                                        <th>Kategori</th>
                                        <th>Stok Sekarang</th>
                                        <th>Stok Minimal</th>
                                        <th>Satuan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bahan)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data bahan baku</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bahan as $i => $row):
                                            $stok_sekarang = getStokBahan($db, $row['id_bahan']);
                                            $status_stok = $stok_sekarang < $row['stok_minimal'] ? 'danger' : 'success';
                                        ?>
                                            <tr>
                                                <td><?= $offset + $i + 1 ?></td>
                                                <td><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                                <td class="text-end">
                                                    <span class="badge bg-<?= $status_stok ?>">
                                                        <?= $stok_sekarang ?> <?= htmlspecialchars($row['nama_satuan']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($row['stok_minimal']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_satuan']) ?></td>
                                                <td class="text-center">
                                                    <?php if ($row['aktif']): ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Nonaktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="edit.php?id=<?= $row['id_bahan'] ?>" class="btn btn-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete.php?id=<?= $row['id_bahan'] ?>"
                                                            class="btn btn-danger btn-delete"
                                                            data-id="<?= $row['id_bahan'] ?>"
                                                            title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </a>

                                                        <a href="stok.php?id=<?= $row['id_bahan'] ?>" class="btn btn-info" title="Kelola Stok">
                                                            <i class="fas fa-boxes"> | Kelola Stok</i>
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
                                Menampilkan <?= ($offset + 1) ?> sampai <?= min($offset + $per_page, $total_data) ?> dari <?= $total_data ?> bahan baku
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
            const deleteButtons = document.querySelectorAll('.btn-delete');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');

                    Swal.fire({
                        title: 'Yakin ingin menghapus?',
                        text: "Data ini tidak bisa dikembalikan!",
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