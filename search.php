<?php
/**
 * HALAMAN PENCARIAN (SEARCH)
 * Portal Wisata & Berita Kota
 */

require_once 'config.php';

// Ambil kata kunci dari parameter GET
$keyword = isset($_GET['q']) ? $_GET['q'] : '';

$results = [];
$totalResults = 0;

if (!empty($keyword)) {
    $searchKeyword = '%' . $keyword . '%';
    $stmt = $conn->prepare("SELECT a.*, c.name as category_name, u.full_name as author_name 
                            FROM articles a 
                            LEFT JOIN categories c ON a.category_id = c.id 
                            LEFT JOIN users u ON a.author_id = u.id 
                            WHERE (a.title LIKE ? OR a.content LIKE ?) 
                            AND a.status = 'published' 
                            ORDER BY a.created_at DESC");
    $stmt->bind_param("ss", $searchKeyword, $searchKeyword);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $totalResults = count($results);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Pencarian - <?php echo SITE_NAME; ?></title>
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
            overflow-x: hidden;
        }

        /* ===== NAVBAR - Mobile First ===== */
        .navbar {
            background: var(--bg-white);
            box-shadow: var(--shadow);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.125rem;
            color: var(--text-dark) !important;
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
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-collapse {
            margin-top: 0.75rem;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-dark) !important;
            padding: 0.625rem 0.75rem !important;
            transition: color 0.3s ease;
            font-size: 0.9375rem;
        }

        .nav-link:hover {
            color: var(--secondary) !important;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            color: #fff !important;
            font-size: 0.875rem;
            width: 100%;
            text-align: center;
            margin-top: 0.5rem;
        }

        /* ===== SEARCH HEADER - Mobile First ===== */
        .search-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2.5rem 0;
            position: relative;
            overflow: hidden;
        }

        .search-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .search-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .search-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9375rem;
        }

        /* Search Box - Mobile First */
        .search-wrapper {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 0.375rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
            max-width: 700px;
            margin: 1.5rem auto 0;
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
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
        }

        .search-wrapper button:hover {
            transform: scale(1.02);
        }

        /* ===== MAIN CONTENT - Mobile First ===== */
        .main-content {
            padding: 1.5rem 0 3rem;
        }

        /* Search Results Info */
        .search-info {
            background: var(--bg-white);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .search-info-text {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .search-info-text strong {
            color: var(--text-dark);
        }

        .search-keyword {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8125rem;
            display: inline-block;
            word-break: break-all;
        }

        .result-count {
            background: var(--bg-light);
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8125rem;
            color: var(--text-dark);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* ===== RESULT CARD - Mobile First ===== */
        .result-card {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .result-card-image {
            width: 100%;
            height: 160px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .result-card-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .result-card-image-placeholder.berita {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .result-card-image-placeholder.wisata {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
        }

        .result-card-image-placeholder i {
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .result-card-content {
            display: flex;
            flex-direction: column;
        }

        .result-card-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.625rem;
        }

        .badge-type {
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-berita {
            background: rgba(30, 58, 95, 0.1);
            color: var(--primary);
        }

        .badge-wisata {
            background: rgba(4, 120, 87, 0.1);
            color: var(--secondary);
        }

        .badge-category {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .result-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .result-card-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .result-card-title a:hover {
            color: var(--secondary);
        }

        .result-card-excerpt {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .result-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .result-card-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* ===== NO RESULTS - Mobile First ===== */
        .no-results {
            text-align: center;
            padding: 3rem 1.5rem;
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .no-results-icon {
            width: 80px;
            height: 80px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }

        .no-results-icon i {
            font-size: 2rem;
            color: var(--text-muted);
        }

        .no-results h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.375rem;
        }

        .no-results p {
            color: var(--text-muted);
            font-size: 0.9375rem;
            margin-bottom: 1.25rem;
        }

        .btn-back {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9375rem;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: #fff;
        }

        /* ===== SIDEBAR - Mobile First ===== */
        .sidebar-widget {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }

        .sidebar-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.875rem;
            padding-bottom: 0.625rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-title i {
            color: var(--secondary);
        }

        .filter-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-item:last-child {
            border-bottom: none;
        }

        .filter-item label {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            cursor: pointer;
            color: var(--text-dark);
            font-size: 0.875rem;
        }

        .filter-count {
            font-size: 0.75rem;
            color: var(--text-muted);
            background: var(--bg-light);
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
        }

        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tips-list li {
            margin-bottom: 0.5rem;
            font-size: 0.8125rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .tips-list li:last-child {
            margin-bottom: 0;
        }

        .tips-list i {
            color: var(--secondary);
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: var(--primary-dark);
            color: #fff;
            padding: 1.5rem 0;
            text-align: center;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
            font-size: 0.875rem;
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */

        /* Small devices (576px and up) */
        @media (min-width: 576px) {
            .navbar-brand {
                font-size: 1.25rem;
            }

            .search-header h1 {
                font-size: 2rem;
            }

            .search-wrapper {
                flex-direction: row;
                gap: 0;
            }

            .search-wrapper button {
                width: auto;
            }

            .search-info {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .result-card {
                display: flex;
                gap: 1.25rem;
            }

            .result-card-image {
                flex-shrink: 0;
                width: 140px;
                height: 110px;
                margin-bottom: 0;
            }

            .result-card-content {
                flex: 1;
            }
        }

        /* Medium devices (768px and up) */
        @media (min-width: 768px) {
            .navbar {
                padding: 1rem 0;
            }

            .navbar-collapse {
                margin-top: 0;
            }

            .btn-login {
                width: auto;
                margin-top: 0;
            }

            .search-header {
                padding: 3.5rem 0;
            }

            .search-header h1 {
                font-size: 2.25rem;
            }

            .main-content {
                padding: 2rem 0 4rem;
            }

            .result-card {
                padding: 1.5rem;
            }

            .result-card-image {
                width: 160px;
                height: 130px;
            }

            .result-card-title {
                font-size: 1.125rem;
            }

            .result-card-excerpt {
                -webkit-line-clamp: 3;
            }

            .no-results {
                padding: 4rem 2rem;
            }

            .no-results-icon {
                width: 100px;
                height: 100px;
            }

            .no-results-icon i {
                font-size: 3rem;
            }

            .no-results h3 {
                font-size: 1.5rem;
            }
        }

        /* Large devices (992px and up) */
        @media (min-width: 992px) {
            .navbar-brand {
                font-size: 1.5rem;
            }

            .navbar-brand .logo-icon {
                width: 40px;
                height: 40px;
                font-size: 1.125rem;
            }

            .search-header h1 {
                font-size: 2.5rem;
            }

            .result-card:hover {
                transform: translateY(-4px);
            }

            .result-card-image {
                width: 180px;
                height: 140px;
            }

            .result-card-image-placeholder i {
                font-size: 2.5rem;
            }

            .result-card-title {
                font-size: 1.25rem;
            }

            .result-card-meta {
                font-size: 0.8125rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-md">
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
                <ul class="navbar-nav mx-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#wisata">Destinasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#berita">Berita</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#tentang">Tentang</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="btn btn-login" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Search Header -->
    <section class="search-header text-center">
        <div class="container">
            <h1><i class="bi bi-search me-2 me-md-3"></i>Pencarian</h1>
            <p>Temukan berita dan destinasi wisata yang Anda cari</p>

            <form action="search.php" method="GET" class="search-wrapper">
                <input type="text" name="q" placeholder="Masukkan kata kunci pencarian..."
                    value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit">
                    <i class="bi bi-search"></i>
                    Cari
                </button>
            </form>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <?php if (!empty($keyword)): ?>
                <div class="row g-3 g-lg-4">
                    <!-- Results Column -->
                    <div class="col-12 col-lg-8 order-2 order-lg-1">
                        <!-- Search Info - REFLECTED XSS VULNERABILITY PRESERVED -->
                        <div class="search-info">
                            <div class="search-info-text">
                                Hasil pencarian untuk: <span class="search-keyword"><?php echo $keyword; ?></span>
                            </div>
                            <div class="result-count">
                                <i class="bi bi-file-text"></i>
                                <?php echo $totalResults; ?> hasil
                            </div>
                        </div>

                        <?php if ($totalResults > 0): ?>
                            <?php foreach ($results as $item): ?>
                                <article class="result-card">
                                    <div class="result-card-image">
                                        <div class="result-card-image-placeholder <?php echo $item['type']; ?>">
                                            <i
                                                class="bi bi-<?php echo $item['type'] == 'berita' ? 'newspaper' : 'geo-alt-fill'; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="result-card-content">
                                        <div class="result-card-header">
                                            <span
                                                class="badge-type <?php echo $item['type'] == 'berita' ? 'badge-berita' : 'badge-wisata'; ?>">
                                                <?php echo $item['type'] == 'berita' ? 'Berita' : 'Wisata'; ?>
                                            </span>
                                            <span class="badge-category">
                                                <i class="bi bi-folder"></i>
                                                <?php echo htmlspecialchars($item['category_name']); ?>
                                            </span>
                                        </div>
                                        <h3 class="result-card-title">
                                            <a href="view.php?id=<?php echo $item['id']; ?>">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </h3>
                                        <p class="result-card-excerpt">
                                            <?php echo createExcerpt($item['content'], 150); ?>
                                        </p>
                                        <div class="result-card-meta">
                                            <span>
                                                <i class="bi bi-calendar3"></i>
                                                <?php echo formatTanggal($item['created_at']); ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-person"></i>
                                                <?php echo htmlspecialchars($item['author_name']); ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-eye"></i>
                                                <?php echo $item['views']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-results">
                                <div class="no-results-icon">
                                    <i class="bi bi-search"></i>
                                </div>
                                <h3>Tidak ada hasil ditemukan</h3>
                                <p>Coba gunakan kata kunci yang berbeda atau lebih umum</p>
                                <a href="index.php" class="btn-back">
                                    <i class="bi bi-arrow-left"></i>
                                    Kembali ke Beranda
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-12 col-lg-4 order-1 order-lg-2">
                        <div class="sidebar-widget">
                            <h4 class="sidebar-title">
                                <i class="bi bi-funnel"></i>
                                Filter Hasil
                            </h4>
                            <div class="filter-item">
                                <label>
                                    <input type="checkbox" class="form-check-input" checked>
                                    <span>Berita</span>
                                </label>
                                <span class="filter-count">
                                    <?php echo count(array_filter($results, fn($r) => $r['type'] == 'berita')); ?>
                                </span>
                            </div>
                            <div class="filter-item">
                                <label>
                                    <input type="checkbox" class="form-check-input" checked>
                                    <span>Wisata</span>
                                </label>
                                <span class="filter-count">
                                    <?php echo count(array_filter($results, fn($r) => $r['type'] == 'wisata')); ?>
                                </span>
                            </div>
                        </div>

                        <div class="sidebar-widget">
                            <h4 class="sidebar-title">
                                <i class="bi bi-lightbulb"></i>
                                Tips Pencarian
                            </h4>
                            <ul class="tips-list">
                                <li>
                                    <i class="bi bi-check-circle"></i>
                                    <span>Gunakan kata kunci spesifik</span>
                                </li>
                                <li>
                                    <i class="bi bi-check-circle"></i>
                                    <span>Coba variasi kata yang berbeda</span>
                                </li>
                                <li>
                                    <i class="bi bi-check-circle"></i>
                                    <span>Periksa ejaan kata kunci</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3>Masukkan kata kunci untuk mencari</h3>
                    <p>Cari berita atau destinasi wisata yang Anda inginkan</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>