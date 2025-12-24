<?php
/**
 * =====================================================
 * KONFIGURASI DATABASE
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * CATATAN: File ini berisi konfigurasi koneksi database.
 * Sesuaikan nilai-nilai di bawah dengan pengaturan 
 * database Anda.
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'portal_wisata_berita');

// Konfigurasi Website
define('SITE_NAME', 'Portal Wisata & Berita Kota');
define('SITE_URL', 'http://localhost/portal-wisata-berita');

// Koneksi Database menggunakan MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset ke utf8
$conn->set_charset("utf8");

// Fungsi helper untuk format tanggal Indonesia
function formatTanggal($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($tanggal);
    return date('d', $timestamp) . ' ' . $bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
}

// Fungsi untuk membuat excerpt
function createExcerpt($text, $length = 150) {
    $text = strip_tags($text);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Start session
session_start();
?>
