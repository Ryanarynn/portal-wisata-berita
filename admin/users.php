<?php
/**
 * =====================================================
 * ADMIN/USERS.PHP - Users Management
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: 
 * - Broken Access Control
 * - Information Disclosure (shows passwords in logs)
 */

require_once '../config.php';
require_once '../includes/functions.php';

// VULNERABILITY: Broken Access Control
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
}

// Get all users
$users = [];
$query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM articles WHERE author_id = u.id) as article_count,
                 (SELECT SUM(views) FROM articles WHERE author_id = u.id) as total_views
          FROM users u
          ORDER BY u.created_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - Admin <?php echo SITE_NAME; ?></title>
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
        }

        .admin-content {
            padding: 1.5rem;
        }

        .user-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-badge.admin {
            background: #fee2e2;
            color: #dc2626;
        }

        .role-badge.editor {
            background: #fef3c7;
            color: #d97706;
        }

        .role-badge.contributor {
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
            <a href="articles.php" class="nav-link"><i class="bi bi-file-text"></i> Artikel</a>
            <a href="categories.php" class="nav-link"><i class="bi bi-folder"></i> Kategori</a>
            <a href="comments.php" class="nav-link"><i class="bi bi-chat-dots"></i> Komentar</a>
            <a href="upload.php" class="nav-link"><i class="bi bi-cloud-upload"></i> Upload File</a>
            <div class="nav-section">Sistem</div>
            <a href="users.php" class="nav-link active"><i class="bi bi-people"></i> Users</a>
            <a href="settings.php" class="nav-link"><i class="bi bi-gear"></i> Settings</a>
            <div class="nav-section">Lainnya</div>
            <a href="../index.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Ke Website</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-power"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <header class="admin-header">
            <h4 class="mb-0"><i class="bi bi-people me-2"></i>Kelola Users</h4>
        </header>

        <div class="admin-content">
            <div class="row g-4">
                <?php foreach ($users as $user): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="user-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="user-avatar"
                                    style="background-color: <?php echo getAvatarColor($user['full_name']); ?>">
                                    <?php echo getInitials($user['full_name']); ?>
                                </div>
                                <div>
                                    <h6 class="mb-1"><?php echo safeOutput($user['full_name']); ?></h6>
                                    <small class="text-muted">@<?php echo safeOutput($user['username']); ?></small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <span
                                    class="role-badge <?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                            </div>
                            <div class="row text-center border-top pt-3">
                                <div class="col-6 border-end">
                                    <div class="h5 mb-0"><?php echo $user['article_count']; ?></div>
                                    <small class="text-muted">Artikel</small>
                                </div>
                                <div class="col-6">
                                    <div class="h5 mb-0"><?php echo formatViews($user['total_views'] ?? 0); ?></div>
                                    <small class="text-muted">Views</small>
                                </div>
                            </div>
                            <!-- VULNERABILITY: Shows password hint -->
                            <div class="mt-3 p-2 bg-light rounded small text-muted">
                                <i class="bi bi-key"></i> Password: <?php echo substr($user['password'], 0, 3); ?>***
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>