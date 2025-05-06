<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$id_stok_kue = $_GET['id'] ?? 0;

// Ambil data stok
$stmt = $db->prepare("SELECT sk.*, jk.nama_kue 
                     FROM stok_kue sk
                     JOIN jenis_kue jk ON sk.id_jenis_kue = jk.id_jenis_kue
                     WHERE sk.id_stok_kue = ?");
$stmt->execute([$id_stok_kue]);
$stok = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stok) {
    echo '<div class="alert alert-danger">Data stok tidak ditemukan</div>';
    exit;
}
?>

<form id="editStokForm">
    <input type="hidden" name="id_stok_kue" value="<?= $stok['id_stok_kue'] ?>">

    <div class="mb-3">
        <label class="form-label">Jenis Kue</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($stok['nama_kue']) ?>" readonly>
    </div>

    <div class="mb-3">
        <label class="form-label">Jumlah</label>
        <input type="number" name="jumlah" class="form-control"
            value="<?= $stok['jumlah'] ?>" min="0" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Tanggal Produksi</label>
        <input type="date" name="tanggal_produksi" class="form-control"
            value="<?= $stok['tanggal_produksi'] ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Tanggal Kadaluarsa</label>
        <input type="date" name="tanggal_kadaluarsa" class="form-control"
            value="<?= $stok['tanggal_kadaluarsa'] ?>" required>
    </div>
</form>

<script>
    // Script untuk menangani submit form
    $(document).ready(function() {
        $('#saveStokChanges').click(function() {
            const formData = $('#editStokForm').serialize();

            $.ajax({
                url: 'update_stok.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Tutup modal dan refresh halaman
                        $('#editStokModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menyimpan');
                }
            });
        });
    });
</script>