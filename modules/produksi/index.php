<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Daftar Produksi Kue';
$active_page = 'produksi';

// Konfigurasi paginasi
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$per_page = in_array($per_page, [5, 10, 25, 50, 100]) ? $per_page : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);

// Hitung offset
$offset = ($page - 1) * $per_page;

// Hitung total data produksi
$count_query = "SELECT COUNT(*) as total FROM produksi";
$stmt = $db->query($count_query);
$total_data = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_data / $per_page);

// Validasi halaman
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Query utama produksi dengan LIMIT
$query = "SELECT p.*, r.nama_resep, k.nama_kue 
          FROM produksi p
          JOIN resep_kue r ON p.id_resep = r.id_resep
          JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
          ORDER BY p.tanggal_produksi DESC
          LIMIT :offset, :per_page";

// Filter berdasarkan jenis kue dan tanggal produksi
$where = [];
$params = [];

if (isset($_GET['jenis_kue']) && $_GET['jenis_kue'] != '') {
    $where[] = 'k.id_jenis_kue = :jenis_kue';
    $params[':jenis_kue'] = $_GET['jenis_kue'];
}

if (isset($_GET['tanggal_produksi']) && $_GET['tanggal_produksi'] != '') {
    $where[] = 'p.tanggal_produksi = :tanggal_produksi';
    $params[':tanggal_produksi'] = $_GET['tanggal_produksi'];
}

// Gabungkan kondisi WHERE jika ada filter
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Query utama dengan filter
$query = "
    SELECT p.*, r.nama_resep, k.nama_kue 
    FROM produksi p
    JOIN resep_kue r ON p.id_resep = r.id_resep
    JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
    $where_sql
    ORDER BY p.tanggal_produksi DESC
    LIMIT :offset, :per_page
";

$stmt = $db->prepare($query);

// Binding parameters filter
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();

$produksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title><?= $page_title ?></title>
    <!-- Tambahkan CSS untuk modal -->
    <style>
        .modal-edit {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content-edit {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            border-radius: 5px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
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

            <div class="row">
                <!-- [ Main Content ] start -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Daftar Produksi</h3>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Produksi Baru
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <?php displayMessage(); ?>
                        <!-- Filter dan Paginasi Atas -->
                        <div class="row mb-3">
                            <form method="get" class="w-100">
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <div class="input-group">
                                            <span class="input-group-text">Tampilkan</span>
                                            <select name="per_page" class="form-select per-page-selector" onchange="this.form.submit()">
                                                <option value="5" <?= $per_page == 5 ? 'selected' : '' ?>>5</option>
                                                <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                                                <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                                                <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                                                <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                                            </select>
                                            <span class="input-group-text">data produksi</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <!-- Filter Jenis Kue -->
                                        <div class="input-group">
                                            <span class="input-group-text">Jenis Kue</span>
                                            <select name="jenis_kue" class="form-select" onchange="this.form.submit()">
                                                <option value="">Semua Jenis Kue</option>
                                                <?php
                                                // Ambil semua jenis kue
                                                $jenis_kue_query = "SELECT * FROM jenis_kue";
                                                $stmt_jenis_kue = $db->query($jenis_kue_query);
                                                $jenis_kue_list = $stmt_jenis_kue->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($jenis_kue_list as $jk) {
                                                    echo '<option value="' . $jk['id_jenis_kue'] . '" ' . (isset($_GET['jenis_kue']) && $_GET['jenis_kue'] == $jk['id_jenis_kue'] ? 'selected' : '') . '>' . $jk['nama_kue'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <!-- Filter Tanggal Produksi -->
                                        <div class="input-group">
                                            <span class="input-group-text">Tanggal Produksi</span>
                                            <input type="date" name="tanggal_produksi" class="form-control" value="<?= isset($_GET['tanggal_produksi']) ? $_GET['tanggal_produksi'] : '' ?>" onchange="this.form.submit()">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Tabel -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-light">
                                    <tr class="text-center">
                                        <th>No</th>
                                        <th>Tanggal Produksi</th>
                                        <th>ID Produksi</th>
                                        <th>Jenis Kue</th>
                                        <!-- <th>Resep</th> -->
                                        <th>Jumlah Batch</th>
                                        <th>Produksi Kue</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produksi as $i => $row): ?>
                                        <tr>
                                            <td class="text-center"><?= $i + 1 ?></td>
                                            <td><?= tgl_indo($row['tanggal_produksi']) ?></td>
                                            <td><?= $row['id_produksi'] ?></td>
                                            <td><?= $row['nama_kue'] ?></td>
                                            <!-- <td><?= $row['nama_resep'] ?></td> -->
                                            <td class="text-center"><?= $row['jumlah_batch'] ?></td>
                                            <td class="text-center">
                                                <span id="total-kue-<?= $row['id_produksi'] ?>" class="fw-semibold">
                                                    <?= $row['total_kue'] . ' pcs' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-warning m-1"
                                                    onclick="showEditModal('<?= $row['id_produksi'] ?>', '<?= $row['total_kue'] ?>')">
                                                    <i class="fas fa-edit"></i> Edit Produksi Kue
                                                </button>
                                                <a href="detail.php?id=<?= $row['id_produksi'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Lihat Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>
                        <!-- Paginasi Bawah -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                            <div class="pagination-info text-muted">
                                Menampilkan <?= ($offset + 1) ?> sampai <?= min($offset + $per_page, $total_data) ?> dari <?= $total_data ?> data produksi
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm m-0">
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

                <!-- Modal Edit Total Kue -->
                <div id="editModal" class="modal-edit">
                    <div class="modal-content-edit">
                        <span class="close" onclick="closeEditModal()">&times;</span>
                        <h4>Edit Total Kue</h4>
                        <form id="editForm" method="post" action="update_total_kue.php">
                            <input type="hidden" id="edit-id-produksi" name="id_produksi">
                            <div class="mb-3">
                                <label for="edit-total-kue" class="form-label">Total Kue</label>
                                <input type="number" class="form-control" id="edit-total-kue" name="total_kue" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- [ Main Content ] end -->
        </div>
    </div>


    <?php include '../../includes/footer.php'; ?>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <!-- JavaScript untuk modal edit -->
    <script>
        // Fungsi untuk menampilkan modal edit
        function showEditModal(idProduksi, totalKue) {
            document.getElementById('edit-id-produksi').value = idProduksi;
            document.getElementById('edit-total-kue').value = totalKue;
            document.getElementById('editModal').style.display = 'block';
        }

        // Fungsi untuk menutup modal edit
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Submit form edit menggunakan fetch dan SweetAlert2
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const idProduksi = formData.get('id_produksi');
                        const totalKueBaru = formData.get('total_kue');

                        document.getElementById(`total-kue-${idProduksi}`).textContent = totalKueBaru;
                        closeEditModal();

                        // SweetAlert sukses
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Total kue berhasil diperbarui.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        // SweetAlert gagal
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Gagal memperbarui total kue: ' + data.message,
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan!',
                        text: 'Terjadi kesalahan saat memperbarui total kue.',
                    });
                });
        });

        // Tutup modal jika klik di luar area modal
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        };
    </script>

</body>

</html>