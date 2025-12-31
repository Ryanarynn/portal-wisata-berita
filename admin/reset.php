<?php
/**
 * =====================================================
 * ADMIN/RESET.PHP - Lab Reset Control Panel
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once '../config.php';
require_once '../includes/functions.php';

// Check login
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle manual reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reset') {
        // ===== DIRECT RESET (tanpa HTTP request) =====
        // Ini berfungsi di shared hosting seperti InfinityFree

        $errors = [];
        $success_count = 0;

        // PENTING: Password disimpan dalam plain text agar sesuai dengan
        // login.php yang menggunakan SQL Injection vulnerable query
        // (tidak menggunakan password_verify)

        // 1. Reset Users (PALING PENTING untuk login)
        try {
            $conn->query("DELETE FROM users");
            @$conn->query("ALTER TABLE users AUTO_INCREMENT = 1");
            $result = $conn->query("INSERT INTO users (username, password, email, full_name, role, bio, created_at) VALUES 
                ('admin', 'admin123', 'admin@portal.com', 'Administrator', 'admin', 'Administrator sistem', NOW()),
                ('editor', 'editor123', 'editor@portal.com', 'Sarah Editor', 'editor', 'Editor konten', NOW()),
                ('contributor', 'contrib123', 'contributor@portal.com', 'Budi Contributor', 'contributor', 'Kontributor artikel', NOW())
            ");
            if ($result) $success_count++;
            else $errors[] = "Users: " . $conn->error;
        } catch (Exception $e) {
            $errors[] = "Users: " . $e->getMessage();
        }

        // 2. Reset Comments (hapus XSS) - coba dengan dan tanpa kolom status
        try {
            $conn->query("DELETE FROM comments");
            @$conn->query("ALTER TABLE comments AUTO_INCREMENT = 1");
            // Coba dengan status
            $result = @$conn->query("INSERT INTO comments (article_id, name, email, comment, status, created_at) VALUES 
                (1, 'Budi Santoso', 'budi@email.com', 'Artikel yang sangat informatif!', 'approved', NOW())
            ");
            if (!$result) {
                // Coba tanpa status
                $result = $conn->query("INSERT INTO comments (article_id, name, email, comment, created_at) VALUES 
                    (1, 'Budi Santoso', 'budi@email.com', 'Artikel yang sangat informatif!', NOW())
                ");
            }
            if ($result) $success_count++;
        } catch (Exception $e) {
            $errors[] = "Comments: " . $e->getMessage();
        }

        // 3. Reset Subscribers - skip jika tabel tidak ada atau berbeda
        try {
            @$conn->query("DELETE FROM subscribers");
            @$conn->query("ALTER TABLE subscribers AUTO_INCREMENT = 1");
            @$conn->query("INSERT INTO subscribers (email, name, status) VALUES 
                ('subscriber1@email.com', 'John Doe', 'active'),
                ('subscriber2@email.com', 'Jane Smith', 'active')
            ");
            $success_count++;
        } catch (Exception $e) {
            // Skip jika error
        }

        // 4. Reset Activity Log - skip jika tabel tidak ada
        try {
            @$conn->query("DELETE FROM activity_log");
            @$conn->query("ALTER TABLE activity_log AUTO_INCREMENT = 1");
            @$conn->query("INSERT INTO activity_log (user_id, action, details, ip_address, created_at) VALUES 
                (1, 'system_reset', 'Lab berhasil di-reset', '" . $_SERVER['REMOTE_ADDR'] . "', NOW())
            ");
            $success_count++;
        } catch (Exception $e) {
            // Skip jika error
        }

        // 5. Bersihkan XSS dari Articles
        try {
            $result = @$conn->query("SELECT id, content FROM articles");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $cleanContent = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $row['content']);
                    $cleanContent = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $cleanContent);
                    if ($cleanContent !== $row['content']) {
                        $stmt = $conn->prepare("UPDATE articles SET content = ? WHERE id = ?");
                        $stmt->bind_param("si", $cleanContent, $row['id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                $success_count++;
            }
        } catch (Exception $e) {
            // Skip jika error
        }

        // 6. Hapus file PHP berbahaya dari uploads
        $uploadDir = '../uploads/';
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar'])) {
                    @unlink($uploadDir . $file);
                }
            }
            $success_count++;
        }

        // 7. Log reset
        @file_put_contents('../reset_log.txt', date('Y-m-d H:i:s') . " | IP: " . $_SERVER['REMOTE_ADDR'] . " | SUCCESS | Manual Reset\n", FILE_APPEND);

        // Destroy all sessions (logout semua user)
        @session_destroy();
        @session_start();

        if ($success_count > 0) {
            $message = "Lab berhasil di-reset! ($success_count operasi). Login dengan admin/admin123";
            if (!empty($errors)) {
                $message .= " (Warning: " . implode(", ", $errors) . ")";
            }
            $messageType = 'success';
        } else {
            $message = 'Reset gagal: ' . implode(", ", $errors);
            $messageType = 'danger';
        }
    }
}

// Get last reset time from log
$lastReset = 'Belum pernah';
if (file_exists('../reset_log.txt')) {
    $logContent = file_get_contents('../reset_log.txt');
    $lines = array_filter(explode("\n", $logContent));
    if (!empty($lines)) {
        $lastLine = end($lines);
        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lastLine, $matches)) {
            $lastReset = $matches[1];
        }
    }
}

// Get current stats
$stats = [];
$stats['users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$stats['comments'] = $conn->query("SELECT COUNT(*) as count FROM comments")->fetch_assoc()['count'];
$stats['articles'] = $conn->query("SELECT COUNT(*) as count FROM articles")->fetch_assoc()['count'];
$stats['subscribers'] = $conn->query("SELECT COUNT(*) as count FROM subscribers")->fetch_assoc()['count'];
$stats['uploads'] = count(glob('../uploads/*'));
$stats['logs'] = $conn->query("SELECT COUNT(*) as count FROM activity_log")->fetch_assoc()['count'];

// Check for potential XSS in comments
$xssCheck = $conn->query("SELECT COUNT(*) as count FROM comments WHERE comment LIKE '%<script%' OR comment LIKE '%onerror%' OR comment LIKE '%onclick%'");
$potentialXss = $xssCheck->fetch_assoc()['count'];

// Check for dangerous uploads
$dangerousFiles = [];
if (is_dir('../uploads')) {
    $files = scandir('../uploads');
    foreach ($files as $file) {
        if ($file === '.' || $file === '..')
            continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar'])) {
            $dangerousFiles[] = $file;
        }
        if (preg_match('/\.(php|phtml)\./i', $file)) {
            $dangerousFiles[] = $file;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Reset - Admin <?php echo SITE_NAME; ?></title>
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

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
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

        .reset-card {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #fff;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
        }

        .warning-card {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 12px;
            padding: 1.25rem;
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
            <a href="subscribers.php" class="nav-link"><i class="bi bi-envelope"></i> Subscribers</a>
            <a href="settings.php" class="nav-link"><i class="bi bi-gear"></i> Settings</a>
            <a href="logs.php" class="nav-link"><i class="bi bi-journal-text"></i> Activity Log</a>
            <div class="nav-section">Lab Tools</div>
            <a href="reset.php" class="nav-link active"><i class="bi bi-arrow-counterclockwise"></i> Reset Lab</a>
            <div class="nav-section">Lainnya</div>
            <a href="../index.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Ke Website</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-power"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <h4 class="mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>Lab Reset Control Panel</h4>
        </header>

        <div class="admin-content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <i
                        class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'x-circle' : 'exclamation-triangle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <!-- Current Stats -->
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="value text-primary"><?php echo $stats['users']; ?></div>
                        <div class="label">Users</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="value text-info"><?php echo $stats['comments']; ?></div>
                        <div class="label">Comments</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="value text-success"><?php echo $stats['articles']; ?></div>
                        <div class="label">Artikel</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="value text-warning"><?php echo $stats['subscribers']; ?></div>
                        <div class="label">Subscribers</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="value text-secondary"><?php echo $stats['uploads']; ?></div>
                        <div class="label">Uploads</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="value text-dark"><?php echo $stats['logs']; ?></div>
                        <div class="label">Log Entries</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Reset Panel -->
                <div class="col-lg-6">
                    <div class="reset-card">
                        <i class="bi bi-arrow-counterclockwise display-3 mb-3"></i>
                        <h3 class="mb-3">Reset Lab ke Kondisi Awal</h3>
                        <p class="mb-4 opacity-75">
                            Ini akan membersihkan semua data yang telah di-inject/retas:<br>
                            • Reset users ke default<br>
                            • Hapus semua XSS dari comments<br>
                            • Bersihkan malware dari uploads<br>
                            • Reset settings dan activity log
                        </p>
                        <form method="POST"
                            onsubmit="return confirm('Yakin ingin mereset lab? Semua data yang di-inject akan dihapus!')">
                            <input type="hidden" name="action" value="reset">
                            <button type="submit" class="btn btn-lg btn-light">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Sekarang
                            </button>
                        </form>
                        <div class="mt-3 small opacity-75">
                            <i class="bi bi-clock me-1"></i>Reset terakhir: <?php echo $lastReset; ?>
                        </div>
                    </div>
                </div>

                <!-- Status & Warnings -->
                <div class="col-lg-6">
                    <!-- XSS Warning -->
                    <?php if ($potentialXss > 0): ?>
                        <div class="warning-card mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 2rem;"></i>
                                <div>
                                    <h6 class="mb-1">Potensi XSS Terdeteksi!</h6>
                                    <p class="mb-0 small text-muted">
                                        Ditemukan <strong><?php echo $potentialXss; ?></strong> komentar yang mungkin
                                        mengandung kode berbahaya.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Dangerous Uploads Warning -->
                    <?php if (!empty($dangerousFiles)): ?>
                        <div class="warning-card mb-3" style="background: #fee2e2; border-color: #fca5a5;">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-file-earmark-code-fill text-danger" style="font-size: 2rem;"></i>
                                <div>
                                    <h6 class="mb-1">File Berbahaya di Uploads!</h6>
                                    <p class="mb-0 small text-muted">
                                        Ditemukan <strong><?php echo count($dangerousFiles); ?></strong> file
                                        PHP/script:<br>
                                        <code><?php echo implode(', ', array_slice($dangerousFiles, 0, 3)); ?><?php echo count($dangerousFiles) > 3 ? '...' : ''; ?></code>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Cron Job Instructions -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-clock-history me-2"></i>Setup Auto-Reset (Cron Job)
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                Untuk reset otomatis, gunakan layanan cron gratis seperti <strong>cron-job.org</strong>:
                            </p>
                            <div class="bg-light rounded p-3 mb-3">
                                <code class="small d-block mb-2">
                                    URL untuk di-ping:
                                </code>
                                <code class="text-primary small">
                                    https://nama-domain.com/reset.php?key=X7kP2mN9qR4wT8yB5vL3
                                </code>
                            </div>
                            <p class="small text-muted mb-3">
                                <i class="bi bi-lightbulb me-1"></i>Setting yang disarankan:
                            </p>
                            <ul class="small text-muted mb-0">
                                <li>Setiap 1 jam untuk lab yang ramai</li>
                                <li>Setiap 6 jam untuk penggunaan normal</li>
                                <li>Setiap 24 jam untuk penggunaan minimal</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Direct Reset Link -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header">
                    <i class="bi bi-link-45deg me-2"></i>Direct Reset Link
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Gunakan link ini untuk reset manual via URL:</p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="resetUrl"
                            value="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../reset.php?key=X7kP2mN9qR4wT8yB5vL3'; ?>"
                            readonly>
                        <button class="btn btn-outline-primary" onclick="copyResetUrl()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <small class="text-danger">
                        <i class="bi bi-shield-exclamation me-1"></i>
                        Jangan share link ini! Ganti key di reset.php untuk keamanan.
                    </small>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyResetUrl() {
            const input = document.getElementById('resetUrl');
            input.select();
            document.execCommand('copy');
            alert('URL berhasil di-copy!');
        }
    </script>
</body>

</html>