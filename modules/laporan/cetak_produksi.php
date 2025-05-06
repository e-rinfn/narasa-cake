<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Ambil parameter filter dari URL
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$id_jenis_kue = $_GET['id_jenis_kue'] ?? null;

// Query yang sama dengan halaman laporan
$sql = "SELECT p.id_produksi, p.tanggal_produksi, 
               r.nama_resep, r.versi, 
               k.nama_kue,
               p.jumlah_batch, p.total_kue,
               a.nama_lengkap as operator
        FROM produksi p
        JOIN resep_kue r ON p.id_resep = r.id_resep
        JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
        JOIN admin a ON p.id_admin = a.id_admin
        WHERE p.tanggal_produksi BETWEEN :start_date AND :end_date";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

if ($id_jenis_kue) {
    $sql .= " AND r.id_jenis_kue = :id_jenis_kue";
    $params[':id_jenis_kue'] = $id_jenis_kue;
}

$sql .= " ORDER BY p.tanggal_produksi DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$produksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total produksi
$total_kue = array_sum(array_column($produksi, 'total_kue'));

// Include TCPDF library
require_once('../../vendor/autoload.php');

// Buat PDF baru dengan ukuran A4 portrait
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set dokumen informasi
$pdf->SetCreator('Sistem Kue');
$pdf->SetAuthor('Sistem Kue');
$pdf->SetTitle('Laporan Produksi');
$pdf->SetSubject('Laporan Produksi');

// Set margins
$pdf->SetMargins(15, 25, 15); // kiri, atas, kanan
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(true, 25);

// Tambah halaman
$pdf->AddPage();

// Fungsi untuk membuat header/kop
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
$pdf->Cell(0, 6, 'LAPORAN PRODUKSI', 0, 1, 'C');
$pdf->Ln(5);

// Informasi filter
$pdf->SetFont('helvetica', '', 10);

// Periode laporan
$pdf->Cell(40, 5, 'Periode Laporan:', 0, 0);
$pdf->Cell(0, 5, tgl_indo($start_date) . ' s/d ' . tgl_indo($end_date), 0, 1);

// Filter jenis kue jika ada
if ($id_jenis_kue) {
    $stmt = $db->prepare("SELECT nama_kue FROM jenis_kue WHERE id_jenis_kue = ?");
    $stmt->execute([$id_jenis_kue]);
    $nama_kue = $stmt->fetchColumn();

    $pdf->Cell(40, 5, 'Jenis Kue:', 0, 0);
    $pdf->Cell(0, 5, $nama_kue, 0, 1);
}

$pdf->Ln(5);

// Total produksi
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'Total Produksi:', 0, 0);
$pdf->Cell(0, 5, number_format($total_kue, 0, ',', '.') . ' pcs', 0, 1);
$pdf->Ln(5);

// Header tabel
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(10, 7, 'No', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Tanggal', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Jenis Kue', 1, 0, 'C', 1);
// $pdf->Cell(40, 7, 'Resep', 1, 0, 'C', 1);
$pdf->Cell(20, 7, 'Batch', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Total Kue', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Operator', 1, 1, 'C', 1);

// Isi tabel
$pdf->SetFont('helvetica', '', 9);
foreach ($produksi as $i => $row) {
    $pdf->Cell(10, 6, $i + 1, 1, 0, 'C');
    $pdf->Cell(25, 6, tgl_indo($row['tanggal_produksi']), 1, 0);
    $pdf->Cell(50, 6, $row['nama_kue'], 1, 0);
    // $pdf->Cell(40, 6, $row['nama_resep'], 1, 0);
    $pdf->Cell(20, 6, $row['jumlah_batch'], 1, 0, 'C');
    $pdf->Cell(25, 6, $row['total_kue'], 1, 0, 'C');
    $pdf->Cell(50, 6, $row['operator'], 1, 1);
}

// Output PDF
$pdf->Output('Laporan_Produksi_' . date('Ymd_His') . '.pdf', 'I');
