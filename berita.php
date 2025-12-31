<?php
/**
 * =====================================================
 * BERITA.PHP - News Articles Listing Page
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Get berita categories
$beritaCategories = getCategoriesByType($conn, 'berita');

// Filter by category
$categoryFilter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = "a.type = 'berita' AND a.status = 'published'";
if (!empty($categoryFilter)) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->bind_param("s", $categoryFilter);
    $stmt->execute();
    $catResult = $stmt->get_result();
    if ($cat = $catResult->fetch_assoc()) {
        $where .= " AND a.category_id = " . $cat['id'];
    }
    $stmt->close();
}

// Get total articles
$result = $conn->query("SELECT COUNT(*) as total FROM articles a WHERE $where");
$totalArticles = $result->fetch_assoc()['total'];
$totalPages = ceil($totalArticles / $perPage);

// Get articles
$articles = [];
$query = "SELECT a.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name
          FROM articles a
          LEFT JOIN categories c ON a.category_id = c.id
          LEFT JOIN users u ON a.author_id = u.id
          WHERE $where
          ORDER BY a.created_at DESC
          LIMIT $offset, $perPage";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}

// Get featured/breaking news
$breakingArticle = null;
$breakingQuery = $conn->query("SELECT a.*, c.name as category_name 
                               FROM articles a 
                               LEFT JOIN categories c ON a.category_id = c.id
                               WHERE a.type = 'berita' AND a.status = 'published' AND a.is_breaking = 1
                               ORDER BY a.created_at DESC LIMIT 1");
if ($breakingQuery && $breakingQuery->num_rows > 0) {
    $breakingArticle = $breakingQuery->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Berita terkini dan terupdate dari <?php echo SITE_NAME; ?>">
    <title>Berita Terkini - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .breaking-card {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border-radius: var(--radius-xl);
            padding: 2rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .breaking-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .breaking-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff;
            color: #dc2626;
            padding: 0.375rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .breaking-label i {
            animation: pulse 1s infinite;
        }

        .category-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .category-tab {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: 1px solid var(--border-color);
            background: var(--bg-white);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .category-tab:hover,
        .category-tab.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="bi bi-newspaper me-2"></i>Berita Terkini</h1>
            <p>Informasi terbaru dan terpercaya</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content py-5">
        <div class="container">
            <!-- Breaking News -->
            <?php if ($breakingArticle && empty($categoryFilter) && $page === 1): ?>
                <div class="breaking-card mb-5">
                    <span class="breaking-label">
                        <i class="bi bi-lightning-fill"></i> Breaking News
                    </span>
                    <span
                        class="badge bg-light text-dark mb-2"><?php echo safeOutput($breakingArticle['category_name']); ?></span>
                    <h2 class="h3 mb-3">
                        <a href="view.php?id=<?php echo $breakingArticle['id']; ?>" class="text-white text-decoration-none">
                            <?php echo safeOutput($breakingArticle['title']); ?>
                        </a>
                    </h2>
                    <p class="mb-3 opacity-75">
                        <?php echo safeOutput(truncateText($breakingArticle['excerpt'] ?? $breakingArticle['content'], 150)); ?>
                    </p>
                    <a href="view.php?id=<?php echo $breakingArticle['id']; ?>" class="btn btn-light">
                        Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Category Tabs -->
                    <div class="category-tabs">
                        <a href="berita.php" class="category-tab <?php echo empty($categoryFilter) ? 'active' : ''; ?>">
                            Semua
                        </a>
                        <?php foreach ($beritaCategories as $cat): ?>
                            <a href="berita.php?kategori=<?php echo $cat['slug']; ?>"
                                class="category-tab <?php echo $categoryFilter === $cat['slug'] ? 'active' : ''; ?>">
                                <i class="<?php echo $cat['icon']; ?> me-1"></i><?php echo $cat['name']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Articles -->
                    <?php if (empty($articles)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-journal-x display-1 text-muted"></i>
                            <h3 class="mt-3">Belum Ada Berita</h3>
                            <p class="text-muted">Belum ada berita dalam kategori ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($articles as $article): ?>
                                <div class="col-md-6">
                                    <article class="news-card">
                                        <div class="news-card-image">
                                            <?php if (!empty($article['image'])): ?>
                                                <img src="uploads/<?php echo safeOutput($article['image']); ?>"
                                                    alt="<?php echo safeOutput($article['title']); ?>"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="news-card-image-placeholder berita" style="display: none;">
                                                    <i class="bi bi-newspaper"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="news-card-image-placeholder berita">
                                                    <i class="bi bi-newspaper"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span
                                                class="news-card-category"><?php echo safeOutput($article['category_name']); ?></span>
                                        </div>
                                        <div class="news-card-body">
                                            <h3 class="news-card-title">
                                                <a href="view.php?id=<?php echo $article['id']; ?>">
                                                    <?php echo safeOutput($article['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="news-card-excerpt">
                                                <?php echo safeOutput(truncateText($article['excerpt'] ?? $article['content'], 80)); ?>
                                            </p>
                                            <div class="news-card-meta">
                                                <span><i class="bi bi-person"></i>
                                                    <?php echo safeOutput($article['author_name']); ?></span>
                                                <span><i class="bi bi-clock"></i>
                                                    <?php echo timeAgo($article['created_at']); ?></span>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-5" aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $queryParams = !empty($categoryFilter) ? "kategori=$categoryFilter&" : "";
                                    if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo $queryParams; ?>page=<?php echo $page - 1; ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?<?php echo $queryParams; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo $queryParams; ?>page=<?php echo $page + 1; ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <?php include 'includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>