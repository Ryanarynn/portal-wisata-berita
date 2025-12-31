<?php
/**
 * =====================================================
 * AUTHOR.PHP - Author Profile Page
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Get author ID from URL
$authorId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$authorId) {
    header('Location: index.php');
    exit;
}

// Get author info
$stmt = $conn->prepare("SELECT id, username, full_name, email, bio, avatar, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $authorId);
$stmt->execute();
$result = $stmt->get_result();
$author = $result->fetch_assoc();
$stmt->close();

if (!$author) {
    header('Location: index.php');
    exit;
}

// Get author stats
$statsQuery = $conn->query("SELECT 
    COUNT(*) as total_articles,
    SUM(views) as total_views,
    (SELECT COUNT(*) FROM comments c JOIN articles a ON c.article_id = a.id WHERE a.author_id = $authorId) as total_comments
FROM articles WHERE author_id = $authorId AND status = 'published'");
$stats = $statsQuery->fetch_assoc();

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Get total articles
$result = $conn->query("SELECT COUNT(*) as total FROM articles WHERE author_id = $authorId AND status = 'published'");
$totalArticles = $result->fetch_assoc()['total'];
$totalPages = ceil($totalArticles / $perPage);

// Get author's articles
$articles = [];
$query = "SELECT a.*, c.name as category_name, c.slug as category_slug
          FROM articles a
          LEFT JOIN categories c ON a.category_id = c.id
          WHERE a.author_id = $authorId AND a.status = 'published'
          ORDER BY a.created_at DESC
          LIMIT $offset, $perPage";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Profil penulis <?php echo safeOutput($author['full_name']); ?> di <?php echo SITE_NAME; ?>">
    <title><?php echo safeOutput($author['full_name']); ?> - Penulis - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .author-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 4rem 0;
            color: #fff;
        }

        .author-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: #fff;
            border: 4px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 1rem;
        }

        .author-name {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .author-role {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .author-role.admin {
            background: #dc2626;
        }

        .author-role.editor {
            background: #f59e0b;
        }

        .author-role.contributor {
            background: #6366f1;
        }

        .author-bio {
            max-width: 600px;
            margin: 1rem auto 0;
            color: rgba(255, 255, 255, 0.9);
        }

        .author-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .stat-label {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Author Header -->
    <section class="author-header text-center">
        <div class="container">
            <div class="author-avatar" style="background-color: <?php echo getAvatarColor($author['full_name']); ?>">
                <?php echo getInitials($author['full_name']); ?>
            </div>
            <h1 class="author-name"><?php echo safeOutput($author['full_name']); ?></h1>
            <span class="author-role <?php echo $author['role']; ?>"><?php echo ucfirst($author['role']); ?></span>
            <?php if (!empty($author['bio'])): ?>
                <p class="author-bio"><?php echo safeOutput($author['bio']); ?></p>
            <?php endif; ?>

            <div class="author-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($stats['total_articles']); ?></div>
                    <div class="stat-label">Artikel</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo formatViews($stats['total_views'] ?? 0); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($stats['total_comments']); ?></div>
                    <div class="stat-label">Komentar</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Author Articles -->
    <div class="main-content py-5">
        <div class="container">
            <h2 class="mb-4">
                <i class="bi bi-journal-text me-2"></i>
                Artikel oleh <?php echo safeOutput($author['full_name']); ?>
            </h2>

            <?php if (empty($articles)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal-x display-1 text-muted"></i>
                    <h3 class="mt-3">Belum Ada Artikel</h3>
                    <p class="text-muted">Penulis ini belum mempublikasikan artikel.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($articles as $article): ?>
                        <div class="col-md-6 col-lg-4">
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
                                    <span class="news-card-category"><?php echo safeOutput($article['category_name']); ?></span>
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
                                        <span><i class="bi bi-clock"></i> <?php echo timeAgo($article['created_at']); ?></span>
                                        <span><i class="bi bi-eye"></i> <?php echo formatViews($article['views']); ?></span>
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
                                    <a class="page-link" href="?id=<?php echo $authorId; ?>&page=<?php echo $page - 1; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?id=<?php echo $authorId; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?php echo $authorId; ?>&page=<?php echo $page + 1; ?>">
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