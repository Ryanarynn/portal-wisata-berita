<?php
/**
 * =====================================================
 * ADMIN/ARTICLE-FORM.PHP - Add/Edit Article Form
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: 
 * - IDOR (Insecure Direct Object Reference) - Can edit any article
 * - SQL Injection in update/insert
 * - Stored XSS in content
 * - No CSRF protection
 * - Broken Access Control
 */

require_once '../config.php';
require_once '../includes/functions.php';

// VULNERABILITY: Broken Access Control - no role check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    // MISSING: exit;
}

$message = '';
$messageType = '';
$article = null;
$isEdit = false;

// Get article for editing - VULNERABILITY: IDOR - tidak cek apakah user punya hak
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $articleId = (int) $_GET['id'];
    // VULNERABILITY: Tidak mengecek apakah user adalah pemilik artikel
    $result = $conn->query("SELECT * FROM articles WHERE id = $articleId");
    $article = $result->fetch_assoc();
    $isEdit = ($article !== null);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABILITY: No CSRF token validation
    
    $title = $_POST['title'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $content = $_POST['content'] ?? ''; // VULNERABILITY: Stored XSS - tidak di-sanitize
    $excerpt = $_POST['excerpt'] ?? '';
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $type = $_POST['type'] ?? 'berita';
    $status = $_POST['status'] ?? 'draft';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isBreaking = isset($_POST['is_breaking']) ? 1 : 0;
    $image = $_POST['image'] ?? '';
    
    // Generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    }
    
    // Generate excerpt if empty
    if (empty($excerpt)) {
        $excerpt = substr(strip_tags($content), 0, 200) . '...';
    }
    
    $authorId = getCurrentUserId();
    
    if (empty($title) || empty($content)) {
        $message = 'Judul dan konten wajib diisi!';
        $messageType = 'danger';
    } else {
        if (isset($_POST['article_id']) && !empty($_POST['article_id'])) {
            // Update existing - VULNERABILITY: SQL Injection
            $articleId = (int) $_POST['article_id'];
            
            // VULNERABILITY: IDOR - tidak cek ownership
            // VULNERABILITY: SQL Injection - content tidak di-escape
            $query = "UPDATE articles SET 
                      title = '" . $conn->real_escape_string($title) . "',
                      slug = '" . $conn->real_escape_string($slug) . "',
                      content = '$content',
                      excerpt = '" . $conn->real_escape_string($excerpt) . "',
                      category_id = $categoryId,
                      type = '$type',
                      status = '$status',
                      is_featured = $isFeatured,
                      is_breaking = $isBreaking,
                      image = '" . $conn->real_escape_string($image) . "',
                      published_at = " . ($status === 'published' ? 'NOW()' : 'NULL') . ",
                      updated_at = NOW()
                      WHERE id = $articleId";
            
            if ($conn->query($query)) {
                $message = 'Artikel berhasil diupdate!';
                $messageType = 'success';
                
                // Log activity
                logActivity($conn, 'article_update', "Updated article ID: $articleId, title: $title");
                
                // Reload article data
                $result = $conn->query("SELECT * FROM articles WHERE id = $articleId");
                $article = $result->fetch_assoc();
            } else {
                $message = 'Gagal mengupdate artikel: ' . $conn->error;
                $messageType = 'danger';
            }
        } else {
            // Insert new - VULNERABILITY: SQL Injection in content
            $query = "INSERT INTO articles (title, slug, content, excerpt, category_id, author_id, type, status, is_featured, is_breaking, image, published_at, created_at) 
                      VALUES (
                          '" . $conn->real_escape_string($title) . "',
                          '" . $conn->real_escape_string($slug) . "',
                          '$content',
                          '" . $conn->real_escape_string($excerpt) . "',
                          $categoryId,
                          $authorId,
                          '$type',
                          '$status',
                          $isFeatured,
                          $isBreaking,
                          '" . $conn->real_escape_string($image) . "',
                          " . ($status === 'published' ? 'NOW()' : 'NULL') . ",
                          NOW()
                      )";
            
            if ($conn->query($query)) {
                $newId = $conn->insert_id;
                $message = 'Artikel berhasil ditambahkan!';
                $messageType = 'success';
                
                logActivity($conn, 'article_create', "Created new article ID: $newId, title: $title");
                
                // Redirect to edit page
                header("Location: article-form.php?id=$newId&created=1");
                exit;
            } else {
                $message = 'Gagal menambahkan artikel: ' . $conn->error;
                $messageType = 'danger';
            }
        }
    }
}

// Get categories for dropdown
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY type, name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get tags
$tags = [];
$result = $conn->query("SELECT * FROM tags ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $tags[] = $row;
}

// Get selected tags for article
$selectedTags = [];
if ($article) {
    $tagResult = $conn->query("SELECT tag_id FROM article_tags WHERE article_id = " . $article['id']);
    while ($row = $tagResult->fetch_assoc()) {
        $selectedTags[] = $row['tag_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Tambah'; ?> Artikel - Admin <?php echo SITE_NAME; ?></title>
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
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-content {
            padding: 1.5rem;
        }

        .editor-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .editor-card .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }

        .editor-card .card-body {
            padding: 1.5rem;
        }

        .content-editor {
            min-height: 400px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }

        .tag-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: #e2e8f0;
            border-radius: 50px;
            font-size: 0.75rem;
            margin: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tag-chip:hover, .tag-chip.selected {
            background: var(--primary);
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
            <a href="articles.php" class="nav-link active"><i class="bi bi-file-text"></i> Artikel</a>
            <a href="categories.php" class="nav-link"><i class="bi bi-folder"></i> Kategori</a>
            <a href="comments.php" class="nav-link"><i class="bi bi-chat-dots"></i> Komentar</a>
            <a href="upload.php" class="nav-link"><i class="bi bi-cloud-upload"></i> Upload File</a>
            <div class="nav-section">Sistem</div>
            <a href="users.php" class="nav-link"><i class="bi bi-people"></i> Users</a>
            <a href="subscribers.php" class="nav-link"><i class="bi bi-envelope"></i> Subscribers</a>
            <a href="settings.php" class="nav-link"><i class="bi bi-gear"></i> Settings</a>
            <a href="logs.php" class="nav-link"><i class="bi bi-journal-text"></i> Activity Log</a>
            <div class="nav-section">Lainnya</div>
            <a href="../index.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Ke Website</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-power"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="d-flex align-items-center gap-3">
                <a href="articles.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h5 class="mb-0"><?php echo $isEdit ? 'Edit Artikel' : 'Artikel Baru'; ?></h5>
                    <?php if ($isEdit && $article): ?>
                        <small class="text-muted">ID: <?php echo $article['id']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if ($isEdit && $article): ?>
                    <a href="../view.php?id=<?php echo $article['id']; ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> Preview
                    </a>
                <?php endif; ?>
                <button type="submit" form="articleForm" name="status" value="draft" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark me-1"></i> Simpan Draft
                </button>
                <button type="submit" form="articleForm" name="status" value="published" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Publish
                </button>
            </div>
        </header>

        <div class="admin-content">
            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>Artikel berhasil dibuat!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- VULNERABILITY: Form tanpa CSRF token -->
            <form method="POST" id="articleForm">
                <input type="hidden" name="article_id" value="<?php echo $article['id'] ?? ''; ?>">
                
                <div class="row g-4">
                    <!-- Main Content Area -->
                    <div class="col-lg-8">
                        <div class="editor-card mb-4">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Judul Artikel</label>
                                    <input type="text" class="form-control form-control-lg" name="title" 
                                           value="<?php echo safeOutput($article['title'] ?? ''); ?>" 
                                           placeholder="Masukkan judul artikel..." required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Slug URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text">/view.php?slug=</span>
                                        <input type="text" class="form-control" name="slug" 
                                               value="<?php echo safeOutput($article['slug'] ?? ''); ?>"
                                               placeholder="url-artikel-disini">
                                    </div>
                                    <small class="text-muted">Kosongkan untuk generate otomatis dari judul</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Konten Artikel</label>
                                    <!-- VULNERABILITY: Content tidak di-sanitize - Stored XSS possible -->
                                    <textarea class="form-control content-editor" name="content" rows="15" 
                                              placeholder="Tulis konten artikel di sini... (HTML diperbolehkan)"><?php echo $article['content'] ?? ''; ?></textarea>
                                    <small class="text-muted">Anda dapat menggunakan tag HTML untuk formatting</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Excerpt / Ringkasan</label>
                                    <textarea class="form-control" name="excerpt" rows="3" 
                                              placeholder="Ringkasan singkat artikel..."><?php echo safeOutput($article['excerpt'] ?? ''); ?></textarea>
                                    <small class="text-muted">Kosongkan untuk generate otomatis dari konten</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar Settings -->
                    <div class="col-lg-4">
                        <!-- Publish Settings -->
                        <div class="editor-card mb-4">
                            <div class="card-header">
                                <i class="bi bi-gear me-2"></i>Pengaturan
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="draft" <?php echo ($article['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="pending" <?php echo ($article['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                                        <option value="published" <?php echo ($article['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Tipe</label>
                                    <select class="form-select" name="type" id="typeSelect">
                                        <option value="berita" <?php echo ($article['type'] ?? 'berita') === 'berita' ? 'selected' : ''; ?>>Berita</option>
                                        <option value="wisata" <?php echo ($article['type'] ?? '') === 'wisata' ? 'selected' : ''; ?>>Wisata</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="category_id" id="categorySelect">
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                    data-type="<?php echo $cat['type']; ?>"
                                                    <?php echo ($article['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo safeOutput($cat['name']); ?> (<?php echo ucfirst($cat['type']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <hr>
                                
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" name="is_featured" id="isFeatured"
                                           <?php echo ($article['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="isFeatured">
                                        <i class="bi bi-star text-warning"></i> Artikel Unggulan
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_breaking" id="isBreaking"
                                           <?php echo ($article['is_breaking'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="isBreaking">
                                        <i class="bi bi-lightning text-danger"></i> Breaking News
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Featured Image -->
                        <div class="editor-card mb-4">
                            <div class="card-header">
                                <i class="bi bi-image me-2"></i>Gambar Utama
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <input type="text" class="form-control" name="image" 
                                           value="<?php echo safeOutput($article['image'] ?? ''); ?>"
                                           placeholder="nama-file-gambar.jpg">
                                    <small class="text-muted">Nama file gambar di folder uploads/</small>
                                </div>
                                
                                <?php if ($article && !empty($article['image'])): ?>
                                    <div class="border rounded p-2 text-center">
                                        <img src="../uploads/<?php echo safeOutput($article['image']); ?>" 
                                             alt="Preview" class="img-fluid rounded" style="max-height: 150px;"
                                             onerror="this.src='https://via.placeholder.com/300x150?text=No+Image'">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Article Info -->
                        <?php if ($isEdit && $article): ?>
                        <div class="editor-card">
                            <div class="card-header">
                                <i class="bi bi-info-circle me-2"></i>Informasi
                            </div>
                            <div class="card-body">
                                <div class="small text-muted">
                                    <p class="mb-2">
                                        <strong>ID:</strong> <?php echo $article['id']; ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Views:</strong> <?php echo number_format($article['views']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Dibuat:</strong><br>
                                        <?php echo formatTanggal($article['created_at']); ?>
                                    </p>
                                    <?php if ($article['updated_at']): ?>
                                    <p class="mb-0">
                                        <strong>Diupdate:</strong><br>
                                        <?php echo formatTanggal($article['updated_at']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-generate slug from title
        document.querySelector('input[name="title"]').addEventListener('input', function() {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            
            const slugInput = document.querySelector('input[name="slug"]');
            if (!slugInput.dataset.manual) {
                slugInput.value = slug;
            }
        });

        // Mark slug as manually edited
        document.querySelector('input[name="slug"]').addEventListener('input', function() {
            this.dataset.manual = true;
        });

        // Filter categories by type
        document.getElementById('typeSelect').addEventListener('change', function() {
            const type = this.value;
            const categorySelect = document.getElementById('categorySelect');
            const options = categorySelect.querySelectorAll('option[data-type]');
            
            options.forEach(option => {
                if (option.dataset.type === type || !type) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset selection if current is hidden
            const selected = categorySelect.options[categorySelect.selectedIndex];
            if (selected.style.display === 'none') {
                categorySelect.value = '';
            }
        });

        // Trigger filter on load
        document.getElementById('typeSelect').dispatchEvent(new Event('change'));
    </script>
</body>

</html>
