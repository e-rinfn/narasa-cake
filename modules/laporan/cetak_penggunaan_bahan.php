<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Ambil parameter filter dari URL
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$id_bahan = $_GET['id_bahan'] ?? 'all';

// Query yang sama dengan halaman laporan
$query = "SELECT 
            p.id_produksi,
            p.tanggal_produksi,
            b.nama_bahan,
            s.nama_satuan,
            pb.jumlah_digunakan,
            (pb.jumlah_digunakan * sb.harga_per_satuan) as nilai
          FROM penggunaan_bahan pb
          JOIN bahan_baku b ON pb.id_bahan = b.id_bahan
          JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
          JOIN stok_bahan sb ON pb.id_stok = sb.id_stok
          JOIN produksi p ON pb.id_produksi = p.id_produksi
          WHERE p.tanggal_produksi BETWEEN :start_date AND :end_date";

if ($id_bahan != 'all') {
    $query .= " AND pb.id_bahan = :id_bahan";
}

$query .= " ORDER BY p.tanggal_produksi DESC, p.id_produksi, b.nama_bahan";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);

if ($id_bahan != 'all') {
    $stmt->bindParam(':id_bahan', $id_bahan);
}

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total
$total_digunakan = 0;
$total_nilai = 0;
foreach ($data as $item) {
    $total_digunakan += $item['jumlah_digunakan'];
    $total_nilai += $item['nilai'];
}

// Ambil nama bahan jika filter spesifik
$nama_bahan = 'Semua Bahan';
if ($id_bahan != 'all') {
    $stmt = $db->prepare("SELECT nama_bahan FROM bahan_baku WHERE id_bahan = ?");
    $stmt->execute([$id_bahan]);
    $nama_bahan = $stmt->fetchColumn();
}

// Include TCPDF library
require_once('../../vendor/autoload.php');

// PDF Setup
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistem Kue');
$pdf->SetAuthor('Sistem Kue');
$pdf->SetTitle('Laporan Penjualan');
$pdf->SetSubject('Laporan Penjualan');
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

// Fungsi untuk membuat header/kop
// Fungsi kop
function buatKop($pdf)
{
    $logoPath = '../../assets/images/logo.png';

    // Tambahkan logo jika ada
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 20, 30, 20, 0, 'PNG');
    }

    // Set font untuk header
    $pdf->SetFont('helvetica', 'B', 14);

    // Judul perusahaan
    $pdf->SetY(10);
    $pdf->Cell(0, 15, 'NARASA CAKE & BAKERY', 0, 1, 'C');

    // Alamat perusahaan
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Jl. Raya Pagerageung No.182, Sukadana, Kec. Pagerageung', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Kab. Tasikmalaya, Jawa Barat 46413', 0, 1, 'C');


    // Beri jarak setelah header
    $pdf->SetY($pdf->GetY() + 10);
}

// Panggil fungsi buatKop
buatKop($pdf);

// Judul laporan
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'LAPORAN PENGGUNAAN BAHAN BAKU', 0, 1, 'C');
$pdf->Ln(5);

// Informasi filter
$pdf->SetFont('helvetica', '', 10);

// Periode laporan
$pdf->Cell(40, 5, 'Periode Laporan:', 0, 0);
$pdf->Cell(0, 5, tgl_indo($start_date) . ' s/d ' . tgl_indo($end_date), 0, 1);

// Filter bahan baku
$pdf->Cell(40, 5, 'Bahan Baku:', 0, 0);
$pdf->Cell(0, 5, $nama_bahan, 0, 1);

$pdf->Ln(5);

// Total penggunaan
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'Total Digunakan:', 0, 0);
$pdf->Cell(0, 5, number_format($total_digunakan, 2) . ' ' . ($data[0]['nama_satuan'] ?? ''), 0, 1);

// $pdf->Cell(40, 5, 'Total Nilai:', 0, 0);
// $pdf->Cell(0, 5, rupiah($total_nilai), 0, 1);

$pdf->Ln(5);

// Header tabel
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(25, 7, 'ID Produksi', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Tanggal', 1, 0, 'C', 1);
$pdf->Cell(60, 7, 'Nama Bahan', 1, 0, 'C', 1);
$pdf->Cell(20, 7, 'Satuan', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Jumlah Digunakan', 1, 0, 'C', 1);
// $pdf->Cell(30, 7, 'Nilai', 1, 1, 'C', 1);
$pdf->Cell(0, 7, '', 1, 1, 'C', 1); // Placeholder untuk kolom yang di-comment

// Isi tabel
$pdf->SetFont('helvetica', '', 9);
$current_id = null;
foreach ($data as $row) {
    // Tambahkan baris header produksi jika berbeda
    if ($current_id !== $row['id_produksi']) {
        $current_id = $row['id_produksi'];
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 6, 'Produksi ID: ' . $row['id_produksi'] . ' - Tanggal: ' . tgl_indo($row['tanggal_produksi']), 1, 1, 'L', true);
        $pdf->SetFont('helvetica', '', 9);
    }

    $pdf->Cell(25, 6, $row['id_produksi'], 1, 0);
    $pdf->Cell(25, 6, tgl_indo($row['tanggal_produksi']), 1, 0);
    $pdf->Cell(60, 6, $row['nama_bahan'], 1, 0);
    $pdf->Cell(20, 6, $row['nama_satuan'], 1, 0);
    $pdf->Cell(30, 6, number_format($row['jumlah_digunakan'], 2), 1, 0, 'R');
    // $pdf->Cell(30, 6, rupiah($row['nilai']), 1, 1, 'R');
    $pdf->Cell(0, 6, '', 1, 1, 'R'); // Placeholder untuk kolom yang di-comment
}

// Output PDF
$pdf->Output('Laporan_Penggunaan_Bahan_' . date('Ymd_His') . '.pdf', 'I');
