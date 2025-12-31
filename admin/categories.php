<?php
/**
 * =====================================================
 * ADMIN/CATEGORIES.PHP - Categories Management
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: 
 * - SQL Injection in delete action
 * - Broken Access Control
 * - No CSRF protection
 * - XSS in category name display
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
$editCategory = null;

// Handle delete action
// VULNERABILITY: SQL Injection - ID tidak di-sanitize dengan benar
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete']; // VULNERABLE: Langsung dipakai tanpa validasi
    $conn->query("DELETE FROM categories WHERE id = $deleteId");
    header('Location: categories.php?deleted=1');
    exit;
}

// Handle edit - get category data
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $result = $conn->query("SELECT * FROM categories WHERE id = $editId");
    $editCategory = $result->fetch_assoc();
}

// Handle form submission (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABILITY: No CSRF token validation
    
    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $type = $_POST['type'] ?? 'berita';
    $description = $_POST['description'] ?? '';
    $icon = $_POST['icon'] ?? 'bi-folder';
    $color = $_POST['color'] ?? '#1e3a5f';
    
    // VULNERABILITY: Weak input validation
    if (empty($name)) {
        $message = 'Nama kategori wajib diisi!';
        $messageType = 'danger';
    } else {
        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(str_replace(' ', '-', $name));
        }
        
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            // Update existing
            $categoryId = (int) $_POST['category_id'];
            // VULNERABILITY: SQL Injection - values tidak di-escape dengan benar
            $query = "UPDATE categories SET 
                      name = '$name', 
                      slug = '$slug', 
                      type = '$type', 
                      description = '$description',
                      icon = '$icon',
                      color = '$color'
                      WHERE id = $categoryId";
            $conn->query($query);
            $message = 'Kategori berhasil diupdate!';
            $messageType = 'success';
        } else {
            // Insert new
            // VULNERABILITY: SQL Injection
            $query = "INSERT INTO categories (name, slug, type, description, icon, color) 
                      VALUES ('$name', '$slug', '$type', '$description', '$icon', '$color')";
            $conn->query($query);
            $message = 'Kategori berhasil ditambahkan!';
            $messageType = 'success';
        }
    }
}

// Get all categories with article count
$categories = [];
$query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM articles WHERE category_id = c.id) as article_count
          FROM categories c
          ORDER BY c.type, c.sort_order, c.name";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Separate by type
$beritaCategories = array_filter($categories, fn($c) => $c['type'] === 'berita');
$wisataCategories = array_filter($categories, fn($c) => $c['type'] === 'wisata');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin <?php echo SITE_NAME; ?></title>
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

        .category-card {
            background: #fff;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s;
        }

        .category-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .category-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
        }

        .category-info {
            flex: 1;
        }

        .category-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .category-meta {
            font-size: 0.75rem;
            color: #64748b;
        }

        .category-actions {
            display: flex;
            gap: 0.5rem;
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
            <a href="categories.php" class="nav-link active"><i class="bi bi-folder"></i> Kategori</a>
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
            <h4 class="mb-0"><i class="bi bi-folder me-2"></i>Kelola Kategori</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
            </button>
        </header>

        <div class="admin-content">
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Kategori berhasil dihapus!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Berita Categories -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-newspaper me-2"></i>Kategori Berita
                            <span class="badge bg-light text-primary float-end"><?php echo count($beritaCategories); ?></span>
                        </div>
                        <div class="card-body">
                            <?php foreach ($beritaCategories as $cat): ?>
                                <div class="category-card">
                                    <div class="category-icon" style="background-color: <?php echo $cat['color']; ?>">
                                        <i class="<?php echo $cat['icon']; ?>"></i>
                                    </div>
                                    <div class="category-info">
                                        <!-- VULNERABILITY: XSS - name tidak di-escape -->
                                        <div class="category-name"><?php echo $cat['name']; ?></div>
                                        <div class="category-meta">
                                            <i class="bi bi-file-text"></i> <?php echo $cat['article_count']; ?> artikel
                                            • <?php echo $cat['slug']; ?>
                                        </div>
                                    </div>
                                    <div class="category-actions">
                                        <a href="categories.php?edit=<?php echo $cat['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#categoryModal"
                                           onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="categories.php?delete=<?php echo $cat['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Hapus kategori ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($beritaCategories)): ?>
                                <p class="text-muted text-center py-3">Belum ada kategori berita</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Wisata Categories -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <i class="bi bi-geo-alt me-2"></i>Kategori Wisata
                            <span class="badge bg-light text-success float-end"><?php echo count($wisataCategories); ?></span>
                        </div>
                        <div class="card-body">
                            <?php foreach ($wisataCategories as $cat): ?>
                                <div class="category-card">
                                    <div class="category-icon" style="background-color: <?php echo $cat['color']; ?>">
                                        <i class="<?php echo $cat['icon']; ?>"></i>
                                    </div>
                                    <div class="category-info">
                                        <!-- VULNERABILITY: XSS - name tidak di-escape -->
                                        <div class="category-name"><?php echo $cat['name']; ?></div>
                                        <div class="category-meta">
                                            <i class="bi bi-file-text"></i> <?php echo $cat['article_count']; ?> artikel
                                            • <?php echo $cat['slug']; ?>
                                        </div>
                                    </div>
                                    <div class="category-actions">
                                        <a href="categories.php?edit=<?php echo $cat['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>); return false;"
                                           data-bs-toggle="modal" 
                                           data-bs-target="#categoryModal">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="categories.php?delete=<?php echo $cat['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Hapus kategori ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($wisataCategories)): ?>
                                <p class="text-muted text-center py-3">Belum ada kategori wisata</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="categoryForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Kategori</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="categoryId">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" name="name" id="categoryName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" name="slug" id="categorySlug" 
                                   placeholder="Kosongkan untuk generate otomatis">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipe</label>
                            <select class="form-select" name="type" id="categoryType">
                                <option value="berita">Berita</option>
                                <option value="wisata">Wisata</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" id="categoryDesc" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Icon (Bootstrap Icons)</label>
                                <input type="text" class="form-control" name="icon" id="categoryIcon" 
                                       value="bi-folder" placeholder="bi-folder">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Warna</label>
                                <input type="color" class="form-control form-control-color w-100" 
                                       name="color" id="categoryColor" value="#1e3a5f">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(cat) {
            document.getElementById('modalTitle').textContent = 'Edit Kategori';
            document.getElementById('categoryId').value = cat.id;
            document.getElementById('categoryName').value = cat.name;
            document.getElementById('categorySlug').value = cat.slug;
            document.getElementById('categoryType').value = cat.type;
            document.getElementById('categoryDesc').value = cat.description || '';
            document.getElementById('categoryIcon').value = cat.icon;
            document.getElementById('categoryColor').value = cat.color;
        }

        // Reset form when modal closes
        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('modalTitle').textContent = 'Tambah Kategori';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
        });

        // Auto-generate slug
        document.getElementById('categoryName').addEventListener('input', function() {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-');
            if (!document.getElementById('categorySlug').value) {
                document.getElementById('categorySlug').placeholder = slug;
            }
        });
    </script>
</body>

</html>
