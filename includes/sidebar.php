<?php
/**
 * =====================================================
 * SIDEBAR.PHP - Sidebar Widgets Component
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * Reusable sidebar dengan trending, categories, tags
 */

// Get trending articles
$sidebarTrending = getTrendingArticles($conn, 5);

// Get categories
$sidebarCategories = getAllCategories($conn);

// Get popular tags
$sidebarTags = getPopularTags($conn, 12);
?>

<!-- Sidebar -->
<aside class="sidebar">
    <!-- Trending Widget -->
    <div class="sidebar-widget">
        <h5 class="sidebar-title">
            <i class="bi bi-fire text-danger"></i>
            Trending
        </h5>
        <div class="trending-list">
            <?php foreach ($sidebarTrending as $index => $article): ?>
                <div class="trending-item">
                    <span class="trending-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                    <div class="trending-content">
                        <a href="view.php?id=<?php echo $article['id']; ?>" class="trending-title">
                            <?php echo safeOutput($article['title']); ?>
                        </a>
                        <div class="trending-meta">
                            <span><i class="bi bi-eye"></i> <?php echo formatViews($article['views']); ?></span>
                            <span><i class="bi bi-clock"></i> <?php echo timeAgo($article['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Categories Widget -->
    <div class="sidebar-widget">
        <h5 class="sidebar-title">
            <i class="bi bi-folder text-primary"></i>
            Kategori
        </h5>

        <!-- Berita Categories -->
        <h6 class="category-group-title">Berita</h6>
        <ul class="category-list">
            <?php foreach ($sidebarCategories['berita'] as $cat): ?>
                <li>
                    <a href="category.php?slug=<?php echo $cat['slug']; ?>">
                        <i class="<?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>"></i>
                        <span><?php echo $cat['name']; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Wisata Categories -->
        <h6 class="category-group-title mt-3">Wisata</h6>
        <ul class="category-list">
            <?php foreach ($sidebarCategories['wisata'] as $cat): ?>
                <li>
                    <a href="category.php?slug=<?php echo $cat['slug']; ?>">
                        <i class="<?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>"></i>
                        <span><?php echo $cat['name']; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Tags Widget -->
    <div class="sidebar-widget">
        <h5 class="sidebar-title">
            <i class="bi bi-tags text-info"></i>
            Popular Tags
        </h5>
        <div class="tags-container">
            <?php foreach ($sidebarTags as $tag): ?>
                <a href="tag.php?slug=<?php echo $tag['slug']; ?>" class="sidebar-tag">
                    #<?php echo $tag['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Ad Space / Promo Widget -->
    <div class="sidebar-widget sidebar-promo">
        <div class="promo-content">
            <i class="bi bi-megaphone"></i>
            <h5>Pasang Iklan</h5>
            <p>Jangkau ribuan pembaca setiap harinya</p>
            <a href="contact.php" class="btn btn-sm btn-light">Hubungi Kami</a>
        </div>
    </div>

    <!-- Social Follow Widget -->
    <div class="sidebar-widget">
        <h5 class="sidebar-title">
            <i class="bi bi-share text-success"></i>
            Follow Us
        </h5>
        <div class="social-follow">
            <a href="#" class="social-follow-item facebook">
                <i class="bi bi-facebook"></i>
                <span>15K</span>
            </a>
            <a href="#" class="social-follow-item twitter">
                <i class="bi bi-twitter-x"></i>
                <span>8K</span>
            </a>
            <a href="#" class="social-follow-item instagram">
                <i class="bi bi-instagram"></i>
                <span>12K</span>
            </a>
            <a href="#" class="social-follow-item youtube">
                <i class="bi bi-youtube"></i>
                <span>5K</span>
            </a>
        </div>
    </div>
</aside>