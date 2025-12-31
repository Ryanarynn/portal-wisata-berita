<?php
/**
 * =====================================================
 * FOOTER.PHP - Common Footer Component
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * Include file ini di semua halaman untuk footer yang konsisten
 * VULNERABILITY: Newsletter form dengan XSS
 */

// Get settings
$footerSettings = getSettings($conn, [
    'site_name',
    'site_tagline',
    'site_email',
    'site_phone',
    'site_address',
    'footer_copyright',
    'social_facebook',
    'social_twitter',
    'social_instagram',
    'social_youtube'
]);

// Get categories for footer
$footerCategories = getAllCategories($conn);

// Get popular tags
$popularTags = getPopularTags($conn, 8);
?>

<!-- Newsletter Section -->
<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-wrapper">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h3 class="newsletter-title">
                        <i class="bi bi-envelope-paper-heart me-2"></i>
                        Berlangganan Newsletter
                    </h3>
                    <p class="newsletter-text">
                        Dapatkan berita dan informasi wisata terbaru langsung di inbox Anda
                    </p>
                </div>
                <div class="col-lg-7">
                    <!-- VULNERABILITY: Form tanpa CSRF token -->
                    <form action="subscribe.php" method="POST" class="newsletter-form">
                        <div class="input-group">
                            <input type="text" class="form-control" name="name" placeholder="Nama Anda">
                            <input type="email" class="form-control" name="email" placeholder="email@anda.com" required>
                            <button class="btn btn-newsletter" type="submit">
                                <i class="bi bi-send me-1"></i>
                                Subscribe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Footer -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <div class="logo-icon">
                        <i class="bi bi-globe-asia-australia"></i>
                    </div>
                    <span><?php echo $footerSettings['site_name'] ?? SITE_NAME; ?></span>
                </div>
                <p class="footer-description">
                    <?php echo $footerSettings['site_tagline'] ?? 'Sumber informasi terpercaya untuk berita terkini dan destinasi wisata menarik di kota kita.'; ?>
                </p>

                <!-- Social Links -->
                <div class="footer-social">
                    <?php if (!empty($footerSettings['social_facebook'])): ?>
                        <a href="<?php echo $footerSettings['social_facebook']; ?>" target="_blank" class="social-link"
                            aria-label="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($footerSettings['social_twitter'])): ?>
                        <a href="<?php echo $footerSettings['social_twitter']; ?>" target="_blank" class="social-link"
                            aria-label="Twitter">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($footerSettings['social_instagram'])): ?>
                        <a href="<?php echo $footerSettings['social_instagram']; ?>" target="_blank" class="social-link"
                            aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($footerSettings['social_youtube'])): ?>
                        <a href="<?php echo $footerSettings['social_youtube']; ?>" target="_blank" class="social-link"
                            aria-label="YouTube">
                            <i class="bi bi-youtube"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Berita Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-title">Berita</h5>
                <ul class="footer-links">
                    <?php foreach (array_slice($footerCategories['berita'], 0, 5) as $cat): ?>
                        <li>
                            <a href="category.php?slug=<?php echo $cat['slug']; ?>">
                                <?php echo $cat['name']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Wisata Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-title">Wisata</h5>
                <ul class="footer-links">
                    <?php foreach (array_slice($footerCategories['wisata'], 0, 5) as $cat): ?>
                        <li>
                            <a href="category.php?slug=<?php echo $cat['slug']; ?>">
                                <?php echo $cat['name']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="about.php">Tentang Kami</a></li>
                    <li><a href="contact.php">Hubungi Kami</a></li>
                    <li><a href="authors.php">Penulis</a></li>
                    <li><a href="sitemap.php">Sitemap</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-title">Kontak</h5>
                <ul class="footer-contact">
                    <?php if (!empty($footerSettings['site_email'])): ?>
                        <li>
                            <i class="bi bi-envelope"></i>
                            <span><?php echo $footerSettings['site_email']; ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($footerSettings['site_phone'])): ?>
                        <li>
                            <i class="bi bi-telephone"></i>
                            <span><?php echo $footerSettings['site_phone']; ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($footerSettings['site_address'])): ?>
                        <li>
                            <i class="bi bi-geo-alt"></i>
                            <span><?php echo $footerSettings['site_address']; ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Tags Cloud -->
        <?php if (!empty($popularTags)): ?>
            <div class="footer-tags">
                <h6 class="footer-tags-title">Popular Tags</h6>
                <div class="tags-cloud">
                    <?php foreach ($popularTags as $tag): ?>
                        <a href="tag.php?slug=<?php echo $tag['slug']; ?>" class="tag-link">
                            #<?php echo $tag['name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="copyright">
                        <?php echo $footerSettings['footer_copyright'] ?? '© ' . date('Y') . ' Portal Wisata & Berita. All rights reserved.'; ?>
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="footer-bottom-links">
                        <a href="privacy.php">Privacy Policy</a>
                        <a href="terms.php">Terms of Service</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="btn-back-to-top" aria-label="Back to top">
    <i class="bi bi-chevron-up"></i>
</button>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
    // Back to Top
    const backToTop = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Navbar scroll effect for homepage
    const navbar = document.querySelector('.navbar-home');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // Dark Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const themeIcon = themeToggle?.querySelector('i');

    // Check saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    if (themeIcon) {
        themeIcon.className = savedTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }

    themeToggle?.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        if (themeIcon) {
            themeIcon.className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }
    });

    // Breaking News Ticker Animation
    const ticker = document.querySelector('.ticker-content');
    if (ticker) {
        // Clone items for seamless loop
        ticker.innerHTML += ticker.innerHTML;
    }

    // Search Modal Focus
    const searchModal = document.getElementById('searchModal');
    searchModal?.addEventListener('shown.bs.modal', () => {
        searchModal.querySelector('input[name="q"]')?.focus();
    });
</script>