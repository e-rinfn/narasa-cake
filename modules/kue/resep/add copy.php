<?php
require_once '../../../config/config.php';
require_once '../../../includes/functions.php';
checkAuth();

$page_title = 'Tambah Resep Kue';
$active_page = 'resep';

// Ambil data jenis kue
$stmt = $db->query("SELECT * FROM jenis_kue WHERE aktif = 1 ORDER BY nama_kue");
$jenis_kue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data bahan baku
$stmt = $db->query("SELECT b.*, k.nama_kategori, s.nama_satuan 
                   FROM bahan_baku b
                   JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                   JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                   WHERE b.aktif = 1
                   ORDER BY b.nama_bahan");
$bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_jenis_kue = $_POST['id_jenis_kue'];
    $nama_resep = $_POST['nama_resep'];


    $porsi = $_POST['porsi'];
    $bahan_resep = $_POST['bahan'] ?? [];

    // Validasi input
    if (empty($id_jenis_kue) || empty($nama_resep) || $porsi < 1) {
        redirectWithMessage('add.php', 'danger', 'Semua field harus diisi dengan benar');
    }

    // Cek minimal 1 bahan dengan jumlah > 0
    $valid_bahan = false;
    foreach ($bahan_resep as $detail) {
        if ($detail['jumlah'] > 0) {
            $valid_bahan = true;
            break;
        }
    }

    if (!$valid_bahan) {
        redirectWithMessage('add.php', 'danger', 'Minimal harus ada 1 bahan dengan jumlah lebih dari 0');
    }

    try {
        $db->beginTransaction();

        // 1. Insert data resep
        $stmt = $db->prepare("INSERT INTO resep_kue 
                             (id_jenis_kue, nama_resep, porsi) 
                             VALUES (?, ?, ?)");
        $stmt->execute([$id_jenis_kue, $nama_resep, $porsi]);
        $id_resep = $db->lastInsertId();

        // 2. Insert detail bahan resep
        foreach ($bahan_resep as $id_bahan => $detail) {
            $jumlah = $detail['jumlah'];
            $catatan = $detail['catatan'] ?? '';

            if ($jumlah > 0) {
                $stmt = $db->prepare("INSERT INTO detail_resep 
                                     (id_resep, id_bahan, jumlah, catatan) 
                                     VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_resep, $id_bahan, $jumlah, $catatan]);
            }
        }

        $db->commit();
        redirectWithMessage('../resep/index.php', 'success', 'Resep berhasil ditambahkan');
    } catch (PDOException $e) {
        $db->rollBack();
        redirectWithMessage('add.php', 'danger', 'Error: ' . $e->getMessage());
    }
}

include '../../../includes/header.php';
?>

<!-- [ Sidebar Menu ] start -->
<?php include '../../../includes/sidebar2.php'; ?>

<!-- [ Sidebar Menu ] end -->


<?php include '../../../includes/navbar.php'; ?>


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
                            <li class="breadcrumb-item"><a href="#">Resep Kue</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3>Tambah Resep Baru</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formResep">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Jenis Kue</label>
                                        <select name="id_jenis_kue" class="form-select" required>
                                            <option value="">-- Pilih Jenis Kue --</option>
                                            <?php foreach ($jenis_kue as $jk): ?>
                                                <option value="<?= $jk['id_jenis_kue'] ?>"><?= $jk['nama_kue'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="form-label">Nama Resep</label>
                                        <input type="text" name="nama_resep" class="form-control" required>
                                    </div>

                                    <!-- <div class="form-group mb-3">
                                        <label class="form-label">Versi Resep</label>
                                        <input type="text" name="versi" class="form-control" placeholder="Contoh: 1.0" required>
                                    </div> -->
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Jumlah Porsi</label>
                                        <input type="number" name="porsi" class="form-control" min="1" value="1" required>
                                        <small class="text-muted">Jumlah kue yang dihasilkan dari resep ini</small>
                                    </div>

                                    <!-- <div class="form-group mb-3">
                                        <label class="form-label">Instruksi Pembuatan</label>
                                        <textarea name="instruksi" class="form-control" rows="5" required></textarea>
                                    </div> -->
                                </div>
                            </div>

                            <hr>

                            <h5 class="mb-3">Bahan-bahan</h5>
                            <!-- <div class="form-group mb-3">
                                <input type="text" id="filterBahan" class="form-control" placeholder="Cari bahan...">
                            </div> -->

                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabelBahan">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th> <!-- Tambahan kolom No -->
                                            <th>Nama Bahan</th>
                                            <th>Kategori</th>
                                            <th width="15%">Jumlah</th>
                                            <th width="15%">Satuan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1; // Inisialisasi nomor
                                        foreach ($bahan as $b): ?>
                                            <tr>
                                                <td><?= $no++ ?></td> <!-- Tampilkan nomor -->
                                                <td><?= $b['nama_bahan'] ?></td>
                                                <td><?= $b['nama_kategori'] ?></td>
                                                <td>
                                                    <input type="number" name="bahan[<?= $b['id_bahan'] ?>][jumlah]"
                                                        class="form-control" min="0" step="0.01" value="0">
                                                </td>
                                                <td><?= $b['nama_satuan'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary me-2">Simpan Resep</button>
                                <a href="../resep/index.php" class="btn btn-secondary">Kembali</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Filter bahan
        $('#filterBahan').keyup(function() {
            const filter = $(this).val().toLowerCase();

            $('#tabelBahan tbody tr').each(function() {
                const namaBahan = $(this).find('td:first').text().toLowerCase();
                if (namaBahan.includes(filter)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Toggle tombol hapus berdasarkan input jumlah
        $(document).on('input', 'input[name^="bahan["]', function() {
            const jumlah = parseFloat($(this).val());
            const btnHapus = $(this).closest('tr').find('.btn-hapus-bahan');

            if (jumlah > 0) {
                btnHapus.prop('disabled', false);
            } else {
                btnHapus.prop('disabled', true);
            }
        });

        // Hapus bahan dari daftar
        $(document).on('click', '.btn-hapus-bahan', function() {
            const row = $(this).closest('tr');
            row.find('input[name^="bahan["]').val(0);
            $(this).prop('disabled', true);
        });

        // Validasi form sebelum submit
        $('#formResep').submit(function(e) {
            let valid = true;

            // Cek field required
            $(this).find('[required]').each(function() {
                if ($(this).val() === '') {
                    valid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Cek minimal 1 bahan dengan jumlah > 0
            let bahanValid = false;
            $('input[name^="bahan["][name$="[jumlah]"]').each(function() {
                if (parseFloat($(this).val()) > 0) {
                    bahanValid = true;
                }
            });

            if (!bahanValid) {
                valid = false;
                alert('Minimal harus ada 1 bahan dengan jumlah lebih dari 0');
            }

            if (!valid) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>

<style>
    /* Tambahan styling untuk tampilan yang lebih baik */
    .table th {
        white-space: nowrap;
    }

    .form-control.is-invalid {
        border-color: #dc3545;
    }
</style>

<?php include '../../../includes/footer.php'; ?>