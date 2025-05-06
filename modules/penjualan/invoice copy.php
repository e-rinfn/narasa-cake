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

// Buat PDF baru dengan ukuran A5
$pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);

// Set dokumen informasi
$pdf->SetCreator('Sistem Kue');
$pdf->SetAuthor('Sistem Kue');
$pdf->SetTitle('Invoice #' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT));
$pdf->SetSubject('Invoice Penjualan');

// Tambah halaman
$pdf->AddPage();

// Tambahkan logo
$logoPath = '../../assets/images/logo.png'; // Sesuaikan dengan path logo Anda
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 30, 0, 'PNG');
}

// Header invoice
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetY(15); // Atur posisi Y setelah logo
$pdf->Cell(0, 10, 'NARASA CAKE & BAKERY', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 4, 'Jl. Contoh No. 123, Tasikmalaya', 0, 1, 'C');
$pdf->Cell(0, 4, 'Telp: 021-1234567 | Email: info@kuemanis.com', 0, 1, 'C');
$pdf->Ln(5);

// Judul invoice
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'INVOICE PENJUALAN', 0, 1, 'C');
$pdf->Ln(3);

// Informasi invoice
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(30, 4, 'No. Invoice', 0, 0);
$pdf->Cell(5, 4, ':', 0, 0);
$pdf->Cell(0, 4, 'INV-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT), 0, 1);
$pdf->Cell(30, 4, 'Tanggal', 0, 0);
$pdf->Cell(5, 4, ':', 0, 0);
$pdf->Cell(0, 4, date('d/m/Y H:i', strtotime($penjualan['tanggal_penjualan'])), 0, 1);
$pdf->Cell(30, 4, 'Pelanggan', 0, 0);
$pdf->Cell(5, 4, ':', 0, 0);
$pdf->Cell(0, 4, $penjualan['nama_pelanggan'] ?? 'Umum', 0, 1);
if ($penjualan['nama_pelanggan']) {
    $pdf->Cell(30, 4, 'Alamat', 0, 0);
    $pdf->Cell(5, 4, ':', 0, 0);
    $pdf->MultiCell(0, 4, $penjualan['alamat'] ?? '-', 0, 1);
    $pdf->Cell(30, 4, 'No. Telepon', 0, 0);
    $pdf->Cell(5, 4, ':', 0, 0);
    $pdf->Cell(0, 4, $penjualan['no_telepon'] ?? '-', 0, 1);
}
$pdf->Cell(30, 4, 'Kasir', 0, 0);
$pdf->Cell(5, 4, ':', 0, 0);
$pdf->Cell(0, 4, $penjualan['nama_admin'], 0, 1);
$pdf->Ln(5);

// Tabel detail penjualan
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(10, 5, 'No', 1, 0, 'C');
$pdf->Cell(65, 5, 'Nama Kue', 1, 0);
$pdf->Cell(25, 5, 'Harga', 1, 0, 'R');
$pdf->Cell(15, 5, 'Jumlah', 1, 0, 'C');
$pdf->Cell(25, 5, 'Subtotal', 1, 0, 'R');
$pdf->Cell(20, 5, 'Poin', 1, 1, 'C');

$pdf->SetFont('helvetica', '', 8);
foreach ($detail as $i => $row) {
    $pdf->Cell(10, 5, $i + 1, 1, 0, 'C');
    $pdf->Cell(65, 5, $row['nama_kue'], 1, 0);
    $pdf->Cell(25, 5, rupiah($row['harga_satuan']), 1, 0, 'R');
    $pdf->Cell(15, 5, $row['jumlah'], 1, 0, 'C');
    $pdf->Cell(25, 5, rupiah($row['subtotal']), 1, 0, 'R');
    $pdf->Cell(20, 5, $row['poin_diberikan'], 1, 1, 'C');
}

// Total
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(115, 5, 'Total Harga', 1, 0, 'R');
$pdf->Cell(25, 5, rupiah($penjualan['total_harga']), 1, 0, 'R');
$pdf->Cell(20, 5, $total_poin, 1, 1, 'C');

// $pdf->Cell(115, 5, 'Poin Dipakai', 1, 0, 'R');
// $pdf->Cell(25, 5, $penjualan['total_poin_dipakai'] . ' poin', 1, 0, 'R');
// $pdf->Cell(20, 5, rupiah($penjualan['nilai_poin_dipakai']), 1, 1, 'R');

// $pdf->Cell(115, 5, 'Total Bayar', 1, 0, 'R');
// $pdf->Cell(25, 5, rupiah($penjualan['total_bayar']), 1, 0, 'R');
// $pdf->Cell(20, 5, '', 1, 1);

$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 4, 'Terima kasih telah berbelanja di Kue Manis', 0, 1, 'C');

// Catatan jika ada
if ($penjualan['catatan']) {
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->MultiCell(0, 4, 'Catatan: ' . $penjualan['catatan'], 0, 1);
}

// Output PDF
$pdf->Output('INV-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT) . '.pdf', 'I');
