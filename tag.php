<?php
/**
 * =====================================================
 * TAG.PHP - Tag-based Article Listing
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Get tag slug from URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Get tag info
$stmt = $conn->prepare("SELECT * FROM tags WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$tag = $result->fetch_assoc();
$stmt->close();

if (!$tag) {
    header('Location: index.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get total articles
$result = $conn->query("SELECT COUNT(*) as total FROM article_tags WHERE tag_id = {$tag['id']}");
$totalArticles = $result->fetch_assoc()['total'];
$totalPages = ceil($totalArticles / $perPage);

// Get articles with this tag
$articles = [];
$query = "SELECT a.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name
          FROM articles a
          JOIN article_tags at ON a.id = at.article_id
          LEFT JOIN categories c ON a.category_id = c.id
          LEFT JOIN users u ON a.author_id = u.id
          WHERE at.tag_id = {$tag['id']} AND a.status = 'published'
          ORDER BY a.created_at DESC
          LIMIT $offset, $perPage";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}

// Get all popular tags for sidebar
$allTags = getPopularTags($conn, 20);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Artikel dengan tag <?php echo safeOutput($tag['name']); ?>">
    <title>#<?php echo safeOutput($tag['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="bi bi-hash me-2"></i><?php echo safeOutput($tag['name']); ?></h1>
            <p><?php echo $totalArticles; ?> artikel dengan tag ini</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Articles Grid -->
                <div class="col-lg-8">
                    <?php if (empty($articles)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-journal-x display-1 text-muted"></i>
                            <h3 class="mt-3">Belum Ada Artikel</h3>
                            <p class="text-muted">Belum ada artikel dengan tag ini.</p>
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
                                        </div>
                                        <div class="news-card-body">
                                            <h3 class="news-card-title">
                                                <a href="view.php?id=<?php echo $article['id']; ?>">
                                                    <?php echo safeOutput($article['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="news-card-excerpt">
                                                <?php echo safeOutput(truncateText($article['excerpt'] ?? $article['content'], 100)); ?>
                                            </p>
                                            <div class="news-card-meta">
                                                <span><i class="bi bi-person"></i>
                                                    <?php echo safeOutput($article['author_name']); ?></span>
                                                <span><i class="bi bi-eye"></i>
                                                    <?php echo formatViews($article['views']); ?></span>
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
                                            <a class="page-link" href="?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Sidebar with Tags -->
                <div class="col-lg-4">
                    <div class="sidebar-widget">
                        <h5 class="sidebar-title">
                            <i class="bi bi-tags text-primary"></i>
                            Semua Tags
                        </h5>
                        <div class="tags-container">
                            <?php foreach ($allTags as $t): ?>
                                <a href="tag.php?slug=<?php echo $t['slug']; ?>"
                                    class="sidebar-tag <?php echo $t['slug'] === $slug ? 'active' : ''; ?>"
                                    style="<?php echo $t['slug'] === $slug ? 'background: var(--primary); color: #fff;' : ''; ?>">
                                    #<?php echo $t['name']; ?>
                                    <span class="badge bg-secondary ms-1"><?php echo $t['article_count']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php include 'includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>