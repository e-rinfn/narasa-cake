<?php

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

function cetakStrukPenjualan($penjualan, $detail)
{
    try {
        // Ganti nama printer sesuai yang terinstal di sistem (Cek di Control Panel > Printers)

        // $connector = new WindowsPrintConnector("POS-58");
        $connector = new FilePrintConnector("struk.txt");
        $printer = new Printer($connector);

        // Header
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("NARASA CAKE & BAKERY\n");
        $printer->setEmphasis(false);
        $printer->text("Jl. Raya Pagerageung No.182\n");
        $printer->text("Tasikmalaya\n");
        $printer->feed();

        // Informasi Transaksi
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("No. Bon: " . str_pad($penjualan['id_penjualan'], 6, '0', STR_PAD_LEFT) . "\n");
        $printer->text("Tanggal : " . formatTanggalIndo($penjualan['tanggal_penjualan']) . "\n");
        $printer->text("Pelanggan: " . ($penjualan['nama_pelanggan'] ?? 'Umum') . "\n");
        if (!empty($penjualan['no_telepon'])) {
            $printer->text("Telp: " . $penjualan['no_telepon'] . "\n");
        }
        $printer->text("Kasir : " . $penjualan['nama_admin'] . "\n");
        $printer->text("--------------------------------\n");

        // Detail Produk
        foreach ($detail as $row) {
            $nama = substr($row['nama_kue'], 0, 20); // Potong nama panjang
            $printer->text("$nama\n");
            $printer->text(" {$row['jumlah']} x " . rupiah($row['harga_satuan']) . " = " . rupiah($row['subtotal']) . "\n");
        }

        // Total
        $printer->text("--------------------------------\n");
        $printer->setEmphasis(true);
        $printer->text("TOTAL: " . rupiah($penjualan['total_harga']) . "\n");
        $printer->setEmphasis(false);

        if (!empty($penjualan['catatan'])) {
            $printer->text("Catatan: " . $penjualan['catatan'] . "\n");
        }

        // Footer
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Terima kasih atas kunjungan Anda\n");

        $printer->feed(3);
        $printer->cut();
        $printer->close();
    } catch (Exception $e) {
        echo "Gagal mencetak: " . $e->getMessage();
    }
}
