<?php
/**
 * =====================================================
 * HEADER.PHP - Common Header Component
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * Include file ini di semua halaman untuk header yang konsisten
 * Berisi: Breaking news ticker, Navigation, Search
 */

// Get breaking news
$breakingNews = getBreakingNews($conn);

// Get categories for mega menu
$allCategories = getAllCategories($conn);

// Get site settings
$siteSettings = getSettings($conn, ['site_name', 'site_tagline']);
$siteName = $siteSettings['site_name'] ?? SITE_NAME;

// Determine current page for active nav
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Breaking News Ticker -->
<?php if (!empty($breakingNews)): ?>
    <div class="breaking-news-bar">
        <div class="container">
            <div class="breaking-news-wrapper">
                <span class="breaking-label">
                    <i class="bi bi-lightning-fill"></i>
                    BREAKING
                </span>
                <div class="breaking-ticker">
                    <div class="ticker-content">
                        <?php foreach ($breakingNews as $news): ?>
                            <a href="<?php echo $news['link']; ?>" class="ticker-item">
                                <?php echo $news['title']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Main Navigation -->
<nav class="navbar navbar-expand-lg <?php echo ($currentPage === 'index') ? 'navbar-home' : 'navbar-inner'; ?>">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="index.php">
            <div class="logo-icon">
                <i class="bi bi-globe-asia-australia"></i>
            </div>
            <span class="brand-text">
                <span class="brand-name"><?php echo $siteName; ?></span>
            </span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <!-- Home -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'index') ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house-door d-lg-none me-2"></i>Home
                    </a>
                </li>

                <!-- Berita Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo ($currentPage === 'berita' || $currentPage === 'category') ? 'active' : ''; ?>"
                        href="berita.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-newspaper d-lg-none me-2"></i>Berita
                    </a>
                    <ul class="dropdown-menu dropdown-menu-mega">
                        <li class="mega-menu-content">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h6 class="dropdown-header">Kategori Berita</h6>
                                    <div class="row">
                                        <?php foreach ($allCategories['berita'] as $cat): ?>
                                            <div class="col-6 col-lg-4">
                                                <a class="dropdown-item"
                                                    href="category.php?slug=<?php echo $cat['slug']; ?>">
                                                    <i class="<?php echo $cat['icon']; ?>"
                                                        style="color: <?php echo $cat['color']; ?>"></i>
                                                    <?php echo $cat['name']; ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-lg-4 mega-menu-side">
                                    <a href="berita.php" class="btn btn-outline-primary btn-sm w-100">
                                        Lihat Semua Berita <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>

                <!-- Wisata Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo ($currentPage === 'wisata') ? 'active' : ''; ?>"
                        href="wisata.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-geo-alt d-lg-none me-2"></i>Wisata
                    </a>
                    <ul class="dropdown-menu dropdown-menu-mega">
                        <li class="mega-menu-content">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h6 class="dropdown-header">Kategori Wisata</h6>
                                    <div class="row">
                                        <?php foreach ($allCategories['wisata'] as $cat): ?>
                                            <div class="col-6 col-lg-4">
                                                <a class="dropdown-item"
                                                    href="category.php?slug=<?php echo $cat['slug']; ?>">
                                                    <i class="<?php echo $cat['icon']; ?>"
                                                        style="color: <?php echo $cat['color']; ?>"></i>
                                                    <?php echo $cat['name']; ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-lg-4 mega-menu-side">
                                    <a href="wisata.php" class="btn btn-outline-success btn-sm w-100">
                                        Jelajahi Wisata <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>

                <!-- Trending -->
                <li class="nav-item">
                    <a class="nav-link" href="trending.php">
                        <i class="bi bi-fire d-lg-none me-2"></i>Trending
                    </a>
                </li>
            </ul>

            <!-- Right Side -->
            <div class="navbar-right d-flex align-items-center gap-2">
                <!-- Search Toggle -->
                <button class="btn btn-search" type="button" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="bi bi-search"></i>
                </button>

                <!-- Bookmark -->
                <a href="bookmark.php" class="btn btn-bookmark" title="Artikel Tersimpan">
                    <i class="bi bi-bookmark"></i>
                </a>

                <!-- Dark Mode Toggle -->
                <button class="btn btn-theme" id="themeToggle" title="Toggle Dark Mode">
                    <i class="bi bi-moon-fill"></i>
                </button>

                <!-- Login/User -->
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar"
                                style="background-color: <?php echo getAvatarColor(getCurrentUserName()); ?>">
                                <?php echo getInitials(getCurrentUserName()); ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo getCurrentUserName(); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isAdmin() || isEditor()): ?>
                                <li><a class="dropdown-item" href="admin/index.php"><i
                                            class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profil Saya</a>
                            </li>
                            <li><a class="dropdown-item" href="bookmark.php"><i
                                        class="bi bi-bookmark me-2"></i>Tersimpan</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i
                                        class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        <span>Login</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="searchModalLabel">
                    <i class="bi bi-search me-2"></i>Cari Artikel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="search.php" method="GET" class="search-form-modal">
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" name="q" placeholder="Ketik kata kunci pencarian..."
                            autocomplete="off" autofocus>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                    </div>
                </form>

                <div class="search-suggestions mt-4">
                    <h6 class="text-muted mb-3">Trending Searches</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="search.php?q=wisata" class="badge bg-light text-dark">wisata</a>
                        <a href="search.php?q=kuliner" class="badge bg-light text-dark">kuliner</a>
                        <a href="search.php?q=politik" class="badge bg-light text-dark">politik</a>
                        <a href="search.php?q=teknologi" class="badge bg-light text-dark">teknologi</a>
                        <a href="search.php?q=ekonomi" class="badge bg-light text-dark">ekonomi</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>