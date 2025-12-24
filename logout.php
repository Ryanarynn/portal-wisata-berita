<?php
/**
 * =====================================================
 * LOGOUT
 * Portal Wisata & Berita Kota
 * =====================================================
 */

session_start();
session_destroy();
header('Location: index.php');
exit;
?>