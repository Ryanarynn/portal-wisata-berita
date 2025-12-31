<?php
/**
 * =====================================================
 * INDEX.PHP - Homepage
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Get breaking news
$breakingNews = getBreakingNews($conn, 5);

// Get featured/headline article
$featuredQuery = $conn->query("SELECT a.*, c.name as category_name, u.full_name as author_name 
                               FROM articles a 
                               LEFT JOIN categories c ON a.category_id = c.id 
                               LEFT JOIN users u ON a.author_id = u.id 
                               WHERE a.status = 'published' AND a.is_featured = 1 
                               ORDER BY a.created_at DESC LIMIT 1");
$featured = $featuredQuery->fetch_assoc();

// If no featured, get most viewed
if (!$featured) {
    $featuredQuery = $conn->query("SELECT a.*, c.name as category_name, u.full_name as author_name 
                                   FROM articles a 
                                   LEFT JOIN categories c ON a.category_id = c.id 
                                   LEFT JOIN users u ON a.author_id = u.id 
                                   WHERE a.status = 'published' 
                                   ORDER BY a.views DESC LIMIT 1");
    $featured = $featuredQuery->fetch_assoc();
}

// Get latest news (berita)
$latestNews = [];
$newsQuery = $conn->query("SELECT a.*, c.name as category_name, u.full_name as author_name 
                           FROM articles a 
                           LEFT JOIN categories c ON a.category_id = c.id 
                           LEFT JOIN users u ON a.author_id = u.id 
                           WHERE a.type = 'berita' AND a.status = 'published' 
                           ORDER BY a.created_at DESC LIMIT 6");
while ($row = $newsQuery->fetch_assoc()) {
    $latestNews[] = $row;
}

// Get popular destinations (wisata)
$destinations = [];
$destQuery = $conn->query("SELECT a.*, c.name as category_name, c.icon as category_icon 
                           FROM articles a 
                           LEFT JOIN categories c ON a.category_id = c.id 
                           WHERE a.type = 'wisata' AND a.status = 'published' 
                           ORDER BY a.views DESC LIMIT 6");
while ($row = $destQuery->fetch_assoc()) {
    $destinations[] = $row;
}

// Get trending articles
$trending = getTrendingArticles($conn, 5);

// Get all categories for navigation
$categories = getAllCategories($conn);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal berita dan wisata terkini - <?php echo SITE_NAME; ?>">
    <title><?php echo SITE_NAME; ?> - Portal Berita & Wisata Terkini</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 4rem 0;
            color: #fff;
        }

        .hero-featured {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-xl);
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-category {
            display: inline-block;
            background: var(--secondary);
            color: #fff;
            padding: 0.375rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .hero-title {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .hero-title a {
            color: #fff;
            text-decoration: none;
        }

        .hero-title a:hover {
            text-decoration: underline;
        }

        .hero-excerpt {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1.5rem;
            font-size: 1.0625rem;
        }

        .hero-meta {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--secondary);
        }

        .section-title {
            font-family: 'Inter', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-all {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .view-all:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <?php if ($featured): ?>
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-7">
                        <div class="hero-featured">
                            <span class="hero-category"><?php echo safeOutput($featured['category_name']); ?></span>
                            <h1 class="hero-title">
                                <a href="view.php?id=<?php echo $featured['id']; ?>">
                                    <?php echo safeOutput($featured['title']); ?>
                                </a>
                            </h1>
                            <p class="hero-excerpt">
                                <?php echo safeOutput(truncateText($featured['excerpt'] ?? $featured['content'], 180)); ?>
                            </p>
                            <div class="hero-meta">
                                <span><i
                                        class="bi bi-person me-1"></i><?php echo safeOutput($featured['author_name']); ?></span>
                                <span class="mx-2">•</span>
                                <span><i class="bi bi-clock me-1"></i><?php echo timeAgo($featured['created_at']); ?></span>
                                <span class="mx-2">•</span>
                                <span><i class="bi bi-eye me-1"></i><?php echo formatViews($featured['views']); ?>
                                    views</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 mt-4 mt-lg-0">
                        <!-- Trending sidebar in hero -->
                        <div class="bg-white bg-opacity-10 rounded-4 p-3">
                            <h6 class="text-white mb-3"><i class="bi bi-fire me-1"></i> Trending Now</h6>
                            <?php foreach (array_slice($trending, 0, 4) as $i => $item): ?>
                                <div class="d-flex gap-2 mb-2 pb-2 border-bottom border-white border-opacity-10">
                                    <span class="text-white fw-bold"
                                        style="font-size: 1.25rem; opacity: 0.5;"><?php echo $i + 1; ?></span>
                                    <div>
                                        <a href="view.php?id=<?php echo $item['id']; ?>"
                                            class="text-white text-decoration-none small fw-medium">
                                            <?php echo safeOutput(truncateText($item['title'], 60)); ?>
                                        </a>
                                        <div class="text-white-50 small"><?php echo formatViews($item['views']); ?> views</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Main Column -->
                <div class="col-lg-8">
                    <!-- Latest News -->
                    <section class="mb-5">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="bi bi-newspaper text-primary"></i>
                                Berita Terkini
                            </h2>
                            <a href="berita.php" class="view-all">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="row g-4">
                            <?php foreach ($latestNews as $article): ?>
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
                                                <span><i class="bi bi-clock"></i>
                                                    <?php echo timeAgo($article['created_at']); ?></span>
                                                <span><i class="bi bi-eye"></i>
                                                    <?php echo formatViews($article['views']); ?></span>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Destinations -->
                    <section>
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="bi bi-geo-alt-fill text-success"></i>
                                Destinasi Wisata Populer
                            </h2>
                            <a href="wisata.php" class="view-all">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="row g-4">
                            <?php foreach ($destinations as $dest): ?>
                                <div class="col-md-6 col-lg-4">
                                    <article class="news-card">
                                        <div class="news-card-image">
                                            <?php if (!empty($dest['image'])): ?>
                                                <img src="uploads/<?php echo safeOutput($dest['image']); ?>"
                                                    alt="<?php echo safeOutput($dest['title']); ?>"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="news-card-image-placeholder wisata" style="display: none;">
                                                    <i class="<?php echo $dest['category_icon'] ?? 'bi bi-geo-alt'; ?>"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="news-card-image-placeholder wisata">
                                                    <i class="<?php echo $dest['category_icon'] ?? 'bi bi-geo-alt'; ?>"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span
                                                class="news-card-category"><?php echo safeOutput($dest['category_name']); ?></span>
                                        </div>
                                        <div class="news-card-body">
                                            <h3 class="news-card-title">
                                                <a href="view.php?id=<?php echo $dest['id']; ?>">
                                                    <?php echo safeOutput($dest['title']); ?>
                                                </a>
                                            </h3>
                                            <div class="news-card-meta">
                                                <span><i class="bi bi-eye"></i> <?php echo formatViews($dest['views']); ?>
                                                    views</span>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
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