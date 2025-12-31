<?php
/**
 * =====================================================
 * ADMIN/SETTINGS.PHP - Site Settings
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: Information Disclosure, No CSRF
 */

require_once '../config.php';
require_once '../includes/functions.php';

// VULNERABILITY: Broken Access Control
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
}

$message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABILITY: No CSRF token
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $settingKey);
            $stmt->execute();
            $stmt->close();
        }
    }
    $message = 'Settings berhasil disimpan!';

    // VULNERABILITY: Log sensitive info
    logActivity($conn, 'settings_update', "Settings updated by user ID: " . getCurrentUserId());
}

// Get all settings
$settings = [];
$result = $conn->query("SELECT * FROM settings ORDER BY setting_key");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin <?php echo SITE_NAME; ?></title>
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

        .settings-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .settings-card .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }

        .settings-card .card-body {
            padding: 1.5rem;
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
            <a href="settings.php" class="nav-link active"><i class="bi bi-gear"></i> Settings</a>
            <div class="nav-section">Lainnya</div>
            <a href="../index.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Ke Website</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-power"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <header class="admin-header">
            <h4 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h4>
        </header>

        <div class="admin-content">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- VULNERABILITY: Form tanpa CSRF -->
            <form method="POST">
                <!-- Site Settings -->
                <div class="settings-card">
                    <div class="card-header"><i class="bi bi-globe me-2"></i>Pengaturan Website</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Website</label>
                                <input type="text" class="form-control" name="setting_site_name"
                                    value="<?php echo safeOutput($settings['site_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tagline</label>
                                <input type="text" class="form-control" name="setting_site_tagline"
                                    value="<?php echo safeOutput($settings['site_tagline'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="setting_site_email"
                                    value="<?php echo safeOutput($settings['site_email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telepon</label>
                                <input type="text" class="form-control" name="setting_site_phone"
                                    value="<?php echo safeOutput($settings['site_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="setting_site_address"
                                    rows="2"><?php echo safeOutput($settings['site_address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media -->
                <div class="settings-card">
                    <div class="card-header"><i class="bi bi-share me-2"></i>Social Media</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-facebook text-primary"></i> Facebook</label>
                                <input type="url" class="form-control" name="setting_social_facebook"
                                    value="<?php echo safeOutput($settings['social_facebook'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-twitter-x"></i> Twitter</label>
                                <input type="url" class="form-control" name="setting_social_twitter"
                                    value="<?php echo safeOutput($settings['social_twitter'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-instagram text-danger"></i> Instagram</label>
                                <input type="url" class="form-control" name="setting_social_instagram"
                                    value="<?php echo safeOutput($settings['social_instagram'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-youtube text-danger"></i> YouTube</label>
                                <input type="url" class="form-control" name="setting_social_youtube"
                                    value="<?php echo safeOutput($settings['social_youtube'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="settings-card">
                    <div class="card-header"><i class="bi bi-sliders me-2"></i>Pengaturan Lanjutan</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Debug Mode</label>
                                <select class="form-select" name="setting_debug_mode">
                                    <option value="0" <?php echo ($settings['debug_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                    <option value="1" <?php echo ($settings['debug_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Show SQL Errors</label>
                                <select class="form-select" name="setting_show_sql_errors">
                                    <option value="0" <?php echo ($settings['show_sql_errors'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                    <option value="1" <?php echo ($settings['show_sql_errors'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg me-1"></i> Simpan Settings
                </button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>