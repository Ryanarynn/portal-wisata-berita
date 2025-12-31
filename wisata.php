<?php
/**
 * =====================================================
 * WISATA.PHP - Travel Destinations Listing Page
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Get wisata categories
$wisataCategories = getCategoriesByType($conn, 'wisata');

// Filter by category
$categoryFilter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = "a.type = 'wisata' AND a.status = 'published'";
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
$query = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon, c.color as category_color,
                 u.full_name as author_name
          FROM articles a
          LEFT JOIN categories c ON a.category_id = c.id
          LEFT JOIN users u ON a.author_id = u.id
          WHERE $where
          ORDER BY a.views DESC, a.created_at DESC
          LIMIT $offset, $perPage";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}

// Get featured destinations
$featuredDestinations = getFeaturedArticles($conn, 3);
$featuredDestinations = array_filter($featuredDestinations, fn($a) => $a['type'] === 'wisata');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Jelajahi destinasi wisata menarik dari <?php echo SITE_NAME; ?>">
    <title>Destinasi Wisata - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .wisata-hero {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
            padding: 4rem 0;
            color: #fff;
        }

        .category-icons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .category-icon-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            color: #fff;
            text-decoration: none;
            transition: var(--transition);
            min-width: 100px;
        }

        .category-icon-item:hover,
        .category-icon-item.active {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
            transform: translateY(-4px);
        }

        .category-icon-item i {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .category-icon-item span {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .destination-card {
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            background: var(--bg-white);
        }

        .destination-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .destination-image {
            height: 200px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .destination-image i {
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .destination-category {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--bg-white);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.625rem;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .destination-category i {
            font-size: 0.625rem;
        }

        .destination-views {
            position: absolute;
            bottom: 0.5rem;
            right: 0.5rem;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.5625rem;
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .destination-views i {
            font-size: 0.5rem;
        }

        .destination-body {
            padding: 1.25rem;
        }

        .destination-title {
            font-size: 1.0625rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .destination-title a {
            color: var(--text-dark);
            text-decoration: none;
        }

        .destination-title a:hover {
            color: var(--secondary);
        }

        .destination-excerpt {
            color: var(--text-muted);
            font-size: 0.875rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="wisata-hero text-center">
        <div class="container">
            <h1><i class="bi bi-geo-alt-fill me-2"></i>Jelajahi Destinasi Wisata</h1>
            <p>Temukan tempat-tempat menarik untuk liburan Anda</p>

            <!-- Category Icons -->
            <div class="category-icons">
                <a href="wisata.php" class="category-icon-item <?php echo empty($categoryFilter) ? 'active' : ''; ?>">
                    <i class="bi bi-grid"></i>
                    <span>Semua</span>
                </a>
                <?php foreach ($wisataCategories as $cat): ?>
                    <a href="wisata.php?kategori=<?php echo $cat['slug']; ?>"
                        class="category-icon-item <?php echo $categoryFilter === $cat['slug'] ? 'active' : ''; ?>">
                        <i class="<?php echo $cat['icon']; ?>"></i>
                        <span><?php echo $cat['name']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="mb-0 text-muted">
                    <strong><?php echo $totalArticles; ?></strong> destinasi ditemukan
                    <?php if (!empty($categoryFilter)): ?>
                        dalam kategori <strong><?php echo $categoryFilter; ?></strong>
                    <?php endif; ?>
                </p>
            </div>

            <?php if (empty($articles)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-map display-1 text-muted"></i>
                    <h3 class="mt-3">Belum Ada Destinasi</h3>
                    <p class="text-muted">Belum ada destinasi wisata dalam kategori ini.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($articles as $article): ?>
                        <div class="col-md-6 col-lg-4">
                            <article class="destination-card">
                                <?php if (!empty($article['image'])): ?>
                                    <div class="destination-image"
                                        style="background: url('uploads/<?php echo safeOutput($article['image']); ?>') center/cover no-repeat;">
                                    <?php else: ?>
                                        <div class="destination-image"
                                            style="background: linear-gradient(135deg, <?php echo $article['category_color'] ?? '#047857'; ?> 0%, #065f46 100%);">
                                            <i class="<?php echo $article['category_icon'] ?? 'bi-geo-alt'; ?>"></i>
                                        <?php endif; ?>
                                        <span class="destination-category"
                                            style="color: <?php echo $article['category_color'] ?? '#047857'; ?>">
                                            <?php echo safeOutput($article['category_name']); ?>
                                        </span>
                                        <span class="destination-views">
                                            <i class="bi bi-eye"></i> <?php echo formatViews($article['views']); ?>
                                        </span>
                                    </div>
                                    <div class="destination-body">
                                        <h3 class="destination-title">
                                            <a href="view.php?id=<?php echo $article['id']; ?>">
                                                <?php echo safeOutput($article['title']); ?>
                                            </a>
                                        </h3>
                                        <p class="destination-excerpt">
                                            <?php echo safeOutput(truncateText($article['excerpt'] ?? $article['content'], 100)); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?php echo timeAgo($article['created_at']); ?>
                                            </small>
                                            <a href="view.php?id=<?php echo $article['id']; ?>"
                                                class="btn btn-sm btn-outline-success">
                                                Lihat <i class="bi bi-arrow-right"></i>
                                            </a>
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
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>