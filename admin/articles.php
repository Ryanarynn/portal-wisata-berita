<?php
/**
 * =====================================================
 * ADMIN/ARTICLES.PHP - Articles Management
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: Broken Access Control
 */

require_once '../config.php';
require_once '../includes/functions.php';

// VULNERABILITY: Broken Access Control - no proper role check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // VULNERABILITY: No CSRF token, no authorization check
    $deleteId = (int) $_GET['delete'];
    $conn->query("DELETE FROM articles WHERE id = $deleteId");
    header('Location: articles.php?deleted=1');
    exit;
}

// Filters
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$where = "1=1";
if ($typeFilter)
    $where .= " AND a.type = '$typeFilter'";
if ($statusFilter)
    $where .= " AND a.status = '$statusFilter'";
if ($search)
    $where .= " AND (a.title LIKE '%$search%' OR a.content LIKE '%$search%')";

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get total
$result = $conn->query("SELECT COUNT(*) as total FROM articles a WHERE $where");
$totalArticles = $result->fetch_assoc()['total'];
$totalPages = ceil($totalArticles / $perPage);

// Get articles
$articles = [];
$query = "SELECT a.*, c.name as category_name, u.full_name as author_name
          FROM articles a
          LEFT JOIN categories c ON a.category_id = c.id
          LEFT JOIN users u ON a.author_id = u.id
          WHERE $where
          ORDER BY a.created_at DESC
          LIMIT $offset, $perPage";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}

// Stats
$statsQuery = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
FROM articles");
$stats = $statsQuery->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel - Admin <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-dark: #152a45;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
        }

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

        .stat-mini {
            background: #fff;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-mini .icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-mini .value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stat-mini .label {
            font-size: 0.75rem;
            color: #64748b;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.published {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.draft {
            background: #fef3c7;
            color: #d97706;
        }

        .status-badge.pending {
            background: #e0e7ff;
            color: #4f46e5;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="../index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-globe-asia-australia"></i></div>
            <span class="brand-text">Portal Admin</span>
        </a>
        <nav class="nav flex-column">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <div class="nav-section">Konten</div>
            <a href="articles.php" class="nav-link active"><i class="bi bi-file-text"></i> Artikel</a>
            <a href="categories.php" class="nav-link"><i class="bi bi-folder"></i> Kategori</a>
            <a href="comments.php" class="nav-link"><i class="bi bi-chat-dots"></i> Komentar</a>
            <a href="upload.php" class="nav-link"><i class="bi bi-cloud-upload"></i> Upload File</a>
            <div class="nav-section">Sistem</div>
            <a href="users.php" class="nav-link"><i class="bi bi-people"></i> Users</a>
            <a href="settings.php" class="nav-link"><i class="bi bi-gear"></i> Settings</a>
            <div class="nav-section">Lainnya</div>
            <a href="../index.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Ke Website</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-power"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <h4 class="mb-0"><i class="bi bi-file-text me-2"></i>Kelola Artikel</h4>
            <a href="article-form.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Artikel Baru</a>
        </header>

        <div class="admin-content">
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Artikel berhasil dihapus!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-mini">
                        <div class="icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-text"></i></div>
                        <div>
                            <div class="value"><?php echo $stats['total']; ?></div>
                            <div class="label">Total</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-mini">
                        <div class="icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <div class="value"><?php echo $stats['published']; ?></div>
                            <div class="label">Published</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-mini">
                        <div class="icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-pencil"></i></div>
                        <div>
                            <div class="value"><?php echo $stats['draft']; ?></div>
                            <div class="label">Draft</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-mini">
                        <div class="icon bg-info bg-opacity-10 text-info"><i class="bi bi-hourglass"></i></div>
                        <div>
                            <div class="value"><?php echo $stats['pending']; ?></div>
                            <div class="label">Pending</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Cari artikel..."
                                value="<?php echo safeOutput($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="type">
                                <option value="">Semua Tipe</option>
                                <option value="berita" <?php echo $typeFilter === 'berita' ? 'selected' : ''; ?>>Berita
                                </option>
                                <option value="wisata" <?php echo $typeFilter === 'wisata' ? 'selected' : ''; ?>>Wisata
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>
                                    Published</option>
                                <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft
                                </option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>
                                    Pending</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i>
                                Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Articles Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Tanggal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?php echo safeOutput(truncateText($article['title'], 50)); ?></div>
                                        <small class="text-muted"><?php echo $article['author_name']; ?></small>
                                    </td>
                                    <td><?php echo safeOutput($article['category_name']); ?></td>
                                    <td>
                                        <span
                                            class="badge bg-<?php echo $article['type'] === 'berita' ? 'primary' : 'success'; ?>">
                                            <?php echo ucfirst($article['type']); ?>
                                        </span>
                                    </td>
                                    <td><span
                                            class="status-badge <?php echo $article['status']; ?>"><?php echo ucfirst($article['status']); ?></span>
                                    </td>
                                    <td><?php echo formatViews($article['views']); ?></td>
                                    <td><small><?php echo formatTanggal($article['created_at']); ?></small></td>
                                    <td class="text-end">
                                        <a href="../view.php?id=<?php echo $article['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                                class="bi bi-eye"></i></a>
                                        <a href="article-form.php?id=<?php echo $article['id']; ?>"
                                            class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="articles.php?delete=<?php echo $article['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus artikel ini?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($articles)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada artikel</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&type=<?php echo $typeFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>