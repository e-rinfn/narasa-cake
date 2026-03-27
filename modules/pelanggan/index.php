<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Manajemen Pelanggan';
$active_page = 'pelanggan';

// Ambil data pelanggan
$stmt = $db->query("SELECT p.*, k.nama_kategori 
                   FROM pelanggan p
                   LEFT JOIN kategori_pelanggan k ON p.id_kategori_pelanggan = k.id_kategori_pelanggan
                   ORDER BY p.nama_pelanggan");
$pelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Daftar Pelanggan</h3>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Pelanggan Baru
                            </a>
                        </div>
                    </div>


                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr class="text-center">
                                    <th>No</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Kategori</th>
                                    <th>Telepon</th>
                                    <!-- <th>Email</th> -->
                                    <th>Total Nabung</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pelanggan as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                                        <td><?= $row['nama_kategori'] ?? 'Umum' ?></td>
                                        <td><?= $row['no_telepon'] ?></td>
                                        <!-- <td><?= $row['email'] ?></td> -->
                                        <td><?= rupiah($row['total_poin']) ?></td>
                                        <!-- <td class="text-center">
                                            <a href="edit.php?id=<?= $row['id_pelanggan'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?= $row['id_pelanggan'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus pelanggan ini?')"><i class="fas fa-trash"></i></a>
                                        </td> -->
                                        <!-- Tambahan di dalam kolom Aksi -->
                                        <td class="text-center">
                                            <div class="d-flex flex-wrap justify-content-center gap-1">
                                                <a href="edit.php?id=<?= $row['id_pelanggan'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="delete.php?id=<?= $row['id_pelanggan'] ?>"
                                                    class="btn btn-danger btn-sm btn-delete-pelanggan"
                                                    data-id="<?= $row['id_pelanggan'] ?>">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>

                                                <a href="edit_poin.php?id=<?= $row['id_pelanggan'] ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-coins"></i> Edit Nabung
                                                </a>
                                                <a href="reset_poin.php?id=<?= $row['id_pelanggan'] ?>"
                                                    class="btn btn-secondary btn-sm btn-reset-poin"
                                                    data-id="<?= $row['id_pelanggan'] ?>">
                                                    <i class="fas fa-sync"></i> Reset Poin
                                                </a>

                                            </div>
                                        </td>



                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const deleteButtons = document.querySelectorAll(".btn-delete-pelanggan");

            deleteButtons.forEach(button => {
                button.addEventListener("click", function(e) {
                    e.preventDefault();
                    const href = this.getAttribute("href");

                    Swal.fire({
                        title: "Yakin ingin menghapus?",
                        text: "Data pelanggan akan dihapus permanen.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "Ya, hapus!",
                        cancelButtonText: "Batal"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = href;
                        }
                    });
                });
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const resetButtons = document.querySelectorAll(".btn-reset-poin");

            resetButtons.forEach(button => {
                button.addEventListener("click", function(e) {
                    e.preventDefault();
                    const href = this.getAttribute("href");

                    Swal.fire({
                        title: "Reset Poin?",
                        text: "Poin pelanggan akan direset menjadi 0!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "Ya, reset!",
                        cancelButtonText: "Batal"
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