<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Tambah Penjualan';
$active_page = 'penjualan';

// Ambil data kue yang tersedia
$stmt = $db->query("SELECT s.id_stok_kue, k.nama_kue, s.jumlah, s.tanggal_kadaluarsa, k.harga_jual
                   FROM stok_kue s
                   JOIN jenis_kue k ON s.id_jenis_kue = k.id_jenis_kue
                   WHERE s.jumlah > 0 AND s.tanggal_kadaluarsa >= CURDATE()
                   ORDER BY s.tanggal_kadaluarsa ASC");
$kue_tersedia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data pelanggan
$stmt = $db->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan");
$pelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pelanggan = $_POST['id_pelanggan'] ?: NULL;
    $tanggal_penjualan = $_POST['tanggal_penjualan'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $kue = $_POST['kue'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $diskon = $_POST['diskon'] ?? [];
    $poin_input = $_POST['poin'] ?? []; // Pastikan ini array, jika tidak, gunakan array kosong
    $poin_dipakai = $_POST['poin_dipakai'] ?? 0;
    $catatan = $_POST['catatan'];

    try {
        $db->beginTransaction();

        // Hitung total harga dan total poin
        $total_harga = 0;
        $total_poin = 0;

        // Menghitung total harga dan total poin
        foreach ($kue as $i => $id_stok_kue) {
            $subtotal = ($harga[$i] - ($diskon[$i] ?? 0)) * $jumlah[$i];
            $total_harga += $subtotal;

            // Gunakan poin yang dimasukkan oleh pengguna untuk setiap item
            $poin_per_item = isset($poin_input[$i]) ? $poin_input[$i] : 0; // Pastikan poin per item ada
            $total_poin += $poin_per_item * $jumlah[$i]; // Menambahkan poin sesuai jumlah item
        }

        // Hitung nilai poin yang dipakai (1 poin = Rp 1)
        $nilai_poin_dipakai = min($poin_dipakai, $total_harga);
        $total_bayar = $total_harga - $nilai_poin_dipakai;

        // 1. Insert penjualan
        $stmt = $db->prepare("INSERT INTO penjualan 
                            (tanggal_penjualan, id_pelanggan, id_admin, total_harga, total_diskon, 
                             total_poin_dipakai, nilai_poin_dipakai, total_bayar, metode_pembayaran, catatan) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tanggal_penjualan,
            $id_pelanggan,
            $_SESSION['user_id'],
            $total_harga,
            array_sum($diskon), // Gunakan array_sum untuk diskon
            $poin_dipakai,
            $nilai_poin_dipakai,
            $total_bayar,
            $metode_pembayaran,
            $catatan
        ]);
        $id_penjualan = $db->lastInsertId();

        // 2. Insert detail penjualan dan update stok
        foreach ($kue as $i => $id_stok_kue) {
            $subtotal = ($harga[$i] - ($diskon[$i] ?? 0)) * $jumlah[$i];
            $poin_diberikan = isset($poin_input[$i]) ? $poin_input[$i] * $jumlah[$i] : 0;

            // Dapatkan id_jenis_kue dari stok_kue
            $stmt = $db->prepare("SELECT id_jenis_kue FROM stok_kue WHERE id_stok_kue = ?");
            $stmt->execute([$id_stok_kue]);
            $stok_info = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("INSERT INTO detail_penjualan 
                                (id_penjualan, id_jenis_kue, id_stok_kue, jumlah, harga_satuan, 
                                 diskon_satuan, subtotal, poin_diberikan) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id_penjualan,
                $stok_info['id_jenis_kue'],
                $id_stok_kue,
                $jumlah[$i],
                $harga[$i],
                $diskon[$i] ?? 0,
                $subtotal,
                $poin_diberikan
            ]);

            // Update stok kue
            $stmt = $db->prepare("UPDATE stok_kue SET jumlah = jumlah - ? WHERE id_stok_kue = ?");
            $stmt->execute([$jumlah[$i], $id_stok_kue]);
        }

        // 3. Update poin pelanggan jika ada
        if ($id_pelanggan) {
            // Tambah poin baru (dari penjualan ini)
            $stmt = $db->prepare("UPDATE pelanggan SET total_poin = total_poin + ? WHERE id_pelanggan = ?");
            $stmt->execute([$total_poin, $id_pelanggan]);

            // Kurangi poin yang dipakai (dari poin pelanggan)
            if ($poin_dipakai > 0) {
                $stmt = $db->prepare("UPDATE pelanggan SET total_poin = total_poin - ? WHERE id_pelanggan = ?");
                $stmt->execute([$poin_dipakai, $id_pelanggan]);
            }
        }

        $db->commit();
        redirectWithMessage("invoice.php?id=$id_penjualan", 'success', 'Penjualan berhasil dicatat');
    } catch (Exception $e) {
        $db->rollBack();
        redirectWithMessage('add.php', 'danger', 'Error: ' . $e->getMessage());
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
                                <h5 class="m-b-10">Manajemen</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Penjualan Kue</a></li>

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
                        <h3>Tambah Penjualan</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formPenjualan">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Penjualan</label>
                                        <input type="datetime-local" name="tanggal_penjualan" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Pelanggan</label>
                                        <select name="id_pelanggan" class="form-control select2" id="selectPelanggan">
                                            <option value="">-- Umum --</option>
                                            <?php foreach ($pelanggan as $p): ?>
                                                <option value="<?= $p['id_pelanggan'] ?>"
                                                    data-kategori="<?= $p['id_kategori_pelanggan'] ?>"
                                                    data-poin="<?= $p['total_poin'] ?>">
                                                    <?= $p['nama_pelanggan'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>


                                </div>

                                <div class="col-md-6">
                                    <!-- <div class="form-group">
                                        <label>Poin yang Dipakai</label>
                                        <input type="number" name="poin_dipakai" class="form-control" min="0" value="0" id="poinDipakai">
                                        <small id="infoPoin"></small>
                                    </div> -->

                                    <div class="form-group">
                                        <label>Metode Pembayaran</label>
                                        <select name="metode_pembayaran" class="form-control" required>
                                            <option value="tunai">Tunai</option>
                                            <option value="transfer">Transfer</option>
                                            <option value="kredit">Kredit</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Catatan</label>
                                        <textarea name="catatan" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- [ List Item Penjualan ] Start -->
                            <div id="itemContainer">
                                <!-- Item akan ditambahkan disini menggunakan JavaScript -->
                            </div>
                            <!-- [ List Item Penjualan ] End -->

                            <div class="form-group mt-4">
                                <button type="button" id="btnAddItem" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Item</button>
                            </div>

                            <div class="form-group">
                                <label>Total Harga</label>
                                <input type="text" id="totalHarga" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Total Bayar</label>
                                <input type="text" id="totalBayar" class="form-control" readonly>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-success">Simpan Penjualan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- [ Main Content ] end -->

        </div>
    </div>
</body>

</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        // Function to update total
        function hitungTotal() {
            let totalHarga = 0;
            let totalPoin = 0;
            $('#itemContainer .item-row').each(function() {
                let harga = parseFloat($(this).find('.harga').val());
                let jumlah = parseInt($(this).find('input[name="jumlah[]"]').val());
                let diskon = parseFloat($(this).find('.diskon').val()) || 0;
                let poin = parseInt($(this).find('.poin').val()) || 0;

                totalHarga += (harga - diskon) * jumlah;
                totalPoin += poin * jumlah;
            });
            let poinDipakai = parseInt($('#poinDipakai').val()) || 0;
            let totalBayar = totalHarga - poinDipakai;
            $('#totalHarga').val(totalHarga);
            $('#totalBayar').val(totalBayar);
        }

        // Add item dynamically
        $('#btnAddItem').click(function() {
            let item = `
                <div class="item-row row mb-3">
                    <div class="col-md-4">
                        <label>Kue</label>
                        <select name="kue[]" class="form-control select-kue" required>
                            <option value="">-- Pilih Kue --</option>
                            <?php foreach ($kue_tersedia as $k): ?>
                                <option value="<?= $k['id_stok_kue'] ?>" data-harga="<?= $k['harga_jual'] ?>">
                                    <?= $k['nama_kue'] ?> (Exp: <?= tgl_indo($k['tanggal_kadaluarsa']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah[]" class="form-control" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <label>Harga</label>
                        <input type="number" name="harga[]" class="form-control harga" required>
                    </div>
                    
                    <div class="col-md-2">
                        <label>Poin</label>
                        <input type="number" name="poin[]" class="form-control poin" value="0" min="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-remove-item"><i class="fas fa-times"></i></button>
                    </div>
                </div>`;
            $('#itemContainer').append(item);
        });

        // Remove item
        $(document).on('click', '.btn-remove-item', function() {
            $(this).closest('.item-row').remove();
            hitungTotal();
        });

        // Recalculate total on input change
        $(document).on('input', '#itemContainer .item-row input', function() {
            hitungTotal();
        });

        // Initialize
        hitungTotal();
    });
</script>