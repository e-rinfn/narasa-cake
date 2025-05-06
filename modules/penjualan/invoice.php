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

// Buat PDF baru dengan ukuran A5 Landscape
$pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);

// Set dokumen informasi
$pdf->SetCreator('Sistem Kue');
$pdf->SetAuthor('Sistem Kue');
$pdf->SetTitle('Invoice #' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT));
$pdf->SetSubject('Invoice Penjualan');

// Set margins untuk membuat konten lebih ke tengah
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// Tambah halaman
$pdf->AddPage();

// Hitung lebar halaman
$pageWidth = $pdf->GetPageWidth();
$contentWidth = $pageWidth - 30; // 15 margin kiri + 15 margin kanan

// Tambahkan logo di sebelah kiri
$logoPath = '../../assets/images/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 25, 20, 20, 0, 'PNG');
    $pdf->SetY(35);
} else {
    $pdf->SetY(25);
}

// Header invoice
$pdf->SetY(20);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'NARASA CAKE & BAKERY', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(0, 3, 'Jl. Raya Pagerageung No.182, Sukadana, Kec. Pagerageung, Kab. Tasikmalaya, Jawa Barat 46413', 0, 1, 'C');
$pdf->Ln(3);

// Judul invoice
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'INVOICE PENJUALAN', 0, 1, 'C');
$pdf->Ln(2);

// Informasi invoice
$col1Width = 40;
$col2Width = $contentWidth - $col1Width;
$pdf->SetFont('helvetica', '', 8);

// No. Invoice
$pdf->Cell($col1Width, 4, 'No. Invoice', 0, 0);
$pdf->Cell(5, 4, ':', 0, 0);
$pdf->Cell($col2Width - 5, 4, 'INV-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT), 0, 1);

// Tanggal
$pdf->Cell($col1Width, 4, 'Tanggal', 0, 0);
$pdf->Cell(5, 4, ':', 0, 0);
$pdf->Cell($col2Width - 5, 4, formatTanggalIndo($penjualan['tanggal_penjualan']), 0, 1);
// Pelanggan
$pdf->Cell($col1Width, 4, 'Pelanggan', 0, 0);
$pdf->Cell(5, 4, ':', 0, 0);
$pdf->Cell($col2Width - 5, 4, $penjualan['nama_pelanggan'] ?? 'Umum', 0, 1);

// Jika ada alamat dan no telepon pelanggan
if ($penjualan['nama_pelanggan']) {
    $pdf->Cell($col1Width, 4, 'Alamat', 0, 0);
    $pdf->Cell(5, 4, ':', 0, 0);
    $pdf->MultiCell($col2Width - 5, 4, $penjualan['alamat'] ?? '-', 0, 1);

    $pdf->Cell($col1Width, 4, 'No. Telepon', 0, 0);
    $pdf->Cell(5, 4, ':', 0, 0);
    $pdf->Cell($col2Width - 5, 4, $penjualan['no_telepon'] ?? '-', 0, 1);
}

// Kasir
$pdf->Cell($col1Width, 4, 'Kasir', 0, 0);
$pdf->Cell(5, 4, ':', 0, 0);
$pdf->Cell($col2Width - 5, 4, $penjualan['nama_admin'], 0, 1);
$pdf->Ln(5);

// Tabel detail penjualan dengan kolom poin di kanan
$colNo = 10;
$colNama = 50;  // Dikurangi untuk memberi ruang kolom poin
$colHarga = 20;
$colNabung = 30;
$colJumlah = 20;
$colSubtotal = 25;
$colPoin = 25; // Lebar kolom Poin

// Header tabel
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($colNo, 6, 'No', 1, 0, 'C');
$pdf->Cell($colNama, 6, 'Nama Kue', 1, 0, 'C');
$pdf->Cell($colJumlah, 6, 'QTY', 1, 0, 'C');
$pdf->Cell($colHarga, 6, 'Harga', 1, 0, 'C');
$pdf->Cell($colNabung, 6, 'Nabung per Kue', 1, 0, 'C');
$pdf->Cell($colSubtotal, 6, 'Subtotal Kue', 1, 0, 'C');
$pdf->Cell($colPoin, 6, 'Subtotal Nabung', 1, 1, 'C'); // Kolom poin di kanan

// Isi tabel
$pdf->SetFont('helvetica', '', 8);
foreach ($detail as $i => $row) {
    $pdf->Cell($colNo, 6, $i + 1, 1, 0, 'C');
    $pdf->Cell($colNama, 6, $row['nama_kue'], 1, 0);
    $pdf->Cell($colJumlah, 6, $row['jumlah'] . " pcs", 1, 0, 'C');
    $pdf->Cell($colHarga, 6, rupiah($row['harga_satuan']), 1, 0, 'R');
    $pdf->Cell($colNabung, 6, ($row['poin_diberikan'] / $row['jumlah']), 1, 0, 'C');
    $pdf->Cell($colSubtotal, 6, rupiah($row['subtotal']), 1, 0, 'R');
    $pdf->Cell($colPoin, 6, rupiah($row['poin_diberikan']), 1, 1, 'C'); // Kolom poin di kanan
}

// Total - atur lebar untuk penyesuaian
$totalLabelWidth = $colNo + $colNama + $colHarga + $colJumlah + $colNabung; // Lebar label total
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($totalLabelWidth, 6, 'Jumlah Subtotal', 1, 0, 'R');
$pdf->Cell($colSubtotal, 6, rupiah($penjualan['total_harga'] - $total_poin), 1, 0, 'R');
$pdf->Cell($colPoin, 6, rupiah($total_poin), 1, 1, 'C'); // Total poin di kolom kanan

// Total - atur lebar untuk penyesuaian
$totalLabelWidth = $colNo + $colNama + $colHarga + $colJumlah + $colNabung; // Lebar label total
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($totalLabelWidth, 6, 'Total', 1, 0, 'R');
$pdf->Cell($colSubtotal, 6, rupiah($penjualan['total_harga']), 1, 1, 'R');


// Spasi sebelum tanda tangan
$pdf->Ln(10);

// Judul tanda tangan kiri dan kanan
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(90, 5, 'Penerima', 0, 0, 'L');
$pdf->Cell(90, 5, 'Pengirim', 0, 1, 'R');

// Spasi untuk tanda tangan
// $pdf->Ln(0);

// $pdf->Cell(90, 0, '_________________________', 0, 0, 'L');
// $pdf->Cell(90, 0, '_________________________', 0, 1, 'R');

// Catatan jika ada
if ($penjualan['catatan']) {
    $pdf->Ln(4);
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->MultiCell(0, 4, 'Catatan: ' . $penjualan['catatan'], 0, 'C');
}

// Output PDF
$pdf->Output('INV-' . str_pad($id_penjualan, 6, '0', STR_PAD_LEFT) . '.pdf', 'I');
