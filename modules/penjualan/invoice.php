<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$id_penjualan = $_GET['id'];

// Ambil data penjualan
$stmt = $db->prepare("SELECT p.*, pl.nama_pelanggan, pl.alamat, pl.no_telepon, a.nama_lengkap as nama_admin 
                     FROM penjualan p
                     LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
                     JOIN admin a ON p.id_admin = a.id_admin
                     WHERE p.id_penjualan = ?");
$stmt->execute([$id_penjualan]);
$penjualan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$penjualan) {
    header("Location: ../index.php");
    exit();
}

// Ambil detail penjualan
$stmt = $db->prepare("SELECT dp.*, k.nama_kue 
                     FROM detail_penjualan dp
                     JOIN jenis_kue k ON dp.id_jenis_kue = k.id_jenis_kue
                     WHERE dp.id_penjualan = ?");
$stmt->execute([$id_penjualan]);
$detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total poin
$total_poin = 0;
foreach ($detail as $row) {
    $total_poin += $row['poin_diberikan'];
}

// Set header untuk PDF
header("Content-type: application/pdf");
header("Content-Disposition: inline; filename=INV-" . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT) . ".pdf");

// Gunakan library TCPDF
require_once('../../vendor/autoload.php');


// Buat PDF baru dengan ukuran custom 21 cm x 14 cm (Continuous Form)
$pdf = new TCPDF('L', 'mm', array(210, 140), true, 'UTF-8', false);

// Set dokumen informasi
$pdf->SetCreator('Sistem Kue');
$pdf->SetAuthor('Sistem Kue');
$pdf->SetTitle('Invoice #' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT));
$pdf->SetSubject('Invoice Penjualan');

// Set margins (diperkecil untuk memaksimalkan ruang)
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 8);

// Tambah halaman
$pdf->AddPage();

// Tambahkan logo jika tersedia
$logoPath = '../../assets/images/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 25, 12, 18);
}

// Header toko
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'NARASA CAKE & BAKERY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(0, 3, 'Jl. Raya Pagerageung No.182, Sukadana', 0, 1, 'C');
$pdf->Cell(0, 3, 'Kec. Pagerageung, Kab. Tasikmalaya, Jawa Barat 46413', 0, 1, 'C');

$pdf->Ln(2);

// Judul invoice
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'INVOICE PENJUALAN', 0, 1, 'C');
$pdf->Ln(1);

// Informasi invoice - digeser ke kanan 10mm
$pdf->SetFont('helvetica', '', 7);
$pdf->SetX(18); // Geser mulai dari posisi 18mm (default margin kiri 8mm + 10mm)

$pdf->Cell(25, 4, 'No. Invoice', 0, 0);
$pdf->Cell(3, 4, ':', 0, 0);
$pdf->Cell(60, 4, 'INV-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT), 0, 1);
$pdf->SetX(18); // Set posisi X lagi untuk baris baru

$pdf->Cell(25, 4, 'Tanggal', 0, 0);
$pdf->Cell(3, 4, ':', 0, 0);
$pdf->Cell(60, 4, formatTanggalIndo($penjualan['tanggal_penjualan']), 0, 1);
$pdf->SetX(18);

$pdf->Cell(25, 4, 'Pelanggan', 0, 0);
$pdf->Cell(3, 4, ':', 0, 0);
$pdf->Cell(60, 4, $penjualan['nama_pelanggan'] ?? 'Umum', 0, 1);
$pdf->SetX(18);

if (!empty($penjualan['alamat'])) {
    $pdf->Cell(25, 4, 'Alamat', 0, 0);
    $pdf->Cell(3, 4, ':', 0, 0);
    $pdf->MultiCell(60, 4, $penjualan['alamat'], 0, 'L');
    $pdf->SetX(18);
}

if (!empty($penjualan['no_telepon'])) {
    $pdf->Cell(25, 4, 'No. Telepon', 0, 0);
    $pdf->Cell(3, 4, ':', 0, 0);
    $pdf->Cell(60, 4, $penjualan['no_telepon'], 0, 1);
    $pdf->SetX(18);
}

$pdf->Cell(25, 4, 'Kasir', 0, 0);
$pdf->Cell(3, 4, ':', 0, 0);
$pdf->Cell(60, 4, $penjualan['nama_admin'], 0, 1);
$pdf->Ln(3);

// Hitung lebar kolom tabel
$pageWidth = 210 - 16; // Total lebar halaman - margin kiri dan kanan (8+8)
$colNo = 8;           // No
$colNama = 40;        // Nama Kue (diperlebar)
$colQty = 20;         // QTY
$colHarga = 20;       // Harga
$colNabung = 27;      // Nabung
$colSubtotal = 30;    // Subtotal
$colTotalNabung = 30; // Total Nabung

// Pastikan total lebar kolom tidak melebihi lebar halaman
$totalColWidth = $colNo + $colNama + $colQty + $colHarga + $colNabung + $colSubtotal + $colTotalNabung;
if ($totalColWidth > $pageWidth) {
    // Jika melebihi, sesuaikan lebar kolom nama
    $colNama = $colNama - ($totalColWidth - $pageWidth);
}

// Tabel header
$pdf->SetX(18);
$pdf->SetFont('helvetica', 'B', 6);
$pdf->Cell($colNo, 5, 'No', 1, 0, 'C');
$pdf->Cell($colNama, 5, 'Nama Kue', 1, 0, 'C');
$pdf->Cell($colQty, 5, 'QTY', 1, 0, 'C');
$pdf->Cell($colHarga, 5, 'Harga', 1, 0, 'C');
$pdf->Cell($colNabung, 5, 'Nabung', 1, 0, 'C');
$pdf->Cell($colSubtotal, 5, 'Subtotal', 1, 0, 'C');
$pdf->Cell($colTotalNabung, 5, 'Subtotal Nabung', 1, 1, 'C');

// Tabel isi
$pdf->SetFont('helvetica', '', 6);
foreach ($detail as $i => $row) {
    $pdf->SetX(18); // Set posisi X untuk setiap baris
    $namaKue = (strlen($row['nama_kue']) > 40 ? substr($row['nama_kue'], 0, 37) . '...' : $row['nama_kue']);

    $pdf->Cell($colNo, 5, $i + 1, 1, 0, 'C');
    $pdf->Cell($colNama, 5, $namaKue, 1, 0);
    $pdf->Cell($colQty, 5, $row['jumlah'], 1, 0, 'C');
    $pdf->Cell($colHarga, 5, rupiah($row['harga_satuan']), 1, 0, 'R');
    $pdf->Cell($colNabung, 5, rupiah($row['poin_diberikan'] / $row['jumlah']), 1, 0, 'R');
    $pdf->Cell($colSubtotal, 5, rupiah($row['subtotal']), 1, 0, 'R');
    $pdf->Cell($colTotalNabung, 5, rupiah($row['poin_diberikan']), 1, 1, 'R');
}

// Tabel footer
$pdf->SetX(18);
$pdf->SetFont('helvetica', 'B', 6);
$pdf->Cell($colNo + $colNama + $colQty + $colHarga + $colNabung, 5, 'Jumlah Subtotal', 1, 0, 'R');
$pdf->Cell($colSubtotal, 5, rupiah($penjualan['total_harga'] - $total_poin), 1, 0, 'R');
$pdf->Cell($colTotalNabung, 5, rupiah($total_poin), 1, 1, 'R');

$pdf->SetX(18);
$pdf->Cell($colNo + $colNama + $colQty + $colHarga + $colNabung, 5, 'Total', 1, 0, 'R');
$pdf->Cell($colSubtotal, 5, rupiah($penjualan['total_harga']), 1, 1, 'R');

// Tanda tangan
$pdf->Ln(6);
$pdf->SetFont('helvetica', '', 7);

// Hitung posisi tanda tangan
$tandaTanganWidth = 50;
$spasiAntarKolom = 20;
$totalWidth = (2 * $tandaTanganWidth) + $spasiAntarKolom;
$startX = ($pageWidth - $totalWidth) / 2 + 8; // Ditambah margin kiri

$pdf->SetX($startX);
$pdf->Cell($tandaTanganWidth, 4, 'Penerima', 0, 0, 'C');
$pdf->Cell($spasiAntarKolom, 4, '', 0, 0);
$pdf->Cell($tandaTanganWidth, 4, 'Pengirim', 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetX($startX);
$pdf->Cell($tandaTanganWidth, 4, '_________________________', 0, 0, 'C');
$pdf->Cell($spasiAntarKolom, 4, '', 0, 0);
$pdf->Cell($tandaTanganWidth, 4, '_________________________', 0, 1, 'C');

// Catatan
if (!empty($penjualan['catatan'])) {
    $pdf->Ln(4);
    $pdf->SetFont('helvetica', 'I', 5);
    $pdf->MultiCell(0, 3, 'Catatan: ' . $penjualan['catatan'], 0, 'C');
}

// Output
$pdf->Output('INV-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT) . '.pdf', 'I');
