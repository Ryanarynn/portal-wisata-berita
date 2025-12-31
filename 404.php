<?php
/**
 * =====================================================
 * 404.PHP - Page Not Found
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            text-align: center;
            padding: 2rem;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 1rem;
            text-shadow: 4px 4px 0 rgba(0, 0, 0, 0.2);
        }

        .error-title {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .error-text {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <div class="error-page">
        <div>
            <div class="error-code">404</div>
            <h1 class="error-title">Halaman Tidak Ditemukan</h1>
            <p class="error-text">
                Maaf, halaman yang Anda cari tidak ada atau sudah dipindahkan.
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="index.php" class="btn btn-light btn-lg">
                    <i class="bi bi-house me-1"></i> Beranda
                </a>
                <a href="javascript:history.back()" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</body>

</html>