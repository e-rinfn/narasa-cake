<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Edit Bahan Baku';
$active_page = 'bahan';

// Ambil ID dari URL
$id_bahan = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data bahan
$stmt = $db->prepare("SELECT * FROM bahan_baku WHERE id_bahan = ?");
$stmt->execute([$id_bahan]);
$bahan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bahan) {
  redirectWithMessage('index.php', 'danger', 'Bahan baku tidak ditemukan');
}

// Ambil data kategori dan satuan
$kategori = $db->query("SELECT * FROM kategori_bahan ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);
$satuan = $db->query("SELECT * FROM satuan_bahan ORDER BY nama_satuan")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nama_bahan = $_POST['nama_bahan'];
  $id_kategori = $_POST['id_kategori'];
  $id_satuan = $_POST['id_satuan'];
  $stok_minimal = $_POST['stok_minimal'];
  $harga_per_satuan = $_POST['harga_per_satuan'] ?? null;
  $metode_penyimpanan = $_POST['metode_penyimpanan'];
  $aktif = isset($_POST['aktif']) ? 1 : 0;

  try {
    $stmt = $db->prepare("UPDATE bahan_baku 
                             SET nama_bahan = ?, id_kategori = ?, id_satuan = ?, 
                                 stok_minimal = ?, harga_per_satuan = ?, 
                                 metode_penyimpanan = ?, aktif = ?
                             WHERE id_bahan = ?");
    $stmt->execute([
      $nama_bahan,
      $id_kategori,
      $id_satuan,
      $stok_minimal,
      $harga_per_satuan ?? null,
      $metode_penyimpanan,
      $aktif,
      $id_bahan
    ]);

    redirectWithMessage('index.php', 'success', 'Bahan baku berhasil diperbarui');
  } catch (PDOException $e) {
    redirectWithMessage('edit.php?id=' . $id_bahan, 'danger', 'Error: ' . $e->getMessage());
  }
}
include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title><?= $page_title ?></title>
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
              <h3 class="mb-0">Edit Bahan Baku</h3>
            </div>
          </div>

          <div class="card-body">


            <!-- Form Input -->
            <form method="POST">


              <div class="row">

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="nama_bahan">Nama Bahan</label>
                    <input type="text" class="form-control" id="nama_bahan" name="nama_bahan"
                      value="<?= htmlspecialchars($bahan['nama_bahan']) ?>" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="stok_minimal">Stok Minimal</label>
                    <input type="number" class="form-control" id="stok_minimal" name="stok_minimal"
                      min="0" step="0.01" value="<?= $bahan['stok_minimal'] ?>" required>
                  </div>
                </div>

              </div>


              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="id_kategori">Kategori</label>
                    <select class="form-control" id="id_kategori" name="id_kategori" required>
                      <option value="">-- Pilih Kategori --</option>
                      <?php foreach ($kategori as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>"
                          <?= $k['id_kategori'] == $bahan['id_kategori'] ? 'selected' : '' ?>>
                          <?= $k['nama_kategori'] ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="id_satuan">Satuan</label>
                    <select class="form-control" id="id_satuan" name="id_satuan" required>
                      <option value="">-- Pilih Satuan --</option>
                      <?php foreach ($satuan as $s): ?>
                        <option value="<?= $s['id_satuan'] ?>"
                          <?= $s['id_satuan'] == $bahan['id_satuan'] ? 'selected' : '' ?>>
                          <?= $s['nama_satuan'] ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="aktif" name="aktif"
                  <?= $bahan['aktif'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="aktif">Aktif</label>
              </div>

              <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
              <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>




          </div>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </div>
  </div>

  <?php include '../../includes/footer.php'; ?>
</body>

</html>