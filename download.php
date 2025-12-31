<?php
/**
 * =====================================================
 * DOWNLOAD.PHP - File Download Handler
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: Path Traversal (CWE-22)
 * File parameter tidak di-sanitasi, memungkinkan akses ke file sistem
 * 
 * POC:
 * - download.php?file=../config.php
 * - download.php?file=../../../etc/passwd (Linux)
 * - download.php?file=..\..\..\..\windows\system32\drivers\etc\hosts (Windows)
 */

require_once 'config.php';

// Cek parameter file
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("Error: File parameter required");
}

$requestedFile = $_GET['file'];

// VULNERABILITY: Path Traversal - Tidak ada sanitasi ../
// Seharusnya: $requestedFile = basename($requestedFile);
// Atau menggunakan realpath() untuk validasi

$uploadDir = 'uploads/';
$filePath = $uploadDir . $requestedFile;

// VULNERABILITY: Tidak memvalidasi apakah file berada di dalam uploads directory
// Seharusnya:
// $realPath = realpath($filePath);
// $realUploadDir = realpath($uploadDir);
// if (strpos($realPath, $realUploadDir) !== 0) { die("Access denied"); }

// Debug mode aktif - Information Disclosure
if (getSetting($conn, 'debug_mode') == '1') {
    // VULNERABILITY: Information Disclosure - menampilkan path lengkap
    error_log("Download attempt: " . $filePath . " from IP: " . $_SERVER['REMOTE_ADDR']);
}

// Check if file exists
if (!file_exists($filePath)) {
    // VULNERABILITY: Information Disclosure - menampilkan path yang dicari
    if (getSetting($conn, 'show_sql_errors') == '1') {
        die("Error: File not found at path: " . $filePath);
    } else {
        die("Error: File not found");
    }
}

// Get file info
$fileSize = filesize($filePath);
$fileName = basename($filePath);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Mime types
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'zip' => 'application/zip',
    'txt' => 'text/plain',
    'php' => 'text/plain',  // VULNERABILITY: Allows reading PHP source code
    'sql' => 'text/plain',  // VULNERABILITY: Allows reading SQL files
    'bak' => 'text/plain',  // VULNERABILITY: Allows reading backup files
];

$mimeType = isset($mimeTypes[$fileExt]) ? $mimeTypes[$fileExt] : 'application/octet-stream';

// Log download activity
logActivity($conn, 'file_download', "Downloaded: $requestedFile, Path: $filePath");

// Send headers
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output file
readfile($filePath);
exit;
