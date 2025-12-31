<?php
/**
 * =====================================================
 * ADMIN/INDEX.PHP - Admin Dashboard
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: Broken Access Control (CWE-284)
 * - Tidak ada pengecekan session yang proper
 * - Contributor dapat mengakses halaman admin
 * - Role check dapat di-bypass
 */

require_once '../config.php';
require_once '../includes/functions.php';

// VULNERABILITY: Broken Access Control
// Hanya cek apakah user login, TIDAK cek role
// Seharusnya: if (!isLoggedIn() || !isAdmin()) { ... }
if (!isset($_SESSION['user_id'])) {
    // VULNERABILITY: Redirect bisa di-bypass
    // Kode di bawah TETAP dieksekusi karena tidak ada exit setelah header
    header('Location: ../login.php');
    // MISSING: exit; 
}

// Get dashboard statistics
$stats = [];

// Total articles
$result = $conn->query("SELECT COUNT(*) as count FROM articles");
$stats['articles'] = $result->fetch_assoc()['count'];

// Total views
$result = $conn->query("SELECT SUM(views) as total FROM articles");
$stats['views'] = $result->fetch_assoc()['total'] ?? 0;

// Total comments
$result = $conn->query("SELECT COUNT(*) as count FROM comments");
$stats['comments'] = $result->fetch_assoc()['count'];

// Total subscribers
$result = $conn->query("SELECT COUNT(*) as count FROM subscribers WHERE status = 'active'");
$stats['subscribers'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['users'] = $result->fetch_assoc()['count'];

// Recent articles
$recentArticles = getRecentArticles($conn, 5);

// Recent comments
$recentComments = [];
$query = "SELECT c.*, a.title as article_title 
          FROM comments c 
          JOIN articles a ON c.article_id = a.id 
          ORDER BY c.created_at DESC LIMIT 5";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $recentComments[] = $row;
}

// Recent activity (Information Disclosure)
$recentActivity = [];
$query = "SELECT al.*, u.username 
          FROM activity_log al 
          LEFT JOIN users u ON al.user_id = u.id 
          ORDER BY al.created_at DESC LIMIT 10";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $recentActivity[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-dark: #152a45;
            --secondary: #047857;
            --accent: #0ea5e9;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
        }

        /* Sidebar */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 1.5rem;
            overflow-y: auto;
            z-index: 1000;
        }

        .admin-sidebar .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #fff;
            text-decoration: none;
            margin-bottom: 2rem;
        }

        .admin-sidebar .brand-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-sidebar .brand-text {
            font-weight: 700;
            font-size: 1.125rem;
        }

        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .admin-sidebar .nav-section {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 1.5rem 0 0.75rem;
            padding-left: 1rem;
        }

        /* Main Content */
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .admin-header {
            background: #fff;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-content {
            padding: 1.5rem;
        }

        /* Stats Cards */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-card .stat-label {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Cards */
        .admin-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .admin-card .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .admin-card .card-body {
            padding: 1.25rem;
        }

        /* Activity List */
        .activity-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="../index.php" class="brand">
            <div class="brand-icon">
                <i class="bi bi-globe-asia-australia"></i>
            </div>
            <span class="brand-text">Portal Admin</span>
        </a>

        <nav class="nav flex-column">
            <a href="index.php" class="nav-link active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="nav-section">Konten</div>
            <a href="articles.php" class="nav-link">
                <i class="bi bi-file-text"></i> Artikel
            </a>
            <a href="categories.php" class="nav-link">
                <i class="bi bi-folder"></i> Kategori
            </a>
            <a href="comments.php" class="nav-link">
                <i class="bi bi-chat-dots"></i> Komentar
            </a>
            <a href="upload.php" class="nav-link">
                <i class="bi bi-cloud-upload"></i> Upload File
            </a>

            <div class="nav-section">Sistem</div>
            <a href="users.php" class="nav-link">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="subscribers.php" class="nav-link">
                <i class="bi bi-envelope"></i> Subscribers
            </a>
            <a href="settings.php" class="nav-link">
                <i class="bi bi-gear"></i> Settings
            </a>
            <a href="logs.php" class="nav-link">
                <i class="bi bi-journal-text"></i> Activity Log
            </a>

            <div class="nav-section">Lainnya</div>
            <a href="../index.php" class="nav-link">
                <i class="bi bi-box-arrow-left"></i> Ke Website
            </a>
            <a href="../logout.php" class="nav-link text-danger">
                <i class="bi bi-power"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div>
                <h4 class="mb-0">Dashboard</h4>
                <small class="text-muted">Selamat datang, <?php echo $_SESSION['full_name'] ?? 'User'; ?></small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span
                    class="badge bg-<?php echo $_SESSION['role'] === 'admin' ? 'danger' : ($_SESSION['role'] === 'editor' ? 'warning' : 'secondary'); ?>">
                    <?php echo ucfirst($_SESSION['role'] ?? 'guest'); ?>
                </span>
            </div>
        </header>

        <!-- Content -->
        <div class="admin-content">
            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo number_format($stats['articles']); ?></div>
                            <div class="stat-label">Total Artikel</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-eye"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo formatViews($stats['views']); ?></div>
                            <div class="stat-label">Total Views</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo number_format($stats['comments']); ?></div>
                            <div class="stat-label">Komentar</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo number_format($stats['subscribers']); ?></div>
                            <div class="stat-label">Subscribers</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Articles -->
                <div class="col-lg-6">
                    <div class="admin-card">
                        <div class="card-header">
                            <span><i class="bi bi-file-text me-2"></i>Artikel Terbaru</span>
                            <a href="articles.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentArticles as $article): ?>
                                    <a href="article-form.php?id=<?php echo $article['id']; ?>"
                                        class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php echo safeOutput(truncateText($article['title'], 50)); ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-eye"></i> <?php echo formatViews($article['views']); ?>
                                                    •
                                                    <?php echo timeAgo($article['created_at']); ?>
                                                </small>
                                            </div>
                                            <span
                                                class="badge bg-<?php echo $article['type'] === 'berita' ? 'primary' : 'success'; ?>">
                                                <?php echo ucfirst($article['type']); ?>
                                            </span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Log (Information Disclosure) -->
                <div class="col-lg-6">
                    <div class="admin-card">
                        <div class="card-header">
                            <span><i class="bi bi-activity me-2"></i>Activity Log</span>
                            <a href="logs.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recentActivity as $log): ?>
                                <div class="activity-item">
                                    <div class="activity-icon bg-light">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo $log['username'] ?? 'System'; ?></strong>
                                            <small class="text-muted"><?php echo timeAgo($log['created_at']); ?></small>
                                        </div>
                                        <div class="text-muted small"><?php echo $log['action']; ?></div>
                                        <!-- VULNERABILITY: Information Disclosure - Shows sensitive details -->
                                        <div class="text-muted small text-truncate"><?php echo $log['details']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="admin-card">
                        <div class="card-header">
                            <span><i class="bi bi-lightning me-2"></i>Quick Actions</span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="article-form.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Artikel Baru
                                </a>
                                <a href="upload.php" class="btn btn-success">
                                    <i class="bi bi-upload me-1"></i> Upload File
                                </a>
                                <a href="comments.php" class="btn btn-warning">
                                    <i class="bi bi-chat me-1"></i> Moderasi Komentar
                                </a>
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="bi bi-gear me-1"></i> Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>