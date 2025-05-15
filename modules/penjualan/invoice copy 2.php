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


// Buat PDF baru dengan ukuran A5 Portrait
$pdf = new TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);

// Set dokumen informasi
$pdf->SetCreator('Sistem Kue');
$pdf->SetAuthor('Sistem Kue');
$pdf->SetTitle('Invoice #' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT));
$pdf->SetSubject('Invoice Penjualan');

// Set margins untuk membuat konten lebih ke tengah
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);

// Tambah halaman
$pdf->AddPage();

// Tambahkan logo jika tersedia
$logoPath = '../../assets/images/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 12, 20);
}

// Header toko
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'NARASA CAKE & BAKERY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(0, 3, 'Jl. Raya Pagerageung No.182, Sukadana', 0, 1, 'C');
$pdf->Cell(0, 3, 'Kec. Pagerageung, Kab. Tasikmalaya, Jawa Barat 46413', 0, 1, 'C');

$pdf->Ln(3);

// Judul invoice
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'INVOICE PENJUALAN', 0, 1, 'C');
$pdf->Ln(2);

// Informasi invoice
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(30, 5, 'No. Invoice', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(60, 5, 'INV-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT), 0, 1);
$pdf->Cell(30, 5, 'Tanggal', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(60, 5, formatTanggalIndo($penjualan['tanggal_penjualan']), 0, 1);
$pdf->Cell(30, 5, 'Pelanggan', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(60, 5, $penjualan['nama_pelanggan'] ?? 'Umum', 0, 1);
if (!empty($penjualan['alamat'])) {
    $pdf->Cell(30, 5, 'Alamat', 0, 0);
    $pdf->Cell(5, 5, ':', 0, 0);
    $pdf->MultiCell(60, 5, $penjualan['alamat'], 0, 'L');
}
if (!empty($penjualan['no_telepon'])) {
    $pdf->Cell(30, 5, 'No. Telepon', 0, 0);
    $pdf->Cell(5, 5, ':', 0, 0);
    $pdf->Cell(60, 5, $penjualan['no_telepon'], 0, 1);
}
$pdf->Cell(30, 5, 'Kasir', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(60, 5, $penjualan['nama_admin'], 0, 1);
$pdf->Ln(5);

// Tabel
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(8, 6, 'No', 1, 0, 'C');
$pdf->Cell(38, 6, 'Nama Kue', 1, 0, 'C');
$pdf->Cell(10, 6, 'QTY', 1, 0, 'C');
$pdf->Cell(16, 6, 'Harga', 1, 0, 'C');
$pdf->Cell(18, 6, 'Nabung', 1, 0, 'C');
$pdf->Cell(20, 6, 'Subtotal', 1, 0, 'C');
$pdf->Cell(20, 6, 'Total Nabung', 1, 1, 'C');

$pdf->SetFont('helvetica', '', 7);
foreach ($detail as $i => $row) {
    $pdf->Cell(8, 6, $i + 1, 1, 0, 'C');
    $pdf->Cell(38, 6, $row['nama_kue'], 1, 0);
    $pdf->Cell(10, 6, $row['jumlah'], 1, 0, 'C');
    $pdf->Cell(16, 6, rupiah($row['harga_satuan']), 1, 0, 'R');
    $pdf->Cell(18, 6, rupiah($row['poin_diberikan'] / $row['jumlah']), 1, 0, 'R');
    $pdf->Cell(20, 6, rupiah($row['subtotal']), 1, 0, 'R');
    $pdf->Cell(20, 6, rupiah($row['poin_diberikan']), 1, 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(90, 6, 'Jumlah Subtotal', 1, 0, 'R');
$pdf->Cell(20, 6, rupiah($penjualan['total_harga'] - $total_poin), 1, 0, 'R');
$pdf->Cell(20, 6, rupiah($total_poin), 1, 1, 'R');
$pdf->Cell(90, 6, 'Total', 1, 0, 'R');
$pdf->Cell(20, 6, rupiah($penjualan['total_harga']), 1, 1, 'R');

// Tanda tangan (rata tengah dan pas untuk A5 Portrait)
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 9);

// Lebar masing-masing kolom tanda tangan
$tandaTanganWidth = 60;
$spasiAntarKolom = 10;

// Total lebar yang dipakai: 60 + 10 + 60 = 130 mm
$pageWidth = $pdf->GetPageWidth(); // biasanya 148 mm untuk A5 Portrait
$totalWidth = (2 * $tandaTanganWidth) + $spasiAntarKolom;
$startX = ($pageWidth - $totalWidth) / 2;

$pdf->SetX($startX);
$pdf->Cell($tandaTanganWidth, 5, 'Penerima', 0, 0, 'C');
$pdf->Cell($spasiAntarKolom, 5, '', 0, 0); // spasi antar kolom
$pdf->Cell($tandaTanganWidth, 5, 'Pengirim', 0, 1, 'C');

$pdf->Ln(15);
$pdf->SetX($startX);
$pdf->Cell($tandaTanganWidth, 5, '', 0, 0, 'C');
$pdf->Cell($spasiAntarKolom, 5, '', 0, 0); // spasi antar kolom
$pdf->Cell($tandaTanganWidth, 5, '', 0, 1, 'C');

// Catatan
if (!empty($penjualan['catatan'])) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'I', 6);
    $pdf->MultiCell(0, 4, 'Catatan: ' . $penjualan['catatan'], 0, 'C');
}

// Output
$pdf->Output('INV-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT) . '.pdf', 'I');
