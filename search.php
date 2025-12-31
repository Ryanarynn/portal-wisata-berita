<?php
/**
 * =====================================================
 * SEARCH.PHP - Search Results Page
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: Reflected XSS (CWE-79)
 * Keyword ditampilkan tanpa sanitasi di beberapa tempat
 */

require_once 'config.php';
require_once 'includes/functions.php';

$keyword = isset($_GET['q']) ? $_GET['q'] : '';
$results = [];
$totalResults = 0;

if (!empty($keyword)) {
    $searchKeyword = '%' . $keyword . '%';

    // Search articles
    $stmt = $conn->prepare("SELECT a.*, c.name as category_name, u.full_name as author_name
                            FROM articles a
                            LEFT JOIN categories c ON a.category_id = c.id
                            LEFT JOIN users u ON a.author_id = u.id
                            WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
                            AND a.status = 'published'
                            ORDER BY a.views DESC, a.created_at DESC
                            LIMIT 20");
    $stmt->bind_param("sss", $searchKeyword, $searchKeyword, $searchKeyword);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $totalResults = count($results);
    $stmt->close();

    // Log search (Information Disclosure potential)
    logActivity($conn, 'search', "Search query: $keyword, Results: $totalResults");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian: <?php echo safeOutput($keyword); ?> - <?php echo SITE_NAME; ?></title>
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

    <!-- Search Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="bi bi-search me-2"></i>Hasil Pencarian</h1>
            <?php if (!empty($keyword)): ?>
                <p>Menampilkan hasil untuk: <strong>"<?php echo $keyword; ?>"</strong></p>
            <?php else: ?>
                <p>Masukkan kata kunci untuk mencari artikel</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Results Column -->
                <div class="col-lg-8">
                    <!-- Search Form -->
                    <div class="bg-white rounded-4 p-4 shadow-sm mb-4">
                        <form action="" method="GET" class="d-flex gap-2">
                            <input type="text" class="form-control form-control-lg" name="q"
                                placeholder="Cari artikel, berita, wisata..."
                                value="<?php echo safeOutput($keyword); ?>">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>

                    <?php if (empty($keyword)): ?>
                        <!-- No Search Yet -->
                        <div class="text-center py-5">
                            <i class="bi bi-search display-1 text-muted"></i>
                            <h3 class="mt-3">Mulai Pencarian</h3>
                            <p class="text-muted">Ketik kata kunci di atas untuk mencari artikel</p>
                        </div>

                    <?php elseif (empty($results)): ?>
                        <!-- No Results -->
                        <div class="text-center py-5">
                            <i class="bi bi-emoji-frown display-1 text-muted"></i>
                            <h3 class="mt-3">Tidak Ditemukan</h3>
                            <p class="text-muted">
                                Tidak ada hasil untuk "<strong><?php echo safeOutput($keyword); ?></strong>"
                            </p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-house me-1"></i> Kembali ke Beranda
                            </a>
                        </div>

                    <?php else: ?>
                        <!-- Results Found -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <p class="mb-0 text-muted">
                                Ditemukan <strong><?php echo $totalResults; ?></strong> artikel
                            </p>
                        </div>

                        <div class="row g-4">
                            <?php foreach ($results as $article): ?>
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
                                                <?php echo safeOutput(truncateText($article['excerpt'] ?? $article['content'], 80)); ?>
                                            </p>
                                            <div class="news-card-meta">
                                                <span>
                                                    <i class="bi bi-person"></i>
                                                    <?php echo safeOutput($article['author_name']); ?>
                                                </span>
                                                <span>
                                                    <i class="bi bi-eye"></i>
                                                    <?php echo formatViews($article['views']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
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