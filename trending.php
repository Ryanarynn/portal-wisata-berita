<?php
/**
 * =====================================================
 * TRENDING.PHP - Trending Articles Page
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get total published articles
$result = $conn->query("SELECT COUNT(*) as total FROM articles WHERE status = 'published'");
$totalArticles = $result->fetch_assoc()['total'];
$totalPages = ceil($totalArticles / $perPage);

// Get trending articles (sorted by views)
$articles = [];
$query = "SELECT a.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name
          FROM articles a
          LEFT JOIN categories c ON a.category_id = c.id
          LEFT JOIN users u ON a.author_id = u.id
          WHERE a.status = 'published'
          ORDER BY a.views DESC, a.created_at DESC
          LIMIT $offset, $perPage";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}

// Top 3 for featured section
$topArticles = array_slice($articles, 0, 3);
$remainingArticles = array_slice($articles, 3);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Artikel trending dan paling populer di <?php echo SITE_NAME; ?>">
    <title>Trending - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .trending-hero {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            padding: 4rem 0;
            color: #fff;
        }

        .trending-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .fire-icon {
            animation: pulse 1s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .top-card {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            height: 300px;
            display: flex;
            align-items: flex-end;
        }

        .top-card-bg {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .top-card-bg.berita {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .top-card-bg.wisata {
            background: linear-gradient(135deg, var(--secondary) 0%, #065f46 100%);
        }

        .top-card-bg i {
            font-size: 5rem;
            color: rgba(255, 255, 255, 0.2);
        }

        .top-card-content {
            position: relative;
            padding: 1.5rem;
            color: #fff;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            width: 100%;
        }

        .top-card-rank {
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 40px;
            height: 40px;
            background: #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
        }

        .top-card-category {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .top-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .top-card-title a {
            color: #fff;
            text-decoration: none;
        }

        .top-card-title a:hover {
            text-decoration: underline;
        }

        .top-card-meta {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="trending-hero text-center">
        <div class="container">
            <span class="trending-badge">
                <i class="bi bi-fire fire-icon"></i>
                Hot & Trending
            </span>
            <h1><i class="bi bi-graph-up-arrow me-2"></i>Artikel Trending</h1>
            <p>Artikel paling banyak dibaca dan populer saat ini</p>
        </div>
    </section>

    <!-- Top 3 Featured -->
    <?php if (!empty($topArticles) && $page === 1): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <div class="row g-4">
                    <?php foreach ($topArticles as $index => $article): ?>
                        <div class="col-md-<?php echo $index === 0 ? '12' : '6'; ?> col-lg-4">
                            <div class="top-card">
                                <?php if (!empty($article['image'])): ?>
                                    <div class="top-card-bg"
                                        style="background: url('uploads/<?php echo safeOutput($article['image']); ?>') center/cover no-repeat;">
                                    </div>
                                <?php else: ?>
                                    <div class="top-card-bg <?php echo $article['type']; ?>">
                                        <i
                                            class="bi bi-<?php echo $article['type'] === 'berita' ? 'newspaper' : 'geo-alt-fill'; ?>"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="top-card-rank"><?php echo $index + 1; ?></span>
                                <div class="top-card-content">
                                    <span class="top-card-category"><?php echo safeOutput($article['category_name']); ?></span>
                                    <h3 class="top-card-title">
                                        <a href="view.php?id=<?php echo $article['id']; ?>">
                                            <?php echo safeOutput($article['title']); ?>
                                        </a>
                                    </h3>
                                    <div class="top-card-meta">
                                        <i class="bi bi-eye"></i> <?php echo formatViews($article['views']); ?> views •
                                        <i class="bi bi-clock"></i> <?php echo timeAgo($article['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Remaining Articles -->
    <div class="main-content py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8">
                    <?php $displayArticles = $page === 1 ? $remainingArticles : $articles; ?>
                    <?php if (empty($displayArticles)): ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Tidak ada artikel lagi.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($displayArticles as $index => $article): ?>
                                <div class="col-md-6">
                                    <article class="news-card">
                                        <div class="news-card-image">
                                            <?php if (!empty($article['image'])): ?>
                                                <img src="uploads/<?php echo safeOutput($article['image']); ?>"
                                                    alt="<?php echo safeOutput($article['title']); ?>"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="news-card-image-placeholder <?php echo $article['type']; ?>"
                                                    style="display: none;">
                                                    <i
                                                        class="bi bi-<?php echo $article['type'] === 'berita' ? 'newspaper' : 'geo-alt-fill'; ?>"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="news-card-image-placeholder <?php echo $article['type']; ?>">
                                                    <i
                                                        class="bi bi-<?php echo $article['type'] === 'berita' ? 'newspaper' : 'geo-alt-fill'; ?>"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span
                                                class="news-card-category"><?php echo safeOutput($article['category_name']); ?></span>
                                            <span class="position-absolute top-0 end-0 m-2 badge bg-danger">
                                                #<?php echo ($page === 1 ? $index + 4 : ($page - 1) * $perPage + $index + 1); ?>
                                            </span>
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
                                                <span><i class="bi bi-eye text-danger"></i>
                                                    <?php echo formatViews($article['views']); ?></span>
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
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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