<?php
/**
 * =====================================================
 * VIEW.PHP - Single Article Page
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: 
 * - IDOR (CWE-639): Can view draft/pending articles
 * - Stored XSS: Comments displayed without sanitization
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Get article ID
$articleId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$articleId) {
    header('Location: index.php');
    exit;
}

// VULNERABILITY: IDOR - No status check, can view draft/pending
$article = getArticleById($conn, $articleId);

if (!$article) {
    header('Location: 404.php');
    exit;
}

// Increment view count
$conn->query("UPDATE articles SET views = views + 1 WHERE id = $articleId");

// Handle comment submission
$commentMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';

    // VULNERABILITY: No sanitization - Stored XSS possible
    if (!empty($name) && !empty($email) && !empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (article_id, name, email, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $articleId, $name, $email, $comment);
        if ($stmt->execute()) {
            $commentMessage = 'success';
        }
        $stmt->close();
    }
}

// Get comments
$comments = [];
$commentQuery = $conn->query("SELECT * FROM comments WHERE article_id = $articleId ORDER BY created_at DESC");
while ($row = $commentQuery->fetch_assoc()) {
    $comments[] = $row;
}

// Get related articles
$relatedArticles = [];
$relatedQuery = $conn->query("SELECT a.*, c.name as category_name 
                              FROM articles a 
                              LEFT JOIN categories c ON a.category_id = c.id 
                              WHERE a.category_id = {$article['category_id']} 
                              AND a.id != $articleId 
                              AND a.status = 'published' 
                              ORDER BY a.created_at DESC LIMIT 4");
while ($row = $relatedQuery->fetch_assoc()) {
    $relatedArticles[] = $row;
}

// Get article tags
$articleTags = getArticleTags($conn, $articleId);

// Check if bookmarked
$isBookmarked = isBookmarked($conn, $articleId);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="<?php echo safeOutput(truncateText($article['excerpt'] ?? $article['content'], 160)); ?>">
    <title><?php echo safeOutput($article['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .article-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 4rem 0 3rem;
            color: #fff;
        }

        .article-category {
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

        .article-title {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.3;
        }

        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .article-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
        }

        .article-content {
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-top: -2rem;
            box-shadow: var(--shadow);
        }

        .article-body {
            font-size: 1.0625rem;
            line-height: 1.8;
            color: var(--text-dark);
        }

        .article-body p {
            margin-bottom: 1.25rem;
        }

        .article-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .article-tag {
            background: var(--bg-light);
            color: var(--text-muted);
            padding: 0.375rem 0.875rem;
            border-radius: 50px;
            font-size: 0.8125rem;
            text-decoration: none;
        }

        .article-tag:hover {
            background: var(--primary);
            color: #fff;
        }

        .share-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: var(--transition);
        }

        .share-btn:hover {
            transform: scale(1.1);
            color: #fff;
        }

        .share-btn.facebook {
            background: #1877f2;
        }

        .share-btn.twitter {
            background: #1da1f2;
        }

        .share-btn.whatsapp {
            background: #25d366;
        }

        .share-btn.copy {
            background: #64748b;
        }

        .comment-card {
            background: var(--bg-light);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .comment-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
        }

        .draft-warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 1rem;
            color: #92400e;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Article Header -->
    <header class="article-header">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <span class="article-category">
                        <i
                            class="<?php echo $article['type'] === 'berita' ? 'bi bi-newspaper' : 'bi bi-geo-alt'; ?> me-1"></i>
                        <?php echo safeOutput($article['category_name']); ?>
                    </span>
                    <h1 class="article-title"><?php echo safeOutput($article['title']); ?></h1>
                    <div class="article-meta">
                        <div class="article-meta-item">
                            <div class="author-avatar"
                                style="background-color: <?php echo getAvatarColor($article['author_name']); ?>">
                                <?php echo getInitials($article['author_name']); ?>
                            </div>
                            <div>
                                <a href="author.php?id=<?php echo $article['author_id']; ?>"
                                    class="text-white text-decoration-none fw-medium">
                                    <?php echo safeOutput($article['author_name']); ?>
                                </a>
                            </div>
                        </div>
                        <div class="article-meta-item">
                            <i class="bi bi-calendar3"></i>
                            <?php echo formatTanggal($article['created_at']); ?>
                        </div>
                        <div class="article-meta-item">
                            <i class="bi bi-eye"></i>
                            <?php echo formatViews($article['views']); ?> views
                        </div>
                        <div class="article-meta-item">
                            <i class="bi bi-chat"></i>
                            <?php echo count($comments); ?> komentar
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content py-4">
        <div class="container">
            <div class="row g-4">
                <!-- Article Body -->
                <div class="col-lg-8">
                    <article class="article-content">
                        <div class="article-body">
                            <?php echo nl2br($article['content']); ?>
                        </div>

                        <!-- Tags -->
                        <?php if (!empty($articleTags)): ?>
                            <div class="article-tags">
                                <span class="text-muted me-2"><i class="bi bi-tags"></i> Tags:</span>
                                <?php foreach ($articleTags as $tag): ?>
                                    <a href="tag.php?slug=<?php echo $tag['slug']; ?>" class="article-tag">
                                        #<?php echo $tag['name']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Share -->
                        <div class="share-buttons">
                            <span class="text-muted me-2 align-self-center"><i class="bi bi-share"></i> Share:</span>
                            <a href="https://facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'view.php?id=' . $articleId); ?>"
                                class="share-btn facebook" target="_blank"><i class="bi bi-facebook"></i></a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . 'view.php?id=' . $articleId); ?>&text=<?php echo urlencode($article['title']); ?>"
                                class="share-btn twitter" target="_blank"><i class="bi bi-twitter-x"></i></a>
                            <a href="https://wa.me/?text=<?php echo urlencode($article['title'] . ' ' . SITE_URL . 'view.php?id=' . $articleId); ?>"
                                class="share-btn whatsapp" target="_blank"><i class="bi bi-whatsapp"></i></a>
                            <button class="share-btn copy"
                                onclick="navigator.clipboard.writeText(window.location.href); alert('Link copied!')"><i
                                    class="bi bi-link-45deg"></i></button>
                        </div>
                    </article>

                    <!-- Comments Section -->
                    <section class="mt-4" id="comments">
                        <div class="bg-white rounded-4 p-4 shadow-sm">
                            <h4 class="mb-4">
                                <i class="bi bi-chat-dots me-2"></i>
                                Komentar (<?php echo count($comments); ?>)
                            </h4>

                            <?php if ($commentMessage === 'success'): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-1"></i> Komentar berhasil ditambahkan!
                                </div>
                            <?php endif; ?>

                            <!-- Comment Form -->
                            <form method="POST" action="#comments" class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="name" placeholder="Nama Anda"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="email" class="form-control" name="email" placeholder="Email Anda"
                                            required>
                                    </div>
                                    <div class="col-12">
                                        <textarea class="form-control" name="comment" rows="4"
                                            placeholder="Tulis komentar Anda..." required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send me-1"></i> Kirim Komentar
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Comments List -->
                            <?php if (empty($comments)): ?>
                                <p class="text-muted text-center py-4">Belum ada komentar. Jadilah yang pertama!</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-card">
                                        <div class="d-flex gap-3">
                                            <div class="comment-avatar"
                                                style="background-color: <?php echo getAvatarColor($comment['name']); ?>">
                                                <?php echo getInitials($comment['name']); ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <strong><?php echo safeOutput($comment['name']); ?></strong>
                                                    <small
                                                        class="text-muted"><?php echo timeAgo($comment['created_at']); ?></small>
                                                </div>
                                                <!-- VULNERABILITY: Stored XSS - Comment not sanitized -->
                                                <div class="comment-text"><?php echo $comment['comment']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Related Articles -->
                    <?php if (!empty($relatedArticles)): ?>
                        <section class="mt-4">
                            <h4 class="mb-4"><i class="bi bi-collection me-2"></i>Artikel Terkait</h4>
                            <div class="row g-3">
                                <?php foreach ($relatedArticles as $related): ?>
                                    <div class="col-md-6">
                                        <div class="bg-white rounded-3 p-3 shadow-sm d-flex gap-3">
                                            <div class="flex-shrink-0"
                                                style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-<?php echo $related['type'] === 'berita' ? 'newspaper' : 'geo-alt'; ?> text-white"
                                                    style="font-size: 1.5rem;"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="view.php?id=<?php echo $related['id']; ?>"
                                                        class="text-decoration-none text-dark">
                                                        <?php echo safeOutput(truncateText($related['title'], 50)); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted"><?php echo timeAgo($related['created_at']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
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