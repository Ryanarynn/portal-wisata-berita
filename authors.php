<?php
/**
 * =====================================================
 * AUTHORS.PHP - All Authors Listing Page
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Get all authors with article count
$authors = [];
$query = "SELECT u.id, u.username, u.full_name, u.bio, u.role, u.created_at,
                 COUNT(a.id) as article_count,
                 COALESCE(SUM(a.views), 0) as total_views
          FROM users u
          LEFT JOIN articles a ON u.id = a.author_id AND a.status = 'published'
          GROUP BY u.id
          ORDER BY article_count DESC, u.full_name ASC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $authors[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Daftar penulis dan kontributor di <?php echo SITE_NAME; ?>">
    <title>Penulis & Kontributor - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .author-card {
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
        }

        .author-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .author-card .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin: 0 auto 1rem;
        }

        .author-card .name {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .author-card .role {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }

        .author-card .role.admin {
            background: #fee2e2;
            color: #dc2626;
        }

        .author-card .role.editor {
            background: #fef3c7;
            color: #d97706;
        }

        .author-card .role.contributor {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .author-card .bio {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .author-card .stats {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .author-card .stat-item {
            text-align: center;
        }

        .author-card .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .author-card .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="bi bi-people me-2"></i>Penulis & Kontributor</h1>
            <p>Tim kreatif di balik konten portal kami</p>
        </div>
    </section>

    <!-- Authors List -->
    <div class="main-content py-5">
        <div class="container">
            <div class="row g-4">
                <?php foreach ($authors as $author): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="author-card">
                            <div class="avatar"
                                style="background-color: <?php echo getAvatarColor($author['full_name']); ?>">
                                <?php echo getInitials($author['full_name']); ?>
                            </div>
                            <h3 class="name"><?php echo safeOutput($author['full_name']); ?></h3>
                            <span class="role <?php echo $author['role']; ?>"><?php echo ucfirst($author['role']); ?></span>
                            <?php if (!empty($author['bio'])): ?>
                                <p class="bio"><?php echo safeOutput($author['bio']); ?></p>
                            <?php else: ?>
                                <p class="bio text-muted fst-italic">Belum ada bio</p>
                            <?php endif; ?>
                            <div class="stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo number_format($author['article_count']); ?></div>
                                    <div class="stat-label">Artikel</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo formatViews($author['total_views']); ?></div>
                                    <div class="stat-label">Views</div>
                                </div>
                            </div>
                            <a href="author.php?id=<?php echo $author['id']; ?>"
                                class="btn btn-outline-primary btn-sm mt-3">
                                Lihat Profil <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>