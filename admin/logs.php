<?php
/**
 * =====================================================
 * ADMIN/LOGS.PHP - Activity Log Viewer
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: 
 * - Information Disclosure (CWE-200)
 * - Shows sensitive data like passwords, API keys
 * - Broken Access Control
 * - No pagination limit exploitation
 */

require_once '../config.php';
require_once '../includes/functions.php';

// VULNERABILITY: Broken Access Control - no role check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    // MISSING: exit;
}

// VULNERABILITY: SQL Injection in filter
$userFilter = isset($_GET['user']) ? $_GET['user'] : '';
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause - VULNERABLE to SQL Injection
$where = "1=1";
if ($userFilter) {
    $where .= " AND al.user_id = '$userFilter'"; // VULNERABLE
}
if ($actionFilter) {
    $where .= " AND al.action LIKE '%$actionFilter%'"; // VULNERABLE
}
if ($dateFrom) {
    $where .= " AND DATE(al.created_at) >= '$dateFrom'"; // VULNERABLE
}
if ($dateTo) {
    $where .= " AND DATE(al.created_at) <= '$dateTo'"; // VULNERABLE
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = isset($_GET['limit']) ? (int) $_GET['limit'] : 50; // VULNERABLE: No max limit
$offset = ($page - 1) * $perPage;

// Get total count
$countResult = $conn->query("SELECT COUNT(*) as total FROM activity_log al WHERE $where");
$totalLogs = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalLogs / $perPage);

// Get activity logs - VULNERABILITY: Shows sensitive details
$logs = [];
$query = "SELECT al.*, u.username, u.full_name, u.role
          FROM activity_log al
          LEFT JOIN users u ON al.user_id = u.id
          WHERE $where
          ORDER BY al.created_at DESC
          LIMIT $offset, $perPage";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

// Get unique actions for filter
$actions = [];
$actionsResult = $conn->query("SELECT DISTINCT action FROM activity_log ORDER BY action");
while ($row = $actionsResult->fetch_assoc()) {
    $actions[] = $row['action'];
}

// Get users for filter
$users = [];
$usersResult = $conn->query("SELECT id, username, full_name FROM users ORDER BY username");
while ($row = $usersResult->fetch_assoc()) {
    $users[] = $row;
}

// VULNERABILITY: Export functionality with sensitive data
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_log_export.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'User', 'Action', 'Details', 'IP Address', 'Timestamp']);
    
    // Export ALL logs without limit - Information Disclosure
    $exportResult = $conn->query("SELECT al.*, u.username FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC");
    while ($row = $exportResult->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['username'],
            $row['action'],
            $row['details'], // VULNERABLE: Exposes sensitive details
            $row['ip_address'],
            $row['created_at']
        ]);
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
    <title>Activity Log - Admin <?php echo SITE_NAME; ?></title>
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

        .log-item {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 1rem;
        }

        .log-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .log-content {
            flex: 1;
        }

        .log-action {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .log-details {
            font-size: 0.875rem;
            color: #64748b;
            word-break: break-all;
        }

        .log-meta {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.5rem;
        }

        .action-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .action-badge.login { background: #dcfce7; color: #16a34a; }
        .action-badge.logout { background: #fef3c7; color: #d97706; }
        .action-badge.settings { background: #e0e7ff; color: #4f46e5; }
        .action-badge.upload { background: #cffafe; color: #0891b2; }
        .action-badge.delete { background: #fee2e2; color: #dc2626; }
        .action-badge.default { background: #f1f5f9; color: #64748b; }
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
            <a href="logs.php" class="nav-link active"><i class="bi bi-journal-text"></i> Activity Log</a>
            <div class="nav-section">Lainnya</div>
            <a href="../index.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Ke Website</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-power"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Activity Log</h4>
                <small class="text-muted">Total: <?php echo number_format($totalLogs); ?> aktivitas tercatat</small>
            </div>
            <div class="d-flex gap-2">
                <!-- VULNERABILITY: Export exposes ALL sensitive data -->
                <a href="logs.php?export=csv" class="btn btn-outline-success">
                    <i class="bi bi-download me-1"></i> Export CSV
                </a>
            </div>
        </header>

        <div class="admin-content">
            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small">User</label>
                            <select class="form-select" name="user">
                                <option value="">Semua User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo $userFilter == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo safeOutput($user['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Action</label>
                            <select class="form-select" name="action">
                                <option value="">Semua Action</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo safeOutput($action); ?>"
                                            <?php echo $actionFilter === $action ? 'selected' : ''; ?>>
                                        <?php echo safeOutput($action); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Dari Tanggal</label>
                            <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Sampai Tanggal</label>
                            <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="logs.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Warning Banner - demonstrates Information Disclosure -->
            <div class="alert alert-warning d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>
                    <strong>System Debug Mode Active</strong> - 
                    Detailed logs including credentials and system paths are being recorded.
                    <!-- VULNERABILITY: This warns about the vulnerability but exposes it anyway -->
                </div>
            </div>

            <!-- Activity Logs -->
            <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal display-1 text-muted"></i>
                    <h5 class="mt-3">Tidak ada aktivitas</h5>
                    <p class="text-muted">Belum ada log yang sesuai dengan filter</p>
                </div>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php
                    // Determine action type for styling
                    $actionType = 'default';
                    if (strpos($log['action'], 'login') !== false) $actionType = 'login';
                    elseif (strpos($log['action'], 'logout') !== false) $actionType = 'logout';
                    elseif (strpos($log['action'], 'settings') !== false) $actionType = 'settings';
                    elseif (strpos($log['action'], 'upload') !== false) $actionType = 'upload';
                    elseif (strpos($log['action'], 'delete') !== false) $actionType = 'delete';
                    ?>
                    <div class="log-item">
                        <div class="log-icon bg-light">
                            <?php if ($actionType === 'login'): ?>
                                <i class="bi bi-box-arrow-in-right text-success"></i>
                            <?php elseif ($actionType === 'logout'): ?>
                                <i class="bi bi-box-arrow-right text-warning"></i>
                            <?php elseif ($actionType === 'settings'): ?>
                                <i class="bi bi-gear text-primary"></i>
                            <?php elseif ($actionType === 'upload'): ?>
                                <i class="bi bi-cloud-upload text-info"></i>
                            <?php elseif ($actionType === 'delete'): ?>
                                <i class="bi bi-trash text-danger"></i>
                            <?php else: ?>
                                <i class="bi bi-activity text-secondary"></i>
                            <?php endif; ?>
                        </div>
                        <div class="log-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="action-badge <?php echo $actionType; ?>">
                                        <?php echo safeOutput($log['action']); ?>
                                    </span>
                                    <span class="ms-2 text-muted small">
                                        oleh <strong><?php echo $log['username'] ?? 'System'; ?></strong>
                                        <?php if ($log['role']): ?>
                                            <span class="badge bg-secondary"><?php echo $log['role']; ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <?php echo timeAgo($log['created_at']); ?>
                                </small>
                            </div>
                            
                            <!-- VULNERABILITY: Information Disclosure - Shows sensitive details without filtering -->
                            <div class="log-details mt-2 p-2 bg-light rounded">
                                <code><?php echo $log['details']; ?></code>
                            </div>
                            
                            <div class="log-meta">
                                <i class="bi bi-geo-alt"></i> IP: <?php echo $log['ip_address']; ?>
                                <span class="mx-2">•</span>
                                <i class="bi bi-clock"></i> <?php echo $log['created_at']; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&user=<?php echo urlencode($userFilter); ?>&action=<?php echo urlencode($actionFilter); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($totalPages > 10): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>&user=<?php echo urlencode($userFilter); ?>&action=<?php echo urlencode($actionFilter); ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
