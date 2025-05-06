<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Ambil data stok bahan (sama dengan halaman utama)
$stmt = $db->query("SELECT b.id_bahan, b.nama_bahan, 
                   k.nama_kategori, s.nama_satuan,
                   b.stok_minimal, b.harga_per_satuan,
                   COALESCE(SUM(sb.jumlah), 0) as stok_aktual
                   FROM bahan_baku b
                   JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                   JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                   LEFT JOIN stok_bahan sb ON b.id_bahan = sb.id_bahan
                   GROUP BY b.id_bahan
                   ORDER BY b.nama_bahan");
$bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total nilai stok dan bahan kurang
$total_nilai = 0;
$total_bahan_kurang = 0;
foreach ($bahan as $item) {
    $total_nilai += $item['stok_aktual'] * $item['harga_per_satuan'];
    if ($item['stok_aktual'] < $item['stok_minimal']) {
        $total_bahan_kurang++;
    }
}

// Include TCPDF library
require_once('../../vendor/autoload.php');

// Buat PDF baru dengan ukuran A4 landscape (karena tabel lebar)
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
$pdf->Cell(0, 6, 'LAPORAN STOK BAHAN BAKU', 0, 1, 'C');
$pdf->Ln(5);

// Informasi ringkasan
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Tanggal Cetak: ' . date('d/m/Y H:i:s'), 0, 1);
$pdf->Cell(0, 5, 'Total Jenis Bahan: ' . count($bahan), 0, 1);
$pdf->Cell(0, 5, 'Total Bahan Kurang: ' . $total_bahan_kurang, 0, 1);
$pdf->Cell(0, 5, 'Total Nilai Stok: ' . rupiah($total_nilai), 0, 1);
$pdf->Ln(8);

// Header tabel
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(10, 7, 'No', 1, 0, 'C', 1);
$pdf->Cell(40, 7, 'Nama Bahan', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Kategori', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Stok', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Minimal', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Satuan', 1, 0, 'C', 1);
// $pdf->Cell(30, 7, 'Harga Satuan', 1, 0, 'C', 1);
// $pdf->Cell(30, 7, 'Total Nilai', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Status', 1, 1, 'C', 1);

// Isi tabel
$pdf->SetFont('helvetica', '', 8);
foreach ($bahan as $i => $row) {
    $status = $row['stok_aktual'] < $row['stok_minimal'] ? 'danger' : 'success';
    $status_text = $row['stok_aktual'] < $row['stok_minimal'] ? 'Kurang' : 'Aman';
    $total_nilai_bahan = $row['stok_aktual'] * $row['harga_per_satuan'];

    $pdf->Cell(10, 6, $i + 1, 1, 0, 'C');
    $pdf->Cell(40, 6, $row['nama_bahan'], 1, 0);
    $pdf->Cell(30, 6, $row['nama_kategori'], 1, 0);
    $pdf->Cell(25, 6, $row['stok_aktual'], 1, 0, 'C');
    $pdf->Cell(25, 6, $row['stok_minimal'], 1, 0, 'C');
    $pdf->Cell(25, 6, $row['nama_satuan'], 1, 0, 'L');
    // $pdf->Cell(30, 6, rupiah($row['harga_per_satuan']), 1, 0, 'R');
    // $pdf->Cell(30, 6, rupiah($total_nilai_bahan), 1, 0, 'R');

    // Warna status
    if ($status == 'danger') {
        $pdf->SetTextColor(255, 0, 0); // Merah untuk status kurang
    } else {
        $pdf->SetTextColor(0, 128, 0); // Hijau untuk status aman
    }
    $pdf->Cell(25, 6, $status_text, 1, 1, 'C');
    $pdf->SetTextColor(0, 0, 0); // Kembalikan ke warna hitam
}

// Output PDF
$pdf->Output('Laporan_Bahan_Baku_' . date('Ymd_His') . '.pdf', 'I');
