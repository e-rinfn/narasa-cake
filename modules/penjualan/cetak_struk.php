<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/config.php'; // pastikan path benar
require 'print_helper.php';

checkAuth();

$id = $_GET['id'] ?? null;
if (!$id) {
    die('ID tidak ditemukan');
}

// Pastikan koneksi mysqli bernama $conn atau sesuai config.php kamu
if (!isset($conn)) {
    die('Database connection tidak tersedia');
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

// Ambil detail barang
// Ambil detail penjualan
$stmt = $db->prepare("SELECT dp.*, k.nama_kue 
                     FROM detail_penjualan dp
                     JOIN jenis_kue k ON dp.id_jenis_kue = k.id_jenis_kue
                     WHERE dp.id_penjualan = ?");
$stmt->execute([$id_penjualan]);
$detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cetak ke printer
cetakStrukPenjualan($penjualan, $detail);
