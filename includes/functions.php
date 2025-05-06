<?php
require_once __DIR__ . '/../config/config.php';

// Format tanggal Indonesia
function tgl_indo($date)
{
    if (empty($date)) return '-';

    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $pecah = explode('-', $date);
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

// Untuk format tanggal di PDF
function formatTanggalIndo($datetime)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $timestamp = strtotime($datetime);
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);
    return "$tgl $bln $thn $jam";
}



// Format Rupiah
function rupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function redirectWithMessage(string $url, string $type, string $message): void
{
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash_message'] = ['type' => $type, 'text' => $message];
    header("Location: $url");
    exit();
}

function displayMessage(): void
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (isset($_SESSION['flash_message'])) {
        $type = htmlspecialchars($_SESSION['flash_message']['type']);
        $text = htmlspecialchars($_SESSION['flash_message']['text']);
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $text
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        unset($_SESSION['flash_message']);
    }
}

// Get nama bahan
function getBahanName($db, $id_bahan)
{
    $stmt = $db->prepare("SELECT nama_bahan FROM bahan_baku WHERE id_bahan = ?");
    $stmt->execute([$id_bahan]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nama_bahan'] : 'Unknown';
}

// Get jenis kue dari stok
function getJenisKueFromStok($db, $id_stok_kue)
{
    $stmt = $db->prepare("SELECT id_jenis_kue FROM stok_kue WHERE id_stok_kue = ?");
    $stmt->execute([$id_stok_kue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id_jenis_kue'] : null;
}

// Get harga kue
function getHargaKue($db, $id_stok_kue)
{
    $stmt = $db->prepare("SELECT harga_jual FROM jenis_kue k 
                         JOIN stok_kue s ON k.id_jenis_kue = s.id_jenis_kue 
                         WHERE s.id_stok_kue = ?");
    $stmt->execute([$id_stok_kue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['harga_jual'] : 0;
}

// Hitung total stok bahan
function getTotalStokBahan($db, $id_bahan)
{
    $stmt = $db->prepare("SELECT SUM(jumlah) as total FROM stok_bahan WHERE id_bahan = ?");
    $stmt->execute([$id_bahan]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['total'] : 0;
}


/**
 * Mendapatkan total stok bahan
 */
function getStokBahan($db, $id_bahan)
{
    $stmt = $db->prepare("SELECT SUM(jumlah) FROM stok_bahan WHERE id_bahan = ?");
    $stmt->execute([$id_bahan]);
    return $stmt->fetchColumn() ?: 0;
}
