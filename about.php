<?php
/**
 * =====================================================
 * ABOUT.PHP - About Us Page
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Get stats
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM articles WHERE status = 'published'");
$stats['articles'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['authors'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT SUM(views) as total FROM articles");
$stats['views'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as count FROM subscribers WHERE status = 'active'");
$stats['subscribers'] = $result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tentang <?php echo SITE_NAME; ?> - Portal berita dan wisata terpercaya">
    <title>Tentang Kami - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .about-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 5rem 0;
            color: #fff;
            text-align: center;
        }

        .about-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .about-hero p {
            color: rgba(255, 255, 255, 0.8);
            max-width: 600px;
            margin: 0 auto;
        }

        .stats-section {
            margin-top: -3rem;
            position: relative;
            z-index: 10;
        }

        .stat-box {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .stat-box .value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
        }

        .stat-box .label {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .value-card {
            background: var(--bg-light);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            height: 100%;
        }

        .value-card .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            margin: 0 auto 1rem;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero -->
    <section class="about-hero">
        <div class="container">
            <h1>Tentang Kami</h1>
            <p>Portal berita dan wisata terpercaya yang menyajikan informasi akurat dan terkini untuk masyarakat
                Indonesia.</p>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-box">
                        <div class="value"><?php echo number_format($stats['articles']); ?></div>
                        <div class="label">Artikel Terbit</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-box">
                        <div class="value"><?php echo $stats['authors']; ?></div>
                        <div class="label">Penulis Aktif</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-box">
                        <div class="value"><?php echo formatViews($stats['views']); ?></div>
                        <div class="label">Total View</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-box">
                        <div class="value"><?php echo number_format($stats['subscribers']); ?></div>
                        <div class="label">Subscriber</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Content -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="bg-white rounded-4 p-4 p-md-5 shadow-sm">
                        <h2 class="mb-4">Visi & Misi</h2>
                        <p class="lead text-muted mb-4">
                            Menjadi portal berita dan wisata terdepan yang menyajikan informasi berkualitas, akurat, dan
                            bermanfaat bagi seluruh lapisan masyarakat Indonesia.
                        </p>

                        <h3 class="h5 mb-3">Misi Kami:</h3>
                        <ul class="mb-4">
                            <li>Menyajikan berita terkini dengan jurnalisme berkualitas</li>
                            <li>Memperkenalkan keindahan wisata Indonesia ke dunia</li>
                            <li>Memberikan informasi yang akurat dan terpercaya</li>
                            <li>Mendukung pengembangan pariwisata lokal</li>
                        </ul>

                        <h3 class="h5 mb-3">Tim Kami</h3>
                        <p class="text-muted">
                            Kami adalah tim profesional yang berdedikasi dalam menyajikan konten berkualitas. Dengan
                            pengalaman di bidang jurnalisme dan travel, kami berkomitmen memberikan yang terbaik untuk
                            pembaca.
                        </p>

                        <div class="text-center mt-4">
                            <a href="authors.php" class="btn btn-primary">
                                <i class="bi bi-people me-1"></i> Lihat Tim Penulis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Nilai-Nilai Kami</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="icon"><i class="bi bi-shield-check"></i></div>
                        <h5>Integritas</h5>
                        <p class="text-muted small mb-0">Menyajikan fakta dan informasi yang dapat dipertanggungjawabkan
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="icon"><i class="bi bi-lightning"></i></div>
                        <h5>Kecepatan</h5>
                        <p class="text-muted small mb-0">Selalu update dengan berita terkini dan tercepat</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="icon"><i class="bi bi-heart"></i></div>
                        <h5>Dedikasi</h5>
                        <p class="text-muted small mb-0">Berkomitmen memberikan yang terbaik untuk pembaca</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>

</html>