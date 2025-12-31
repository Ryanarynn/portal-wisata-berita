<?php
/**
 * =====================================================
 * KONFIGURASI DATABASE
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * INSTRUKSI:
 * 1. Salin file ini menjadi 'config.php'
 * 2. Ubah nilai DB_USER dan DB_PASS sesuai dengan konfigurasi database Anda
 * 3. Pastikan database sudah dibuat dengan nama yang sesuai
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Ganti dengan username database Anda
define('DB_PASS', '');              // Ganti dengan password database Anda
define('DB_NAME', 'portal_wisata_berita');

// Konfigurasi Website
define('SITE_NAME', 'Portal Wisata & Berita Kota');
define('SITE_URL', 'http://localhost/portal-wisata-berita/');

// Koneksi Database menggunakan MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset ke utf8
$conn->set_charset("utf8");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>