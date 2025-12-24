<?php
/**
 * HALAMAN DETAIL BERITA / WISATA (VIEW)
 * Portal Wisata & Berita Kota
 */

require_once 'config.php';

// Ambil ID artikel dari parameter
$articleId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($articleId <= 0) {
    header('Location: index.php');
    exit;
}

// Ambil data artikel
$queryArticle = "SELECT a.*, c.name as category_name, u.full_name as author_name 
                 FROM articles a 
                 LEFT JOIN categories c ON a.category_id = c.id 
                 LEFT JOIN users u ON a.author_id = u.id 
                 WHERE a.id = ?";
$stmt = $conn->prepare($queryArticle);
$stmt->bind_param("i", $articleId);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();
$stmt->close();

if (!$article) {
    header('Location: index.php');
    exit;
}

// Update view count
$conn->query("UPDATE articles SET views = views + 1 WHERE id = $articleId");

// Proses komentar
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';

    if (!empty($name) && !empty($comment)) {
        $stmtInsert = $conn->prepare("INSERT INTO comments (article_id, name, email, comment) VALUES (?, ?, ?, ?)");
        $stmtInsert->bind_param("isss", $articleId, $name, $email, $comment);

        if ($stmtInsert->execute()) {
            $successMessage = "Komentar berhasil ditambahkan!";
        } else {
            $errorMessage = "Gagal menambahkan komentar.";
        }
        $stmtInsert->close();
    } else {
        $errorMessage = "Nama dan komentar wajib diisi!";
    }
}

// Ambil semua komentar
$queryComments = "SELECT * FROM comments WHERE article_id = ? ORDER BY created_at DESC";
$stmtComments = $conn->prepare($queryComments);
$stmtComments->bind_param("i", $articleId);
$stmtComments->execute();
$commentsResult = $stmtComments->get_result();

// Ambil artikel terkait
$queryRelated = "SELECT id, title, created_at FROM articles 
                 WHERE category_id = ? AND id != ? AND status = 'published' 
                 ORDER BY created_at DESC LIMIT 5";
$stmtRelated = $conn->prepare($queryRelated);
$stmtRelated->bind_param("ii", $article['category_id'], $articleId);
$stmtRelated->execute();
$relatedResult = $stmtRelated->get_result();

// Generate random avatar colors
function getAvatarColor($name)
{
    $colors = ['#1e3a5f', '#047857', '#7c3aed', '#db2777', '#ea580c', '#0891b2'];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

function getInitials($name)
{
    $words = explode(' ', $name);
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - <?php echo SITE_NAME; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&family=Merriweather:wght@400;700&display=swap"
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
            font-size: 0.9375rem;
        }

        .nav-link:hover {
            color: var(--secondary) !important;
        }

        .nav-search {
            display: none;
        }

        /* ===== ARTICLE HEADER - Mobile First ===== */
        .article-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem 0 3rem;
            position: relative;
            overflow: hidden;
        }

        .article-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(30%, -50%);
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 1rem;
            font-size: 0.8125rem;
        }

        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: rgba(255, 255, 255, 0.6);
        }

        .breadcrumb-item+.breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.5);
        }

        .article-category {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .article-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.35;
            margin-bottom: 1rem;
        }

        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.8125rem;
        }

        .article-meta span {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        /* ===== MAIN CONTENT - Mobile First ===== */
        .main-content {
            margin-top: -1.5rem;
            padding-bottom: 2rem;
            position: relative;
            z-index: 10;
        }

        /* ===== ARTICLE CONTENT - Mobile First ===== */
        .article-content {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 1.25rem;
        }

        .article-body {
            font-family: 'Merriweather', Georgia, serif;
            font-size: 1rem;
            line-height: 1.85;
            color: #333;
        }

        .article-body p {
            margin-bottom: 1.25rem;
        }

        .article-body h2,
        .article-body h3 {
            font-family: 'Playfair Display', serif;
            color: var(--text-dark);
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }

        .article-body img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        /* Share Section */
        .share-section {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
            margin-top: 1.5rem;
        }

        .share-label {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.875rem;
        }

        .share-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .share-btn.facebook {
            background: #1877f2;
            color: #fff;
        }

        .share-btn.twitter {
            background: #1da1f2;
            color: #fff;
        }

        .share-btn.whatsapp {
            background: #25d366;
            color: #fff;
        }

        .share-btn.copy {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid var(--border-color);
        }

        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* ===== COMMENTS SECTION - Mobile First ===== */
        .comments-section {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow-lg);
        }

        .comments-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
            flex-wrap: wrap;
        }

        .comments-header i {
            color: var(--secondary);
        }

        .comment-count {
            background: var(--secondary);
            color: #fff;
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 50px;
            margin-left: auto;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 0.875rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background: rgba(4, 120, 87, 0.1);
            color: var(--secondary);
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
        }

        .alert i {
            font-size: 1.125rem;
            flex-shrink: 0;
        }

        /* Comment Form */
        .comment-form {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .comment-form-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .comment-form-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .comment-form-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9375rem;
        }

        .comment-form-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.1);
            outline: none;
        }

        .btn-submit-comment {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9375rem;
            width: 100%;
            justify-content: center;
        }

        .btn-submit-comment:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: #fff;
        }

        /* ===== COMMENT ITEM - Social Media Style ===== */
        .comment-item {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .comment-item:last-child {
            border-bottom: none;
        }

        .comment-avatar {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .comment-content {
            flex: 1;
            min-width: 0;
        }

        .comment-bubble {
            background: var(--bg-light);
            border-radius: 0 12px 12px 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.375rem;
        }

        .comment-author {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.125rem;
            font-size: 0.875rem;
        }

        .comment-text {
            color: var(--text-dark);
            line-height: 1.6;
            font-size: 0.875rem;
            word-wrap: break-word;
        }

        .comment-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            padding-left: 0.25rem;
        }

        .comment-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .no-comments {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-muted);
        }

        .no-comments i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: var(--border-color);
        }

        .no-comments p {
            font-size: 0.875rem;
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
            margin-bottom: 1rem;
            padding-bottom: 0.625rem;
            border-bottom: 2px solid var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-title i {
            color: var(--secondary);
        }

        .related-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .related-item:last-child {
            border-bottom: none;
        }

        .related-item a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
            line-height: 1.5;
            transition: color 0.3s ease;
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }

        .related-item a:hover {
            color: var(--secondary);
        }

        .related-item-date {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-back {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            color: #fff;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9375rem;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: #fff;
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
            .article-title {
                font-size: 1.75rem;
            }

            .article-content {
                padding: 1.5rem;
            }

            .share-section {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .btn-submit-comment {
                width: auto;
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

            .navbar-brand {
                font-size: 1.25rem;
            }

            .article-header {
                padding: 3rem 0 4rem;
            }

            .article-title {
                font-size: 2rem;
            }

            .article-meta {
                font-size: 0.9375rem;
            }

            .main-content {
                margin-top: -2rem;
                padding-bottom: 3rem;
            }

            .article-content {
                padding: 2rem;
            }

            .article-body {
                font-size: 1.0625rem;
            }

            .article-body h2,
            .article-body h3 {
                font-size: 1.5rem;
            }

            .comments-section {
                padding: 1.5rem;
            }

            .comment-form {
                padding: 1.25rem;
            }

            .comment-form-avatar {
                width: 48px;
                height: 48px;
                font-size: 1.25rem;
            }

            .comment-avatar {
                width: 48px;
                height: 48px;
                font-size: 1rem;
            }

            .comment-bubble {
                padding: 1rem 1.25rem;
            }

            .sidebar-widget {
                padding: 1.5rem;
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

            .nav-search {
                display: flex;
                gap: 0.5rem;
            }

            .nav-search input {
                border: 2px solid var(--border-color);
                border-radius: 8px;
                padding: 0.5rem 1rem;
                width: 180px;
                font-size: 0.875rem;
            }

            .nav-search input:focus {
                border-color: var(--primary);
                outline: none;
            }

            .nav-search button {
                background: var(--primary);
                border: none;
                border-radius: 8px;
                color: #fff;
                padding: 0.5rem 0.75rem;
            }

            .article-header {
                padding: 4rem 0 5rem;
            }

            .article-title {
                font-size: 2.5rem;
            }

            .main-content {
                margin-top: -3rem;
                padding-bottom: 4rem;
            }

            .article-content {
                padding: 2.5rem;
                border-radius: 16px;
            }

            .article-body {
                font-size: 1.125rem;
            }

            .comments-section {
                padding: 2rem;
                border-radius: 16px;
            }

            .comments-header {
                font-size: 1.5rem;
            }

            .sidebar-widget {
                border-radius: 16px;
            }

            .related-item a {
                font-size: 0.9375rem;
            }
        }

        /* X-Large devices (1200px and up) */
        @media (min-width: 1200px) {
            .article-title {
                font-size: 2.75rem;
            }

            .article-content {
                padding: 3rem;
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
                <span class="d-sm-none">Portal</span>
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
                <form action="search.php" method="GET" class="nav-search">
                    <input type="text" name="q" placeholder="Cari...">
                    <button type="submit"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Article Header -->
    <header class="article-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item"><a
                            href="index.php#<?php echo $article['type']; ?>"><?php echo ucfirst($article['type']); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Artikel</li>
                </ol>
            </nav>
            <span class="article-category">
                <i class="bi bi-<?php echo $article['type'] == 'berita' ? 'newspaper' : 'geo-alt-fill'; ?>"></i>
                <?php echo htmlspecialchars($article['category_name']); ?>
            </span>
            <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="article-meta">
                <span>
                    <i class="bi bi-person-circle"></i>
                    <?php echo htmlspecialchars($article['author_name']); ?>
                </span>
                <span>
                    <i class="bi bi-calendar3"></i>
                    <?php echo formatTanggal($article['created_at']); ?>
                </span>
                <span>
                    <i class="bi bi-eye"></i>
                    <?php echo $article['views']; ?> views
                </span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row g-3 g-lg-4">
                <!-- Article Content -->
                <div class="col-12 col-lg-8">
                    <article class="article-content">
                        <div class="article-body">
                            <?php echo $article['content']; ?>
                        </div>

                        <div class="share-section">
                            <span class="share-label">Bagikan Artikel:</span>
                            <div class="share-buttons">
                                <a href="#" class="share-btn facebook" title="Share to Facebook">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="#" class="share-btn twitter" title="Share to Twitter">
                                    <i class="bi bi-twitter"></i>
                                </a>
                                <a href="#" class="share-btn whatsapp" title="Share to WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <a href="#" class="share-btn copy" title="Copy Link">
                                    <i class="bi bi-link-45deg"></i>
                                </a>
                            </div>
                        </div>
                    </article>

                    <!-- Comments Section -->
                    <div class="comments-section">
                        <div class="comments-header">
                            <i class="bi bi-chat-square-dots"></i>
                            Komentar
                            <span class="comment-count"><?php echo $commentsResult->num_rows; ?></span>
                        </div>

                        <!-- Alert Messages -->
                        <?php if (!empty($successMessage)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <span><?php echo $successMessage; ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errorMessage)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <span><?php echo $errorMessage; ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Comment Form -->
                        <div class="comment-form">
                            <div class="comment-form-header">
                                <div class="comment-form-avatar">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <div class="comment-form-title">Tulis Komentar</div>
                                    <div class="comment-form-subtitle">Bagikan pendapat Anda</div>
                                </div>
                            </div>

                            <form method="POST">
                                <div class="row g-2 g-md-3">
                                    <div class="col-12 col-sm-6">
                                        <input type="text" class="form-control" name="name" placeholder="Nama Anda *"
                                            required>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <input type="email" class="form-control" name="email"
                                            placeholder="Email (opsional)">
                                    </div>
                                    <div class="col-12">
                                        <textarea class="form-control" name="comment" rows="4"
                                            placeholder="Tulis komentar Anda di sini..." required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn-submit-comment">
                                            <i class="bi bi-send"></i>
                                            Kirim Komentar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Comments List - STORED XSS VULNERABILITY PRESERVED -->
                        <div class="comments-list">
                            <?php if ($commentsResult->num_rows > 0): ?>
                                <?php while ($comment = $commentsResult->fetch_assoc()): ?>
                                    <?php $avatarColor = getAvatarColor($comment['name']); ?>
                                    <?php $initials = getInitials($comment['name']); ?>
                                    <div class="comment-item">
                                        <div class="comment-avatar" style="background: <?php echo $avatarColor; ?>;">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div class="comment-content">
                                            <div class="comment-bubble">
                                                <div class="comment-author">
                                                    <?php echo htmlspecialchars($comment['name']); ?>
                                                </div>
                                                <div class="comment-text">
                                                    <?php echo $comment['comment']; ?>
                                                </div>
                                            </div>
                                            <div class="comment-meta">
                                                <span>
                                                    <i class="bi bi-clock"></i>
                                                    <?php echo formatTanggal($comment['created_at']); ?>
                                                </span>
                                                <span>
                                                    <i class="bi bi-hand-thumbs-up"></i>
                                                    Suka
                                                </span>
                                                <span>
                                                    <i class="bi bi-reply"></i>
                                                    Balas
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-comments">
                                    <i class="bi bi-chat-square-dots d-block"></i>
                                    <p class="mb-0">Belum ada komentar.<br>Jadilah yang pertama berkomentar!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-12 col-lg-4">
                    <!-- Related Articles -->
                    <div class="sidebar-widget">
                        <h4 class="sidebar-title">
                            <i class="bi bi-link-45deg"></i>
                            Artikel Terkait
                        </h4>
                        <?php if ($relatedResult->num_rows > 0): ?>
                            <?php while ($related = $relatedResult->fetch_assoc()): ?>
                                <div class="related-item">
                                    <a href="view.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                    <div class="related-item-date">
                                        <i class="bi bi-calendar3"></i>
                                        <?php echo formatTanggal($related['created_at']); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">Tidak ada artikel terkait.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Share Widget -->
                    <div class="sidebar-widget">
                        <h4 class="sidebar-title">
                            <i class="bi bi-share"></i>
                            Bagikan
                        </h4>
                        <div class="d-flex gap-2">
                            <a href="#" class="share-btn facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="#" class="share-btn twitter">
                                <i class="bi bi-twitter"></i>
                            </a>
                            <a href="#" class="share-btn whatsapp">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                            <a href="#" class="share-btn copy">
                                <i class="bi bi-link-45deg"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Back Button -->
                    <a href="index.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
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
<?php
$stmtComments->close();
$stmtRelated->close();
?>