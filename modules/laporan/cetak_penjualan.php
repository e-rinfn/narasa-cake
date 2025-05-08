<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Ambil parameter filter dari URL
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$id_pelanggan = $_GET['id_pelanggan'] ?? null;
$id_jenis_kue = $_GET['id_jenis_kue'] ?? null;

// Query
$sql = "SELECT p.id_penjualan, p.tanggal_penjualan, 
               pl.nama_pelanggan, 
               COUNT(d.id_detail_penjualan) as jumlah_item,
               SUM(d.jumlah) as total_kue,
               p.total_bayar
        FROM penjualan p
        LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
        JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan
        WHERE p.tanggal_penjualan BETWEEN :start_date AND :end_date
        AND p.status_pembayaran = 'lunas'";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

if ($id_pelanggan) {
    $sql .= " AND p.id_pelanggan = :id_pelanggan";
    $params[':id_pelanggan'] = $id_pelanggan;
}

if ($id_jenis_kue) {
    $sql .= " AND d.id_jenis_kue = :id_jenis_kue";
    $params[':id_jenis_kue'] = $id_jenis_kue;
}

$sql .= " GROUP BY p.id_penjualan ORDER BY p.tanggal_penjualan DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$penjualan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total
$total_penjualan = array_sum(array_column($penjualan, 'total_bayar'));

// Include TCPDF
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

// Fungsi kop
function buatKop($pdf)
{
    $logoPath = '../../assets/images/logo.png';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 20, 30, 20, 0, 'PNG');
    }

    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetY(10);
    $pdf->Cell(0, 15, 'NARASA CAKE & BAKERY', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Jl. Raya Pagerageung No.182, Sukadana, Kec. Pagerageung', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Kab. Tasikmalaya, Jawa Barat 46413', 0, 1, 'C');

    // Beri jarak setelah kop
    $pdf->Ln(10);
}
buatKop($pdf);

// Judul
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'LAPORAN PENJUALAN', 0, 1, 'C');
$pdf->Ln(5);

// Filter info
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 5, 'Periode Laporan:', 0, 0);
$pdf->Cell(0, 5, tgl_indo($start_date) . ' s/d ' . tgl_indo($end_date), 0, 1);

if ($id_pelanggan) {
    $stmt = $db->prepare("SELECT nama_pelanggan FROM pelanggan WHERE id_pelanggan = ?");
    $stmt->execute([$id_pelanggan]);
    $nama_pelanggan = $stmt->fetchColumn();
    $pdf->Cell(40, 5, 'Pelanggan:', 0, 0);
    $pdf->Cell(0, 5, $nama_pelanggan, 0, 1);
}

if ($id_jenis_kue) {
    $stmt = $db->prepare("SELECT nama_kue FROM jenis_kue WHERE id_jenis_kue = ?");
    $stmt->execute([$id_jenis_kue]);
    $nama_kue = $stmt->fetchColumn();
    $pdf->Cell(40, 5, 'Jenis Kue:', 0, 0);
    $pdf->Cell(0, 5, $nama_kue, 0, 1);
}

$pdf->Ln(5);

// Total Penjualan
// $pdf->SetFont('helvetica', 'B', 10);
// $pdf->Cell(40, 5, 'Total Penjualan:', 0, 0);
// $pdf->Cell(0, 5, rupiah($total_penjualan), 0, 1);
// $pdf->Ln(5);

// Total Penjualan dan Total Kue (horizontal)
$pdf->SetFont('helvetica', 'B', 10);

$total_kue_semua = array_sum(array_column($penjualan, 'total_kue'));

// Total Penjualan
$pdf->Cell(40, 5, 'Total Penjualan:', 0, 0);
$pdf->Cell(55, 5, rupiah($total_penjualan), 0, 0);

// Total Kue Terjual
$pdf->Cell(40, 5, 'Total Penjualan Kue:', 0, 0);
$pdf->Cell(0, 5, number_format($total_kue_semua, 0, ',', '.') . ' pcs', 0, 1);

$pdf->Ln(5);

// Tabel
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(10, 7, 'No', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Tanggal', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Pelanggan', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Jenis Item', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Total Kue', 1, 0, 'C', 1);
$pdf->Cell(40, 7, 'Total Bayar', 1, 1, 'C', 1);

// Isi data
$pdf->SetFont('helvetica', '', 9);
foreach ($penjualan as $i => $row) {
    $pdf->Cell(10, 6, $i + 1, 1, 0, 'C');
    $pdf->Cell(30, 6, date('d/m/Y H:i', strtotime($row['tanggal_penjualan'])), 1, 0, 'C');
    $pdf->Cell(50, 6, $row['nama_pelanggan'] ?: 'Umum', 1, 0);
    $pdf->Cell(25, 6, $row['jumlah_item'] . ' jenis', 1, 0, 'C');
    $pdf->Cell(25, 6, $row['total_kue']  . ' pcs', 1, 0, 'C');
    $pdf->Cell(40, 6, rupiah($row['total_bayar']), 1, 1, 'R');
}


// Output
$pdf->Output('Laporan_Penjualan_' . date('Ymd_His') . '.pdf', 'I');
