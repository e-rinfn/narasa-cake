<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Tambah Penjualan';
$active_page = 'penjualan';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get available cakes with stock
$stmt = $db->prepare("SELECT 
    s.id_stok_kue,
    k.id_jenis_kue, 
    k.nama_kue, 
    k.harga_jual,
    s.jumlah as stok
FROM jenis_kue k
JOIN stok_kue s ON k.id_jenis_kue = s.id_jenis_kue
WHERE s.jumlah > 0
ORDER BY k.nama_kue ASC");
$stmt->execute();
$kue_tersedia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customers
$stmt = $db->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan");
$pelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirectWithMessage('add.php', 'danger', 'Invalid CSRF token');
    }

    // Sanitize inputs
    $id_pelanggan = !empty($_POST['id_pelanggan']) ? intval($_POST['id_pelanggan']) : null;
    $tanggal_penjualan = isset($_POST['tanggal_penjualan']) ? $_POST['tanggal_penjualan'] : date('Y-m-d H:i:s');
    $metode_pembayaran = in_array($_POST['metode_pembayaran'], ['tunai', 'transfer', 'kredit']) ? $_POST['metode_pembayaran'] : 'tunai';
    $catatan = isset($_POST['catatan']) ? htmlspecialchars($_POST['catatan']) : '';

    // Validate required arrays
    if (
        empty($_POST['kue']) || !is_array($_POST['kue']) ||
        empty($_POST['jumlah']) || !is_array($_POST['jumlah']) ||
        empty($_POST['harga']) || !is_array($_POST['harga'])
    ) {
        redirectWithMessage('add.php', 'danger', 'Data kue tidak valid');
    }

    $kue = array_map('intval', $_POST['kue']);
    $jumlah = array_map('intval', $_POST['jumlah']);
    $harga = array_map('floatval', $_POST['harga']);

    // Handle optional arrays
    $diskon = isset($_POST['diskon']) && is_array($_POST['diskon']) ? array_map('floatval', $_POST['diskon']) : array_fill(0, count($kue), 0);
    $poin_input = isset($_POST['poin']) && is_array($_POST['poin']) ? array_map('intval', $_POST['poin']) : array_fill(0, count($kue), 0);
    $poin_dipakai = isset($_POST['poin_dipakai']) ? intval($_POST['poin_dipakai']) : 0;

    try {
        $db->beginTransaction();

        // 1. Validate stock and calculate totals
        $total_harga = 0;
        $total_poin = 0;
        $items = [];

        foreach ($kue as $i => $id_stok_kue) {
            // Validate stock
            $stmt = $db->prepare("SELECT s.jumlah, k.id_jenis_kue, k.harga_jual 
                                FROM stok_kue s 
                                JOIN jenis_kue k ON s.id_jenis_kue = k.id_jenis_kue 
                                WHERE s.id_stok_kue = ? AND s.jumlah >= ?");
            $stmt->execute([$id_stok_kue, $jumlah[$i]]);
            $stok_info = $stmt->fetch();

            if (!$stok_info) {
                throw new Exception("Stok tidak mencukupi atau tidak ditemukan untuk ID: $id_stok_kue");
            }

            // Calculate item totals
            $harga_satuan = $harga[$i] > 0 ? $harga[$i] : $stok_info['harga_jual'];
            $diskon_satuan = $diskon[$i] ?? 0;
            $jumlah_jual = $jumlah[$i];
            $poin_item = $poin_input[$i] ?? 0;

            $subtotal = ($harga_satuan - $diskon_satuan) * $jumlah_jual;

            $items[] = [
                'id_stok_kue' => $id_stok_kue,
                'id_jenis_kue' => $stok_info['id_jenis_kue'],
                'jumlah' => $jumlah_jual,
                'harga_satuan' => $harga_satuan,
                'diskon_satuan' => $diskon_satuan,
                'subtotal' => $subtotal,
                'poin_diberikan' => $poin_item * $jumlah_jual
            ];

            $total_harga += $subtotal;
            $total_poin += $poin_item * $jumlah_jual;
        }

        // 2. Handle customer points
        $nilai_poin_dipakai = 0;
        $poin_pelanggan = 0;

        if ($id_pelanggan) {
            // Get current points
            $stmt = $db->prepare("SELECT total_poin FROM pelanggan WHERE id_pelanggan = ?");
            $stmt->execute([$id_pelanggan]);
            $pelanggan_info = $stmt->fetch();
            $poin_pelanggan = $pelanggan_info['total_poin'] ?? 0;

            // Calculate points to use (1 point = 1 rupiah)
            $nilai_poin_dipakai = min($poin_dipakai, $total_harga, $poin_pelanggan);
        }

        $total_bayar = $total_harga - $nilai_poin_dipakai;

        // 3. Insert sale record
        $stmt = $db->prepare("INSERT INTO penjualan 
                            (tanggal_penjualan, id_pelanggan, id_admin, total_harga, total_diskon, 
                             total_poin_dipakai, nilai_poin_dipakai, total_bayar, metode_pembayaran, catatan) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tanggal_penjualan,
            $id_pelanggan,
            $_SESSION['user_id'],
            $total_harga,
            array_sum(array_column($items, 'diskon_satuan')),
            $poin_dipakai,
            $nilai_poin_dipakai,
            $total_bayar,
            $metode_pembayaran,
            $catatan
        ]);
        $id_penjualan = $db->lastInsertId();

        // 4. Insert sale details and update stock
        foreach ($items as $item) {
            // Insert detail
            $stmt = $db->prepare("INSERT INTO detail_penjualan 
                                (id_penjualan, id_jenis_kue, id_stok_kue, jumlah, harga_satuan, 
                                diskon_satuan, subtotal, poin_diberikan) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id_penjualan,
                $item['id_jenis_kue'],
                $item['id_stok_kue'],
                $item['jumlah'],
                $item['harga_satuan'],
                $item['diskon_satuan'],
                $item['subtotal'],
                $item['poin_diberikan']
            ]);

            // Update stock
            $stmt = $db->prepare("UPDATE stok_kue SET jumlah = jumlah - ? WHERE id_stok_kue = ?");
            $stmt->execute([$item['jumlah'], $item['id_stok_kue']]);
        }

        // 5. Update customer points if applicable
        if ($id_pelanggan) {
            // Add earned points
            if ($total_poin > 0) {
                $stmt = $db->prepare("UPDATE pelanggan SET total_poin = total_poin + ? WHERE id_pelanggan = ?");
                $stmt->execute([$total_poin, $id_pelanggan]);
            }

            // Subtract used points
            if ($nilai_poin_dipakai > 0) {
                $stmt = $db->prepare("UPDATE pelanggan SET total_poin = total_poin - ? WHERE id_pelanggan = ?");
                $stmt->execute([$poin_dipakai, $id_pelanggan]);
            }
        }

        $db->commit();
        redirectWithMessage("index.php", 'success', 'Penjualan berhasil dicatat');
    } catch (Exception $e) {
        $db->rollBack();
        redirectWithMessage('add.php', 'danger', 'Error: ' . $e->getMessage());
    }
}

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <style>
        .stock-info {
            font-size: 0.8rem;
            color: #666;
            margin-top: 3px;
        }

        .max-stock {
            color: #dc3545;
            font-weight: bold;
        }

        .item-row {
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 10px;
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
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Transaksi</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Penjualan</a></li>
                                <li class="breadcrumb-item active">Tambah</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <?php displayMessage(); ?>

            <!-- [ breadcrumb ] end -->
            <div class="row">
                <!-- [ Main Content ] start -->
                <div class="card">
                    <div class="card-header">
                        <h3>Tambah Penjualan</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formPenjualan">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

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
                                                    data-poin="<?= $p['total_poin'] ?>">
                                                    <?= htmlspecialchars($p['nama_pelanggan']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
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

                            <!-- Customer points section -->
                            <div class="row mb-3" id="customerPointsSection" style="display: none;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Poin Pelanggan</label>
                                        <input type="text" id="customerPoints" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Gunakan Poin (1 Poin = Rp 1)</label>
                                        <input type="number" name="poin_dipakai" id="poinDipakai" class="form-control" min="0" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- [ List Item Penjualan ] Start -->
                            <div id="itemContainer">
                                <!-- Items will be added here via JavaScript -->
                            </div>
                            <!-- [ List Item Penjualan ] End -->

                            <div class="form-group mt-4">
                                <button type="button" id="btnAddItem" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Item
                                </button>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Total Harga</label>
                                        <input type="text" id="totalHarga" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Poin Didapat</label>
                                        <input type="text" id="totalPoin" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Total Bayar</label>
                                        <input type="text" id="totalBayar" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <button type="button" id="btnSimpan" class="btn btn-primary">Simpan</button>
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize first item
            addItemRow();

            // Customer selection change
            $('#selectPelanggan').change(function() {
                const selectedOption = $(this).find('option:selected');
                const points = parseInt(selectedOption.data('poin')) || 0;

                if (selectedOption.val()) {
                    $('#customerPointsSection').show();
                    $('#customerPoints').val(points.toLocaleString() + ' poin');
                    $('#poinDipakai').attr('max', points);
                } else {
                    $('#customerPointsSection').hide();
                    $('#poinDipakai').val(0);
                }

                calculateTotals();
            });

            // Add item row
            $('#btnAddItem').click(addItemRow);

            // Remove item row
            $(document).on('click', '.btn-remove-item', function() {
                if ($('#itemContainer .item-row').length > 1) {
                    $(this).closest('.item-row').remove();
                    calculateTotals();
                } else {
                    Swal.fire('Peringatan', 'Minimal harus ada 1 item', 'warning');
                }
            });

            // Calculate totals on any input change
            $(document).on('input', '#itemContainer input, #itemContainer select, #poinDipakai', calculateTotals);

            // Save button click
            $('#btnSimpan').click(function() {
                // Validate at least one item with quantity > 0
                let valid = false;
                $('input[name="jumlah[]"]').each(function() {
                    if (parseInt($(this).val()) > 0) valid = true;
                });

                if (!valid) {
                    Swal.fire('Error', 'Minimal harus ada 1 item dengan jumlah lebih dari 0', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Apakah Anda yakin ingin menyimpan penjualan ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#formPenjualan').submit();
                    }
                });
            });

            // Function to add new item row
            function addItemRow() {
                const itemHtml = `
                <div class="item-row row mb-3 p-2 border">
                    <div class="col-md-4">
                        <label>Kue</label>
                        <select name="kue[]" class="form-control select-kue" required>
                            <?php foreach ($kue_tersedia as $k): ?>
                                <option value="<?= $k['id_stok_kue'] ?>"
                                    data-stock="<?= $k['stok'] ?>"
                                    data-harga="<?= $k['harga_jual'] ?>">
                                    <?= htmlspecialchars($k['nama_kue']) ?> 
                                    (Stok: <?= $k['stok'] ?>, Harga: <?= number_format($k['harga_jual']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="stock-info">Stok tersedia: <span class="stock-amount">0</span></div>
                    </div>
                    <div class="col-md-2">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah[]" class="form-control jumlah" value="1" min="1" required>
                        <div class="max-stock-info text-danger small"></div>
                    </div>
                    <div class="col-md-2">
                        <label>Harga Satuan</label>
                        <input type="number" name="harga[]" class="form-control harga" required>
                    </div>
                    <div class="col-md-2">
                        <label>Poin per Item</label>
                        <input type="number" name="poin[]" class="form-control poin" value="0" min="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-remove-item">
                            <i class="fas fa-times"></i> Hapus
                        </button>
                    </div>
                </div>`;

                $('#itemContainer').append(itemHtml);
                initializeItemRow($('#itemContainer .item-row').last());
                calculateTotals();
            }

            // Function to initialize item row
            function initializeItemRow(row) {
                const select = row.find('.select-kue');
                const jumlahInput = row.find('.jumlah');
                const stockAmount = row.find('.stock-amount');
                const maxStockInfo = row.find('.max-stock-info');
                const hargaInput = row.find('.harga');

                // Set initial values from selected cake
                const selectedOption = select.find('option:selected');
                const stock = parseInt(selectedOption.data('stock'));
                const price = parseFloat(selectedOption.data('harga'));

                stockAmount.text(stock);
                hargaInput.val(price);
                jumlahInput.attr('max', stock);

                // Handle cake selection change
                select.change(function() {
                    const selected = $(this).find('option:selected');
                    const newStock = parseInt(selected.data('stock'));
                    const newPrice = parseFloat(selected.data('harga'));

                    stockAmount.text(newStock);
                    hargaInput.val(newPrice);
                    jumlahInput.attr('max', newStock);

                    // Validate current quantity
                    const currentQty = parseInt(jumlahInput.val()) || 0;
                    if (currentQty > newStock) {
                        maxStockInfo.text('Jumlah melebihi stok!');
                        jumlahInput.val(newStock);
                    } else {
                        maxStockInfo.text('');
                    }

                    calculateTotals();
                });

                // Handle quantity input
                jumlahInput.on('input', function() {
                    const maxStock = parseInt(jumlahInput.attr('max')) || 0;
                    const enteredQty = parseInt($(this).val()) || 0;

                    if (enteredQty > maxStock) {
                        maxStockInfo.text('Jumlah melebihi stok!');
                        $(this).val(maxStock);
                    } else if (enteredQty <= 0) {
                        $(this).val(1);
                    } else {
                        maxStockInfo.text('');
                    }

                    calculateTotals();
                });
            }

            // Function to calculate all totals
            function calculateTotals() {
                let totalHarga = 0;
                let totalPoin = 0;

                $('#itemContainer .item-row').each(function() {
                    const harga = parseFloat($(this).find('.harga').val()) || 0;
                    const jumlah = parseInt($(this).find('.jumlah').val()) || 0;
                    const poin = parseInt($(this).find('.poin').val()) || 0;

                    totalHarga += harga * jumlah;
                    totalPoin += poin * jumlah;
                });

                const poinDipakai = parseInt($('#poinDipakai').val()) || 0;
                const totalBayar = Math.max(0, totalHarga - poinDipakai);

                $('#totalHarga').val(formatCurrency(totalHarga));
                $('#totalPoin').val(totalPoin + ' poin');
                $('#totalBayar').val(formatCurrency(totalBayar));
            }

            // Helper function to format currency
            function formatCurrency(amount) {
                return 'Rp ' + amount.toLocaleString('id-ID');
            }
        });
    </script>
</body>

</html>