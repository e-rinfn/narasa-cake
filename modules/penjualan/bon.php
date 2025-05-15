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

// Buat PDF baru dengan ukuran 58mm lebar (sekitar 220px), tinggi 200mm untuk estimasi
$pdf = new TCPDF('P', 'mm', array(58, 500), true, 'UTF-8', false);

// Set informasi dokumen
$pdf->SetCreator('Sistem Kue');
$pdf->SetAuthor('Sistem Kue');
$pdf->SetTitle('Bon #' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT));
$pdf->SetSubject('Bon Penjualan');

// Set margin kecil untuk struk kecil
$pdf->SetMargins(3, 3, 3);
$pdf->SetAutoPageBreak(true, 3);

// Tambah halaman
$pdf->AddPage();

// Header toko
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 4, 'NARASA CAKE & BAKERY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 5);
$pdf->Cell(0, 3, 'Jl. Raya Pagerageung No.182, Sukadana', 0, 1, 'C');
$pdf->Cell(0, 3, 'Kec. Pagerageung, Kab. Tasikmalaya', 0, 1, 'C');
$pdf->Ln(1);

// Judul bon
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(0, 4, 'BON PENJUALAN', 0, 1, 'C');
$pdf->Ln(1);

// Info transaksi
$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(17, 3, 'No. Bon', 0, 0);
$pdf->Cell(2, 3, ':', 0, 0);
$pdf->Cell(0, 3, str_pad($id_penjualan, 6, '0', STR_PAD_LEFT), 0, 1);

$pdf->Cell(17, 3, 'Tanggal', 0, 0);
$pdf->Cell(2, 3, ':', 0, 0);
$pdf->Cell(0, 3, formatTanggalIndo($penjualan['tanggal_penjualan']), 0, 1);

$pdf->Cell(17, 3, 'Pelanggan', 0, 0);
$pdf->Cell(2, 3, ':', 0, 0);
$pdf->Cell(0, 3, $penjualan['nama_pelanggan'] ?? 'Umum', 0, 1);

if (!empty($penjualan['no_telepon'])) {
    $pdf->Cell(17, 3, 'Telp', 0, 0);
    $pdf->Cell(2, 3, ':', 0, 0);
    $pdf->Cell(0, 3, $penjualan['no_telepon'], 0, 1);
}

$pdf->Cell(17, 3, 'Kasir', 0, 0);
$pdf->Cell(2, 3, ':', 0, 0);
$pdf->Cell(0, 3, $penjualan['nama_admin'], 0, 1);
$pdf->Ln(1);

// Garis pemisah
$lineY = $pdf->GetY();
$pdf->Line(3, $lineY, 55, $lineY);
$pdf->Ln(1);

// Tabel produk
$pdf->SetFont('helvetica', 'B', 5.5);
$pdf->Cell(22, 4, 'Nama', 0, 0);
$pdf->Cell(6, 4, 'Qty', 0, 0, 'C');
$pdf->Cell(12, 4, 'Harga', 0, 0, 'R');
$pdf->Cell(0, 4, 'Subt.', 0, 1, 'R');

$pdf->SetFont('helvetica', '', 5);
foreach ($detail as $row) {
    $namaKue = (strlen($row['nama_kue']) > 20 ? substr($row['nama_kue'], 0, 17) . '...' : $row['nama_kue']);

    $pdf->Cell(22, 4, $namaKue, 0, 0);
    $pdf->Cell(6, 4, $row['jumlah'], 0, 0, 'C');
    $pdf->Cell(12, 4, rupiah($row['harga_satuan']), 0, 0, 'R');
    $pdf->Cell(0, 4, rupiah($row['subtotal']), 0, 1, 'R');
}

// Garis pemisah
$pdf->Ln(1);
$lineY = $pdf->GetY();
$pdf->Line(3, $lineY, 55, $lineY);
$pdf->Ln(1);

// Total
$pdf->SetFont('helvetica', 'B', 6);
$pdf->Cell(30, 4, 'Total Bayar', 0, 0);
$pdf->Cell(0, 4, rupiah($penjualan['total_harga']), 0, 1, 'R');

// Poin
if ($total_poin > 0) {
    $pdf->Cell(30, 4, 'Total Nabung', 0, 0);
    $pdf->Cell(0, 4, rupiah($total_poin), 0, 1, 'R');
}

$pdf->Ln(2);

// Catatan
if (!empty($penjualan['catatan'])) {
    $pdf->SetFont('helvetica', 'I', 4.5);
    $pdf->MultiCell(0, 3, 'Catatan: ' . $penjualan['catatan'], 0, 'L');
}

// Footer tanda terima
$pdf->Ln(4);
$pdf->SetFont('helvetica', '', 5);
$pdf->Cell(0, 3, 'Terima kasih atas kunjungan Anda', 0, 1, 'C');

// Output PDF
$pdf->Output('BON-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT) . '.pdf', 'I');
