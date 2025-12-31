<?php
/**
 * =====================================================
 * ADMIN/SUBSCRIBERS.PHP - Newsletter Subscribers Management
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: 
 * - Stored XSS in subscriber name display
 * - SQL Injection in search/filter
 * - Broken Access Control
 * - No CSRF protection
 * - Email Header Injection potential
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

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // VULNERABILITY: No CSRF token
    $deleteId = (int) $_GET['delete'];
    $conn->query("DELETE FROM subscribers WHERE id = $deleteId");
    $message = 'Subscriber berhasil dihapus!';
    $messageType = 'success';
}

// Handle status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $toggleId = (int) $_GET['toggle'];
    $conn->query("UPDATE subscribers SET status = IF(status = 'active', 'unsubscribed', 'active') WHERE id = $toggleId");
    header('Location: subscribers.php?toggled=1');
    exit;
}

// Handle bulk email send (VULNERABLE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_newsletter'])) {
    // VULNERABILITY: No CSRF, potential Email Header Injection
    $subject = $_POST['subject'] ?? '';
    $emailBody = $_POST['body'] ?? '';

    // In real scenario, this would send emails
    // VULNERABILITY: Subject and body not sanitized - Email Header Injection possible
    $message = "Newsletter akan dikirim ke semua subscriber aktif dengan subject: $subject";
    $messageType = 'success';

    // Log the action with sensitive info
    logActivity($conn, 'newsletter_send', "Newsletter sent with subject: $subject to all active subscribers");
}

// Handle add subscriber
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subscriber'])) {
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? ''; // VULNERABILITY: Not sanitized - Stored XSS

    if (!empty($email)) {
        // VULNERABILITY: SQL Injection
        $query = "INSERT INTO subscribers (email, name, status) VALUES ('$email', '$name', 'active')";
        if ($conn->query($query)) {
            $message = 'Subscriber berhasil ditambahkan!';
            $messageType = 'success';
        } else {
            $message = 'Gagal menambahkan subscriber: ' . $conn->error;
            $messageType = 'danger';
        }
    }
}

// Filters - VULNERABLE to SQL Injection
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$where = "1=1";
if ($search) {
    // VULNERABILITY: SQL Injection
    $where .= " AND (email LIKE '%$search%' OR name LIKE '%$search%')";
}
if ($statusFilter) {
    $where .= " AND status = '$statusFilter'";
}

// Get subscribers
$subscribers = [];
$query = "SELECT * FROM subscribers WHERE $where ORDER BY subscribed_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $subscribers[] = $row;
}

// Stats
$statsResult = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
FROM subscribers");
$stats = $statsResult->fetch_assoc();

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers_export.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Email', 'Name', 'Status', 'Subscribed At']);

    foreach ($subscribers as $sub) {
        fputcsv($output, [$sub['email'], $sub['name'], $sub['status'], $sub['subscribed_at']]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Subscribers - Admin <?php echo SITE_NAME; ?></title>
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

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-card .label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
        }

        .subscriber-row {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .subscriber-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
            font-size: 0.875rem;
        }

        .subscriber-info {
            flex: 1;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .status-badge.active {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.unsubscribed {
            background: #fee2e2;
            color: #dc2626;
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
            <a href="users.php" class="nav-link"><i class="bi bi-people"></i> Users</a>
            <a href="subscribers.php" class="nav-link active"><i class="bi bi-envelope"></i> Subscribers</a>
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
            <div>
                <h4 class="mb-0"><i class="bi bi-envelope me-2"></i>Newsletter Subscribers</h4>
                <small class="text-muted">Kelola subscriber newsletter</small>
            </div>
            <div class="d-flex gap-2">
                <a href="subscribers.php?export=csv" class="btn btn-outline-success">
                    <i class="bi bi-download me-1"></i> Export
                </a>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newsletterModal">
                    <i class="bi bi-send me-1"></i> Kirim Newsletter
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-lg me-1"></i> Tambah
                </button>
            </div>
        </header>

        <div class="admin-content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['toggled'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Status subscriber berhasil diubah!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="value text-primary"><?php echo $stats['total']; ?></div>
                        <div class="label">Total Subscribers</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="value text-success"><?php echo $stats['active']; ?></div>
                        <div class="label">Active</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="value text-danger"><?php echo $stats['unsubscribed']; ?></div>
                        <div class="label">Unsubscribed</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Cari email atau nama..."
                                value="<?php echo safeOutput($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active
                                </option>
                                <option value="unsubscribed" <?php echo $statusFilter === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscribers List -->
            <?php if (empty($subscribers)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-envelope-open display-1 text-muted"></i>
                    <h5 class="mt-3">Belum Ada Subscriber</h5>
                    <p class="text-muted">Subscriber newsletter akan muncul di sini</p>
                </div>
            <?php else: ?>
                <?php foreach ($subscribers as $sub): ?>
                    <div class="subscriber-row">
                        <div class="subscriber-avatar" style="background-color: <?php echo getAvatarColor($sub['email']); ?>">
                            <?php echo strtoupper(substr($sub['email'], 0, 2)); ?>
                        </div>
                        <div class="subscriber-info">
                            <!-- VULNERABILITY: Stored XSS - name displayed without sanitization -->
                            <div class="fw-semibold"><?php echo $sub['name'] ?: 'Anonymous'; ?></div>
                            <div class="text-muted small"><?php echo safeOutput($sub['email']); ?></div>
                        </div>
                        <div>
                            <span class="status-badge <?php echo $sub['status']; ?>">
                                <?php echo ucfirst($sub['status']); ?>
                            </span>
                        </div>
                        <div class="text-muted small">
                            <?php echo timeAgo($sub['subscribed_at']); ?>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="subscribers.php?toggle=<?php echo $sub['id']; ?>"
                                class="btn btn-sm btn-outline-<?php echo $sub['status'] === 'active' ? 'warning' : 'success'; ?>"
                                title="<?php echo $sub['status'] === 'active' ? 'Unsubscribe' : 'Activate'; ?>">
                                <i class="bi bi-<?php echo $sub['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                            </a>
                            <a href="subscribers.php?delete=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Hapus subscriber ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Subscriber Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="add_subscriber" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Subscriber</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama (Opsional)</label>
                            <!-- VULNERABILITY: Input tidak di-sanitize, bisa XSS -->
                            <input type="text" class="form-control" name="name" placeholder="Nama subscriber">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Send Newsletter Modal -->
    <div class="modal fade" id="newsletterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="send_newsletter" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-send me-2"></i>Kirim Newsletter</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Newsletter akan dikirim ke <strong><?php echo $stats['active']; ?></strong> subscriber aktif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <!-- VULNERABILITY: Potential Email Header Injection -->
                            <input type="text" class="form-control" name="subject" required
                                placeholder="Subject email newsletter">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Isi Newsletter</label>
                            <textarea class="form-control" name="body" rows="8" required
                                placeholder="Tulis isi newsletter di sini..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Kirim Newsletter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>