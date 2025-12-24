<?php
/**
 * =====================================================
 * HALAMAN UTAMA (HOME)
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * Halaman ini menampilkan daftar berita dan destinasi
 * wisata. Dapat diakses publik tanpa login.
 */

require_once 'config.php';

// Ambil berita terbaru
$queryBerita = "SELECT a.*, c.name as category_name, u.full_name as author_name 
                FROM articles a 
                LEFT JOIN categories c ON a.category_id = c.id 
                LEFT JOIN users u ON a.author_id = u.id 
                WHERE a.type = 'berita' AND a.status = 'published' 
                ORDER BY a.created_at DESC LIMIT 6";
$resultBerita = $conn->query($queryBerita);

// Ambil destinasi wisata
$queryWisata = "SELECT a.*, c.name as category_name, u.full_name as author_name 
                FROM articles a 
                LEFT JOIN categories c ON a.category_id = c.id 
                LEFT JOIN users u ON a.author_id = u.id 
                WHERE a.type = 'wisata' AND a.status = 'published' 
                ORDER BY a.views DESC LIMIT 6";
$resultWisata = $conn->query($queryWisata);

// Ambil headline untuk hero section
$queryHeadline = "SELECT a.*, c.name as category_name 
                  FROM articles a 
                  LEFT JOIN categories c ON a.category_id = c.id 
                  WHERE a.status = 'published' 
                  ORDER BY a.views DESC LIMIT 1";
$resultHeadline = $conn->query($queryHeadline);
$headline = $resultHeadline->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description"
        content="Portal Wisata & Berita Kota - Sumber informasi terpercaya untuk berita terkini dan destinasi wisata">
    <title><?php echo SITE_NAME; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-dark: #152a45;
            --secondary: #047857;
            --accent: #0ea5e9;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            font-size: 1rem;
            overflow-x: hidden;
        }

        /* ===== NAVBAR - Mobile First ===== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0.75rem 0;
            transition: all 0.3s ease;
            background: transparent;
        }

        .navbar.scrolled {
            background: var(--bg-white);
            box-shadow: var(--shadow);
            padding: 0.5rem 0;
        }

        .navbar.scrolled .navbar-brand {
            color: var(--text-dark) !important;
        }

        .navbar.scrolled .navbar-toggler-icon {
            filter: invert(1);
        }

        /* Keep nav-link white inside collapsed menu on mobile */
        .navbar-collapse .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .navbar-collapse .nav-link:hover {
            color: #fff !important;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.125rem;
            color: #fff !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
            flex-shrink: 0;
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-collapse {
            background: var(--primary-dark);
            margin-top: 1rem;
            border-radius: 12px;
            padding: 1rem;
        }

        .nav-link {
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 0.75rem 1rem !important;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff !important;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            color: #fff !important;
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: #fff !important;
        }

        /* ===== HERO SECTION - Mobile First ===== */
        .hero-section {
            min-height: 100vh;
            min-height: 100dvh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 50%, #0f172a 100%);
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
            padding: 6rem 0 4rem;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">🏛️</text></svg>') center/cover no-repeat;
            opacity: 0.05;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(to top, var(--bg-light), transparent);
        }

        .hero-content {
            position: relative;
            z-index: 10;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0.875rem;
            border-radius: 50px;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-badge i {
            color: var(--accent);
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
            margin-bottom: 1rem;
        }

        .hero-description {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .hero-cta {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-hero-primary,
        .btn-hero-secondary {
            padding: 0.875rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            border: none;
            color: #fff;
        }

        .btn-hero-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(4, 120, 87, 0.4);
            color: #fff;
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        /* Search Box - Mobile First */
        .hero-search {
            margin-top: 2rem;
        }

        .search-wrapper {
            display: flex;
            flex-direction: column;
            background: var(--bg-white);
            border-radius: 12px;
            padding: 0.5rem;
            box-shadow: var(--shadow-xl);
            gap: 0.5rem;
        }

        .search-wrapper input {
            width: 100%;
            border: none;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            background: transparent;
            outline: none;
            border-radius: 8px;
        }

        .search-wrapper button {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 0.875rem 1.25rem;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .search-wrapper button:hover {
            transform: scale(1.02);
        }

        /* ===== SECTION STYLING - Mobile First ===== */
        .section {
            padding: 3rem 0;
        }

        .section-header {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(to bottom, var(--secondary), var(--accent));
            border-radius: 4px;
            flex-shrink: 0;
        }

        .view-all-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }

        .view-all-link:hover {
            color: var(--secondary);
            gap: 0.75rem;
        }

        /* ===== NEWS CARD - Mobile First ===== */
        .news-card {
            background: var(--bg-white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .news-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .news-card-image {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .news-card-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .news-card-image-placeholder.wisata {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
        }

        .news-card-image-placeholder i {
            font-size: 2.5rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .news-card-category {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            background: var(--bg-white);
            color: var(--primary);
            font-size: 0.625rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .news-card-body {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .news-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-card-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .news-card-title a:hover {
            color: var(--secondary);
        }

        .news-card-excerpt {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .news-card-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* ===== FEATURED CARD ===== */
        .featured-card {
            background: var(--bg-white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .featured-card-image {
            height: 250px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .featured-card-image i {
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.3);
        }

        /* ===== ABOUT SECTION ===== */
        .about-features {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .about-feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .about-feature-icon {
            padding: 0.75rem;
            border-radius: 10px;
            flex-shrink: 0;
        }

        .about-feature-text h5 {
            font-size: 1rem;
            margin-bottom: 0;
        }

        .about-feature-text small {
            font-size: 0.75rem;
        }

        /* ===== FOOTER - Mobile First ===== */
        .footer {
            background: var(--primary-dark);
            color: #fff;
            padding: 3rem 0 1.5rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .footer-brand .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .footer-brand span {
            font-size: 1.125rem;
            font-weight: 800;
        }

        .footer-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .footer-title {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-links a:hover {
            color: var(--accent);
            padding-left: 0.25rem;
        }

        .footer-contact {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-contact li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
        }

        .footer-contact i {
            color: var(--accent);
            font-size: 1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */

        /* Small devices (landscape phones, 576px and up) */
        @media (min-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-cta {
                flex-direction: row;
            }

            .btn-hero-primary,
            .btn-hero-secondary {
                width: auto;
            }

            .search-wrapper {
                flex-direction: row;
                gap: 0;
            }

            .search-wrapper button {
                width: auto;
            }

            .news-card-category {
                font-size: 0.6875rem;
                padding: 0.375rem 0.625rem;
            }

            .about-features {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Medium devices (tablets, 768px and up) */
        @media (min-width: 768px) {
            .navbar-brand {
                font-size: 1.25rem;
            }

            .navbar-brand .logo-icon {
                width: 40px;
                height: 40px;
                font-size: 1.125rem;
            }

            .hero-section {
                padding: 8rem 0 5rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-description {
                font-size: 1.125rem;
            }

            .section {
                padding: 4rem 0;
            }

            .section-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .section-title {
                font-size: 1.75rem;
            }

            .news-card-image {
                height: 200px;
            }

            .news-card-body {
                padding: 1.25rem;
            }

            .news-card-title {
                font-size: 1.0625rem;
            }

            .news-card-excerpt {
                -webkit-line-clamp: 3;
            }

            .featured-card-image {
                height: 350px;
            }
        }

        /* Large devices (desktops, 992px and up) */
        @media (min-width: 992px) {
            .navbar {
                padding: 1rem 0;
            }

            .navbar.scrolled {
                padding: 0.75rem 0;
            }

            .navbar-collapse {
                background: transparent;
                margin-top: 0;
                padding: 0;
            }

            .navbar.scrolled .navbar-collapse {
                background: transparent;
            }

            .nav-link {
                \r padding: 0.5rem 1rem !important;
                \r color: rgba(255, 255, 255, 0.9) !important;
                \r
            }

            \r \r .navbar.scrolled .nav-link {
                \r color: var(--text-dark) !important;
                \r
            }

            \r \r .navbar.scrolled .nav-link:hover {
                \r color: var(--secondary) !important;
                \r
            }

            \r \r .nav-link:hover {
                \r background: transparent;
                \r
            }

            \r \r .btn-login {
                \r width: auto;
                \r margin-top: 0;
            }

            .navbar-brand {
                font-size: 1.5rem;
            }

            .hero-title {
                font-size: 3rem;
            }

            .hero-search {
                max-width: 600px;
            }

            .section {
                padding: 5rem 0;
            }

            .section-title {
                font-size: 2rem;
            }

            .section-title::before {
                height: 32px;
            }

            .news-card:hover {
                transform: translateY(-8px);
                box-shadow: var(--shadow-xl);
            }

            .news-card-category {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
                border-radius: 6px;
            }

            .news-card-body {
                padding: 1.5rem;
            }

            .news-card-title {
                font-size: 1.125rem;
            }

            .news-card-meta {
                font-size: 0.8125rem;
            }

            .featured-card-image {
                height: 400px;
            }

            .featured-card-image i {
                font-size: 6rem;
            }
        }

        /* X-Large devices (large desktops, 1200px and up) */
        @media (min-width: 1200px) {
            .hero-title {
                font-size: 3.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="logo-icon">
                    <i class="bi bi-globe-asia-australia"></i>
                </div>
                <span class="d-none d-sm-inline"><?php echo SITE_NAME; ?></span>
                <span class="d-sm-none">Portal Wisata</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#wisata">Destinasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#berita">Berita</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tentang">Tentang</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-login" href="logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-login" href="login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-8 col-xl-7">
                        <div class="hero-badge">
                            <i class="bi bi-lightning-fill"></i>
                            <?php if ($headline): ?>
                                <span class="text-truncate"><?php echo htmlspecialchars($headline['category_name']); ?> —
                                    Berita Terpopuler</span>
                            <?php else: ?>
                                Portal Informasi Terpercaya
                            <?php endif; ?>
                        </div>
                        <h1 class="hero-title">
                            <?php if ($headline): ?>
                                <?php echo htmlspecialchars($headline['title']); ?>
                            <?php else: ?>
                                Jelajahi Keindahan & Informasi Kota Kami
                            <?php endif; ?>
                        </h1>
                        <p class="hero-description">
                            <?php if ($headline): ?>
                                <?php echo createExcerpt($headline['content'], 150); ?>
                            <?php else: ?>
                                Temukan destinasi wisata menarik dan berita terkini dari kota kami.
                            <?php endif; ?>
                        </p>
                        <div class="hero-cta">
                            <?php if ($headline): ?>
                                <a href="view.php?id=<?php echo $headline['id']; ?>" class="btn-hero-primary">
                                    <i class="bi bi-arrow-right"></i>
                                    Baca Selengkapnya
                                </a>
                            <?php endif; ?>
                            <a href="#wisata" class="btn-hero-secondary">
                                <i class="bi bi-geo-alt"></i>
                                Jelajahi Destinasi
                            </a>
                        </div>

                        <!-- Search Box -->
                        <div class="hero-search">
                            <form action="search.php" method="GET" class="search-wrapper">
                                <input type="text" name="q" placeholder="Cari berita atau destinasi wisata..." required>
                                <button type="submit">
                                    <i class="bi bi-search"></i>
                                    <span>Cari</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Berita Section -->
    <section id="berita" class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Berita Terkini</h2>
                <a href="search.php?q=" class="view-all-link">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="row g-3 g-md-4">
                <?php if ($resultBerita && $resultBerita->num_rows > 0): ?>
                    <?php while ($berita = $resultBerita->fetch_assoc()): ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <article class="news-card">
                                <div class="news-card-image">
                                    <div class="news-card-image-placeholder">
                                        <i class="bi bi-newspaper"></i>
                                    </div>
                                    <span
                                        class="news-card-category"><?php echo htmlspecialchars($berita['category_name']); ?></span>
                                </div>
                                <div class="news-card-body">
                                    <h3 class="news-card-title">
                                        <a href="view.php?id=<?php echo $berita['id']; ?>">
                                            <?php echo htmlspecialchars($berita['title']); ?>
                                        </a>
                                    </h3>
                                    <p class="news-card-excerpt">
                                        <?php echo createExcerpt($berita['content'], 100); ?>
                                    </p>
                                    <div class="news-card-meta">
                                        <span>
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo formatTanggal($berita['created_at']); ?>
                                        </span>
                                        <span>
                                            <i class="bi bi-eye"></i>
                                            <?php echo $berita['views']; ?>
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-4">
                            <i class="bi bi-inbox d-block mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-0">Belum ada berita yang tersedia.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Wisata Section -->
    <section id="wisata" class="section" style="background: var(--bg-white);">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Destinasi Wisata Populer</h2>
                <a href="search.php?q=" class="view-all-link">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="row g-3 g-md-4">
                <?php if ($resultWisata && $resultWisata->num_rows > 0): ?>
                    <?php while ($wisata = $resultWisata->fetch_assoc()): ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <article class="news-card">
                                <div class="news-card-image">
                                    <div class="news-card-image-placeholder wisata">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </div>
                                    <span
                                        class="news-card-category"><?php echo htmlspecialchars($wisata['category_name']); ?></span>
                                </div>
                                <div class="news-card-body">
                                    <h3 class="news-card-title">
                                        <a href="view.php?id=<?php echo $wisata['id']; ?>">
                                            <?php echo htmlspecialchars($wisata['title']); ?>
                                        </a>
                                    </h3>
                                    <p class="news-card-excerpt">
                                        <?php echo createExcerpt($wisata['content'], 100); ?>
                                    </p>
                                    <div class="news-card-meta">
                                        <span>
                                            <i class="bi bi-person"></i>
                                            <?php echo htmlspecialchars($wisata['author_name']); ?>
                                        </span>
                                        <span>
                                            <i class="bi bi-eye"></i>
                                            <?php echo $wisata['views']; ?>
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-4">
                            <i class="bi bi-geo-alt d-block mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-0">Belum ada destinasi wisata yang tersedia.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="tentang" class="section">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-12 col-lg-6 order-2 order-lg-1">
                    <div class="featured-card">
                        <div class="featured-card-image">
                            <i class="bi bi-buildings"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6 order-1 order-lg-2">
                    <h2 class="section-title mb-3 mb-md-4">Tentang Portal Kami</h2>
                    <p class="lead text-muted mb-3" style="font-size: 1rem;">
                        Portal Wisata & Berita Kota adalah sumber informasi terpercaya untuk berita terkini dan
                        destinasi wisata menarik di kota kami.
                    </p>
                    <p class="text-muted mb-3" style="font-size: 0.9375rem;">
                        Kami berkomitmen untuk menyajikan informasi yang akurat, terkini, dan bermanfaat bagi warga kota
                        maupun wisatawan.
                    </p>
                    <div class="about-features">
                        <div class="about-feature-item">
                            <div class="about-feature-icon bg-primary bg-opacity-10">
                                <i class="bi bi-newspaper text-primary fs-5"></i>
                            </div>
                            <div class="about-feature-text">
                                <h5>Berita Akurat</h5>
                                <small class="text-muted">Terverifikasi</small>
                            </div>
                        </div>
                        <div class="about-feature-item">
                            <div class="about-feature-icon bg-success bg-opacity-10">
                                <i class="bi bi-geo-alt text-success fs-5"></i>
                            </div>
                            <div class="about-feature-text">
                                <h5>Wisata Lengkap</h5>
                                <small class="text-muted">Destinasi Terbaik</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <div class="footer-brand">
                        <div class="logo-icon">
                            <i class="bi bi-globe-asia-australia"></i>
                        </div>
                        <span>Portal Wisata & Berita</span>
                    </div>
                    <p class="footer-description">
                        Sumber informasi terpercaya untuk berita terkini dan destinasi wisata menarik di kota kami.
                    </p>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <h5 class="footer-title">Navigasi</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="#berita">Berita</a></li>
                        <li><a href="#wisata">Wisata</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <h5 class="footer-title">Kategori</h5>
                    <ul class="footer-links">
                        <li><a href="#">Politik</a></li>
                        <li><a href="#">Ekonomi</a></li>
                        <li><a href="#">Budaya</a></li>
                        <li><a href="#">Olahraga</a></li>
                    </ul>
                </div>
                <div class="col-12 col-md-4 col-lg-4">
                    <h5 class="footer-title">Kontak</h5>
                    <ul class="footer-contact">
                        <li>
                            <i class="bi bi-envelope"></i>
                            <span>info@portalwisata.com</span>
                        </li>
                        <li>
                            <i class="bi bi-telephone"></i>
                            <span>(021) 123-4567</span>
                        </li>
                        <li>
                            <i class="bi bi-geo-alt"></i>
                            <span>Jl. Contoh No. 123, Kota</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Navbar Scroll Effect -->
    <script>
        const navbar = document.getElementById('mainNavbar');
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Close mobile menu when clicking a link
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    new bootstrap.Collapse(navbarCollapse).hide();
                }
            });
        });
    </script>
</body>

</html>