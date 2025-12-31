<?php
/**
 * =====================================================
 * ADMIN/COMMENTS.PHP - Comments Moderation
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: Stored XSS, Broken Access Control
 */

require_once '../config.php';
require_once '../includes/functions.php';

// VULNERABILITY: Broken Access Control
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $conn->query("DELETE FROM comments WHERE id = " . (int) $_GET['delete']);
    header('Location: comments.php?deleted=1');
    exit;
}

// Get comments with article info
$comments = [];
$query = "SELECT c.*, a.title as article_title, a.id as article_id
          FROM comments c
          JOIN articles a ON c.article_id = a.id
          ORDER BY c.created_at DESC
          LIMIT 50";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Komentar - Admin <?php echo SITE_NAME; ?></title>
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

        .comment-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
            <a href="comments.php" class="nav-link active"><i class="bi bi-chat-dots"></i> Komentar</a>
            <a href="upload.php" class="nav-link"><i class="bi bi-cloud-upload"></i> Upload File</a>
            <div class="nav-section">Sistem</div>
            <a href="users.php" class="nav-link"><i class="bi bi-people"></i> Users</a>
            <a href="settings.php" class="nav-link"><i class="bi bi-gear"></i> Settings</a>
            <div class="nav-section">Lainnya</div>
            <a href="../index.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Ke Website</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-power"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <header class="admin-header">
            <h4 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Moderasi Komentar</h4>
        </header>

        <div class="admin-content">
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Komentar berhasil dihapus!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($comments)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-chat-left-text display-1 text-muted"></i>
                    <h4 class="mt-3">Belum Ada Komentar</h4>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-card">
                        <div class="d-flex gap-3">
                            <div class="comment-avatar"
                                style="background-color: <?php echo getAvatarColor($comment['name']); ?>">
                                <?php echo getInitials($comment['name']); ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong><?php echo safeOutput($comment['name']); ?></strong>
                                        <small class="text-muted ms-2"><?php echo $comment['email']; ?></small>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($comment['created_at']); ?></small>
                                </div>
                                <p class="mb-2 text-muted small">
                                    Pada: <a href="../view.php?id=<?php echo $comment['article_id']; ?>" target="_blank">
                                        <?php echo safeOutput(truncateText($comment['article_title'], 50)); ?>
                                    </a>
                                </p>
                                <!-- VULNERABILITY: Stored XSS - Comment displayed without sanitization -->
                                <div class="p-2 bg-light rounded mb-2">
                                    <?php echo $comment['comment']; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="../view.php?id=<?php echo $comment['article_id']; ?>#comments"
                                        class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                    <a href="comments.php?delete=<?php echo $comment['id']; ?>"
                                        class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus komentar ini?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>