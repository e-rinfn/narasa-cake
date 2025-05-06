<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = isset($_GET['edit']) ? 'Edit Pembelian' : 'Tambah Pembelian';
$active_page = 'pembelian';

// Ambil data bahan baku
$stmt = $db->query("SELECT b.id_bahan, b.nama_bahan, k.nama_kategori, s.nama_satuan 
                   FROM bahan_baku b
                   JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                   JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                   WHERE b.aktif = 1
                   ORDER BY b.nama_bahan");
$bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mode edit
$is_edit = false;
$pembelian = null;
$detail_pembelian = [];

if (isset($_GET['edit'])) {
    $is_edit = true;
    $id_pembelian = $_GET['edit'];

    // Ambil data pembelian
    $stmt = $db->prepare("SELECT * FROM pembelian_bahan WHERE id_pembelian = ?");
    $stmt->execute([$id_pembelian]);
    $pembelian = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil detail pembelian
    $stmt = $db->prepare("SELECT d.*, b.nama_bahan 
                         FROM detail_pembelian d
                         JOIN bahan_baku b ON d.id_bahan = b.id_bahan
                         WHERE d.id_pembelian = ?");
    $stmt->execute([$id_pembelian]);
    $detail_pembelian = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_pembelian = $_POST['tanggal_pembelian'];
    $supplier = $_POST['supplier'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $status_pembayaran = $_POST['status_pembayaran'];
    $catatan = $_POST['catatan'];
    $bahan_ids = $_POST['bahan_id'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];

    try {
        $db->beginTransaction();

        // Hitung total harga
        $total_harga = 0;
        foreach ($bahan_ids as $i => $id_bahan) {
            $total_harga += $jumlah[$i] * $harga[$i];
        }

        if ($is_edit) {
            // Update pembelian
            $stmt = $db->prepare("UPDATE pembelian_bahan 
                                SET tanggal_pembelian = ?, supplier = ?, total_harga = ?,
                                    metode_pembayaran = ?, status_pembayaran = ?, catatan = ?
                                WHERE id_pembelian = ?");
            $stmt->execute([
                $tanggal_pembelian,
                $supplier,
                $total_harga,
                $metode_pembayaran,
                $status_pembayaran,
                $catatan,
                $_GET['edit']
            ]);

            // Hapus detail lama
            $stmt = $db->prepare("DELETE FROM detail_pembelian WHERE id_pembelian = ?");
            $stmt->execute([$_GET['edit']]);

            $id_pembelian = $_GET['edit'];
        } else {
            // Insert pembelian baru
            $stmt = $db->prepare("INSERT INTO pembelian_bahan 
                                (tanggal_pembelian, supplier, total_harga, metode_pembayaran, 
                                 status_pembayaran, id_admin, catatan)
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $tanggal_pembelian,
                $supplier,
                $total_harga,
                $metode_pembayaran,
                $status_pembayaran,
                $_SESSION['user_id'],
                $catatan
            ]);
            $id_pembelian = $db->lastInsertId();
        }

        // Insert detail pembelian
        foreach ($bahan_ids as $i => $id_bahan) {
            $subtotal = $jumlah[$i] * $harga[$i];

            $stmt = $db->prepare("INSERT INTO detail_pembelian 
                                (id_pembelian, id_bahan, jumlah, harga_satuan, subtotal)
                                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $id_pembelian,
                $id_bahan,
                $jumlah[$i],
                $harga[$i],
                $subtotal
            ]);

            // Tambahkan stok bahan (hanya jika status lunas)
            if ($status_pembayaran == 'lunas') {
                $tanggal_kadaluarsa = date('Y-m-d', strtotime($tanggal_pembelian . ' +1 year')); // Default 1 tahun

                $stmt = $db->prepare("INSERT INTO stok_bahan 
                                    (id_bahan, jumlah, batch_number, tanggal_masuk, tanggal_kadaluarsa, harga_per_satuan)
                                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id_bahan,
                    $jumlah[$i],
                    'BATCH-' . $id_pembelian,
                    $tanggal_pembelian,
                    $tanggal_kadaluarsa,
                    $harga[$i]
                ]);
            }
        }

        $db->commit();
        redirectWithMessage(
            'index.php',
            'success',
            $is_edit ? 'Pembelian berhasil diupdate' : 'Pembelian berhasil ditambahkan'
        );
    } catch (Exception $e) {
        $db->rollBack();
        redirectWithMessage(
            'add.php' . ($is_edit ? '?edit=' . $_GET['edit'] : ''),
            'danger',
            'Error: ' . $e->getMessage()
        );
    }
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
                    <h3><?= $page_title ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tanggal Pembelian</label>
                                    <input type="date" name="tanggal_pembelian" class="form-control"
                                        value="<?= $is_edit ? $pembelian['tanggal_pembelian'] : date('Y-m-d') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Supplier</label>
                                    <input type="text" name="supplier" class="form-control"
                                        value="<?= $is_edit ? $pembelian['supplier'] : '' ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Metode Pembayaran</label>
                                    <select name="metode_pembayaran" class="form-control" required>
                                        <option value="tunai" <?= $is_edit && $pembelian['metode_pembayaran'] == 'tunai' ? 'selected' : '' ?>>Tunai</option>
                                        <option value="transfer" <?= $is_edit && $pembelian['metode_pembayaran'] == 'transfer' ? 'selected' : '' ?>>Transfer</option>
                                        <option value="kredit" <?= $is_edit && $pembelian['metode_pembayaran'] == 'kredit' ? 'selected' : '' ?>>Kredit</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Status Pembayaran</label>
                                    <select name="status_pembayaran" class="form-control" required>
                                        <option value="lunas" <?= $is_edit && $pembelian['status_pembayaran'] == 'lunas' ? 'selected' : '' ?>>Lunas</option>
                                        <option value="pending" <?= $is_edit && $pembelian['status_pembayaran'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="dibatalkan" <?= $is_edit && $pembelian['status_pembayaran'] == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="2"><?= $is_edit ? $pembelian['catatan'] : '' ?></textarea>
                        </div>

                        <hr>

                        <h5>Detail Pembelian</h5>
                        <div id="itemContainer">
                            <?php if ($is_edit && !empty($detail_pembelian)): ?>
                                <?php foreach ($detail_pembelian as $i => $item): ?>
                                    <div class="item-row row mb-3">
                                        <div class="col-md-5">
                                            <select name="bahan_id[]" class="form-control select-bahan" required>
                                                <option value="">-- Pilih Bahan --</option>
                                                <?php foreach ($bahan as $b): ?>
                                                    <option value="<?= $b['id_bahan'] ?>"
                                                        <?= $item['id_bahan'] == $b['id_bahan'] ? 'selected' : '' ?>
                                                        data-satuan="<?= $b['nama_satuan'] ?>">
                                                        <?= $b['nama_bahan'] ?> (<?= $b['nama_kategori'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="input-group">
                                                <input type="number" name="jumlah[]" class="form-control" min="0.01" step="0.01"
                                                    value="<?= $item['jumlah'] ?>" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text satuan"><?= $bahan[$i]['nama_satuan'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="number" name="harga[]" class="form-control harga" min="0"
                                                    value="<?= $item['harga_satuan'] ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger btn-remove-item">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="item-row row mb-3">
                                    <div class="col-md-5">
                                        <select name="bahan_id[]" class="form-control select-bahan" required>
                                            <option value="">-- Pilih Bahan --</option>
                                            <?php foreach ($bahan as $b): ?>
                                                <option value="<?= $b['id_bahan'] ?>" data-satuan="<?= $b['nama_satuan'] ?>">
                                                    <?= $b['nama_bahan'] ?> (<?= $b['nama_kategori'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="input-group">
                                            <input type="number" name="jumlah[]" class="form-control" min="0.01" step="0.01" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text satuan"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="harga[]" class="form-control harga" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-remove-item">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="button" id="btnAddItem" class="btn btn-secondary mb-3">
                            <i class="fas fa-plus"></i> Tambah Item
                        </button>

                        <hr>

                        <div class="form-group">
                            <label>Total Pembelian</label>
                            <input type="text" class="form-control font-weight-bold" id="totalPembelian" readonly
                                value="Rp 0">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $is_edit ? 'Update Pembelian' : 'Simpan Pembelian' ?>
                        </button>
                        <a href="index.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Fungsi untuk update satuan
            function updateSatuan(selectElement) {
                const selectedOption = selectElement.find('option:selected');
                const satuan = selectedOption.data('satuan') || '';
                selectElement.closest('.item-row').find('.satuan').text(satuan);
            }

            // Fungsi untuk hitung total
            function hitungTotal() {
                let total = 0;
                $('.item-row').each(function() {
                    const harga = parseFloat($(this).find('.harga').val()) || 0;
                    const jumlah = parseFloat($(this).find('input[name="jumlah[]"]').val()) || 0;
                    total += harga * jumlah;
                });
                $('#totalPembelian').val('Rp ' + total.toLocaleString('id-ID'));
            }

            // Event untuk select bahan
            $(document).on('change', '.select-bahan', function() {
                updateSatuan($(this));
            });

            // Event untuk input harga/jumlah
            $(document).on('input', '.harga, input[name="jumlah[]"]', hitungTotal);

            // Tambah item baru
            $('#btnAddItem').click(function() {
                const newItem = $('.item-row').first().clone();
                newItem.find('select').val('');
                newItem.find('input').val('');
                newItem.find('.satuan').text('');
                $('#itemContainer').append(newItem);
            });

            // Hapus item
            $(document).on('click', '.btn-remove-item', function() {
                if ($('.item-row').length > 1) {
                    $(this).closest('.item-row').remove();
                    hitungTotal();
                } else {
                    alert('Minimal harus ada 1 item');
                }
            });

            // Inisialisasi satuan untuk edit mode
            $('.select-bahan').each(function() {
                updateSatuan($(this));
            });

            // Hitung total awal untuk edit mode
            hitungTotal();
        });
    </script>

    <?php include '../../includes/footer.php'; ?>