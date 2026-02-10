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

// Buat PDF baru dengan ukuran 58mm lebar (standard thermal printer)
$pdf = new TCPDF('P', 'mm', array(58, 1000), true, 'UTF-8', false);

// Set informasi dokumen
$pdf->SetCreator('Sistem Kue');
$pdf->SetAuthor('Sistem Kue');
$pdf->SetTitle('Bon #' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT));
$pdf->SetSubject('Bon Penjualan');

// Set margin sangat kecil untuk thermal printer
$pdf->SetMargins(2, 2, 2); // Reduced margins
$pdf->SetAutoPageBreak(true, 2); // Smaller footer margin

// Tidak perlu header/footer default
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Tambah halaman
$pdf->AddPage();

// Header toko - lebih compact
$pdf->SetFont('helvetica', 'B', 9, '', true); // Reduced size
$pdf->Cell(0, 3, 'NARASA CAKE & BAKERY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 7, '', true); // Smaller font
$pdf->Cell(0, 2, 'Jl. Raya Pagerageung No.182', 0, 1, 'C');
$pdf->Cell(0, 2, 'Pagerageung, Tasikmalaya', 0, 1, 'C');

// Garis pemisah tipis
$pdf->Ln(1);
$pdf->Line(2, $pdf->GetY(), 56, $pdf->GetY());
$pdf->Ln(1);

// Judul bon
$pdf->SetFont('helvetica', 'B', 8, '', true);
$pdf->Cell(0, 3, 'BON PENJUALAN', 0, 1, 'C');
$pdf->Ln(1);

// Info transaksi - lebih compact
$pdf->SetFont('helvetica', '', 7, '', true);
$pdf->Cell(12, 3, 'No. Bon', 0, 0);
$pdf->Cell(2, 3, ':', 0, 0);
$pdf->SetFont('helvetica', 'B', 7, '', true);
$pdf->Cell(0, 3, str_pad($id_penjualan, 6, '0', STR_PAD_LEFT), 0, 1);

$pdf->SetFont('helvetica', '', 7, '', true);
$pdf->Cell(12, 3, 'Tanggal', 0, 0);
$pdf->Cell(2, 3, ':', 0, 0);
$pdf->Cell(0, 3, formatTanggalIndo($penjualan['tanggal_penjualan']), 0, 1);

$pdf->Cell(12, 3, 'Pelanggan', 0, 0);
$pdf->Cell(2, 3, ':', 0, 0);
$pdf->Cell(0, 3, $penjualan['nama_pelanggan'] ?? 'Umum', 0, 1);

if (!empty($penjualan['no_telepon'])) {
    $pdf->Cell(12, 3, 'Telp', 0, 0);
    $pdf->Cell(2, 3, ':', 0, 0);
    $pdf->Cell(0, 3, $penjualan['no_telepon'], 0, 1);
}

$pdf->Cell(12, 3, 'Kasir', 0, 0);
$pdf->Cell(2, 3, ':', 0, 0);
$pdf->Cell(0, 3, $penjualan['nama_admin'], 0, 1);

// Garis pemisah tipis
$pdf->Ln(1);
$pdf->Line(2, $pdf->GetY(), 56, $pdf->GetY());
$pdf->Ln(1);

// Header tabel produk - lebih compact
$pdf->SetFont('helvetica', 'B', 7, '', true);
$pdf->Cell(15, 3, 'Nama', 0, 0);
$pdf->Cell(10, 3, 'Qty', 0, 0, 'C');
$pdf->Cell(10, 3, 'Harga', 0, 0, 'R');
$pdf->Cell(0, 3, 'Subtotal', 0, 1, 'R');

// Isi tabel produk
$pdf->SetFont('helvetica', '', 7, '', true);
foreach ($detail as $row) {
    $namaKue = (strlen($row['nama_kue']) > 18 ? substr($row['nama_kue'], 0, 15) . '...' : $row['nama_kue']);

    $pdf->Cell(15, 3, $namaKue, 0, 0);
    $pdf->Cell(10, 3, $row['jumlah'], 0, 0, 'C');
    $pdf->Cell(10, 3, rupiah($row['harga_satuan']), 0, 0, 'R');
    $pdf->Cell(0, 3, rupiah($row['subtotal']), 0, 1, 'R');
}

// Garis pemisah tipis
$pdf->Ln(1);
$pdf->Line(2, $pdf->GetY(), 56, $pdf->GetY());
$pdf->Ln(1);

// Total pembayaran
$pdf->SetFont('helvetica', 'B', 8, '', true);
$pdf->Cell(25, 4, 'Total Bayar', 0, 0);
$pdf->Cell(0, 4, rupiah($penjualan['total_harga']), 0, 1, 'R');

// Poin - hanya tampilkan jika ada
if ($total_poin > 0) {
    $pdf->SetFont('helvetica', 'B', 7, '', true);
    $pdf->Cell(25, 3, 'Total Nabung', 0, 0);
    $pdf->Cell(0, 3, 'Rp. ' . ($total_poin), 0, 1, 'R');
}

// Catatan - lebih compact
if (!empty($penjualan['catatan'])) {
    $pdf->Ln(1);
    $pdf->SetFont('helvetica', 'I', 6, '', true);
    $pdf->MultiCell(0, 2, 'Catatan: ' . $penjualan['catatan'], 0, 'L');
}

// Footer
$pdf->Ln(3);
$pdf->SetFont('helvetica', '', 6, '', true);
$pdf->Cell(0, 2, 'Terima kasih atas kunjungan Anda', 0, 1, 'C');
// $pdf->Cell(0, 2, 'www.narasacake.com', 0, 1, 'C');



// Output PDF
$pdf->Output('BON-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT) . '.pdf', 'I');
