<?php
/**
 * =====================================================
 * BOOKMARK.PHP - User Bookmarks Page
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Handle bookmark toggle via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'toggle') {
        $articleId = (int) $_POST['article_id'];
        $added = toggleBookmark($conn, $articleId);
        echo json_encode(['success' => true, 'added' => $added]);
        exit;
    }

    if ($_POST['action'] === 'remove') {
        $articleId = (int) $_POST['article_id'];
        $sessionId = session_id();
        $conn->query("DELETE FROM bookmarks WHERE session_id = '$sessionId' AND article_id = $articleId");
        echo json_encode(['success' => true]);
        exit;
    }
}

// Get user bookmarks
$bookmarks = getUserBookmarks($conn);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel Tersimpan - <?php echo SITE_NAME; ?></title>
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
            <h1><i class="bi bi-bookmark-heart me-2"></i>Artikel Tersimpan</h1>
            <p>Artikel yang Anda simpan untuk dibaca nanti</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content py-5">
        <div class="container">
            <?php if (empty($bookmarks)): ?>
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-bookmark display-1 text-muted"></i>
                    </div>
                    <h3>Belum Ada Artikel Tersimpan</h3>
                    <p class="text-muted mb-4">
                        Anda belum menyimpan artikel apapun. Mulai jelajahi dan simpan artikel favorit Anda!
                    </p>
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-house me-1"></i> Jelajahi Artikel
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0 text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        <?php echo count($bookmarks); ?> artikel tersimpan
                    </p>
                </div>

                <div class="row g-4" id="bookmarksList">
                    <?php foreach ($bookmarks as $article): ?>
                        <div class="col-md-6 col-lg-4" data-article-id="<?php echo $article['id']; ?>">
                            <article class="news-card position-relative">
                                <button class="btn btn-sm btn-danger position-absolute end-0 top-0 m-2 remove-bookmark"
                                    data-id="<?php echo $article['id']; ?>" style="z-index: 10;">
                                    <i class="bi bi-x-lg"></i>
                                </button>
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
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Remove bookmark
        document.querySelectorAll('.remove-bookmark').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const articleId = btn.dataset.id;
                const card = btn.closest('[data-article-id]');

                try {
                    const response = await fetch('bookmark.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=remove&article_id=${articleId}`
                    });

                    if (response.ok) {
                        card.style.transition = 'all 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            card.remove();
                            // Check if list is empty
                            if (document.querySelectorAll('[data-article-id]').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });
    </script>
</body>

</html>