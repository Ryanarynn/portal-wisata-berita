<?php
/**
 * =====================================================
 * ADMIN/UPLOAD.PHP - File Upload Handler
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITY: Unrestricted File Upload (CWE-434)
 * - Extension check dapat di-bypass
 * - Tidak memvalidasi MIME type dengan benar
 * - Tidak merename file
 * - Tidak memvalidasi file content
 * 
 * POC:
 * - Upload shell.php.jpg (double extension)
 * - Upload shell.phtml
 * - Upload dengan null byte: shell.php%00.jpg
 * - Upload file dengan header GIF89a diikuti PHP code
 */

require_once '../config.php';
require_once '../includes/functions.php';

// VULNERABILITY: Broken Access Control - sama seperti di index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    // MISSING: exit;
}

$uploadDir = '../uploads/';
$message = '';
$messageType = '';
$uploadedFiles = [];

// Create uploads directory if not exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // VULNERABILITY: No CSRF token validation

    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];

        // VULNERABILITY: Weak extension check - only checks last extension
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // "Allowed" extensions - but can be bypassed
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];

        // VULNERABILITY: Extension check can be bypassed with double extension
        // Example: shell.php.jpg will pass because last ext is jpg
        // But auf Apache with certain configs, shell.php.jpg will execute as PHP

        if (in_array($ext, $allowedExtensions)) {
            // VULNERABILITY: Using original filename - no sanitization
            // Should use: $safeFileName = uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;

            // VULNERABILITY: No content validation
            // Should check actual file content, not just extension

            // VULNERABILITY: No size limit enforcement beyond PHP defaults

            if (move_uploaded_file($fileTmp, $targetPath)) {
                $message = "File berhasil diupload: " . $fileName;
                $messageType = "success";

                // Log upload (with sensitive info - Information Disclosure)
                $userId = getCurrentUserId();
                $stmt = $conn->prepare("INSERT INTO uploads (filename, original_name, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssii", $fileName, $fileName, $ext, $fileSize, $userId);
                $stmt->execute();
                $stmt->close();

                logActivity($conn, 'file_upload', "Uploaded file: $fileName to $targetPath by user ID: $userId");
            } else {
                $message = "Gagal mengupload file!";
                $messageType = "danger";
            }
        } else {
            // VULNERABILITY: Error message reveals allowed extensions
            $message = "Extension tidak diizinkan! Hanya: " . implode(', ', $allowedExtensions);
            $messageType = "danger";
        }
    } else {
        $message = "Error uploading file: " . $file['error'];
        $messageType = "danger";
    }
}

// Get uploaded files list
$result = $conn->query("SELECT u.*, us.username 
                        FROM uploads u 
                        LEFT JOIN users us ON u.uploaded_by = us.id 
                        ORDER BY u.created_at DESC LIMIT 20");
while ($row = $result->fetch_assoc()) {
    $uploadedFiles[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File - Admin <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            background: #fff;
            transition: all 0.3s;
            cursor: pointer;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: var(--primary);
            background: rgba(30, 58, 95, 0.05);
        }

        .upload-zone i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .file-item .file-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .file-item .file-info {
            flex: 1;
        }

        .file-item .file-name {
            font-weight: 500;
        }

        .file-item .file-meta {
            font-size: 0.75rem;
            color: #64748b;
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
            <a href="upload.php" class="nav-link active"><i class="bi bi-cloud-upload"></i> Upload File</a>
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
            <h4 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Upload File</h4>
        </header>

        <div class="admin-content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Upload Form -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">Upload File Baru</h5>
                        </div>
                        <div class="card-body">
                            <!-- VULNERABILITY: Form tanpa CSRF token -->
                            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                <div class="upload-zone" id="dropZone">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <h5>Drag & Drop File</h5>
                                    <p class="text-muted mb-3">atau klik untuk memilih file</p>
                                    <input type="file" name="file" id="fileInput" class="d-none" accept="*/*">
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="document.getElementById('fileInput').click()">
                                        Pilih File
                                    </button>
                                </div>

                                <div id="selectedFile" class="mt-3" style="display: none;">
                                    <div class="file-item">
                                        <div class="file-icon bg-primary text-white"><i class="bi bi-file-earmark"></i>
                                        </div>
                                        <div class="file-info">
                                            <div class="file-name" id="fileName"></div>
                                            <div class="file-meta" id="fileSize"></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="clearFile()">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 mt-3" id="uploadBtn" disabled>
                                    <i class="bi bi-upload me-1"></i> Upload
                                </button>
                            </form>

                            <div class="mt-4 p-3 bg-light rounded">
                                <h6 class="mb-2"><i class="bi bi-info-circle me-1"></i> Info</h6>
                                <small class="text-muted">
                                    Format yang diizinkan: JPG, PNG, GIF, PDF, DOC, DOCX<br>
                                    Maksimal ukuran: 10MB
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Uploaded Files List -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">File Terupload</h5>
                            <span class="badge bg-primary"><?php echo count($uploadedFiles); ?> files</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($uploadedFiles)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-folder2-open display-4"></i>
                                    <p class="mt-2">Belum ada file yang diupload</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>File</th>
                                                <th>Type</th>
                                                <th>Size</th>
                                                <th>Uploader</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($uploadedFiles as $file): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-file-earmark text-primary"></i>
                                                            <span><?php echo safeOutput($file['original_name']); ?></span>
                                                        </div>
                                                    </td>
                                                    <td><span
                                                            class="badge bg-secondary"><?php echo strtoupper($file['file_type']); ?></span>
                                                    </td>
                                                    <td><?php echo number_format($file['file_size'] / 1024, 1); ?> KB</td>
                                                    <td><?php echo $file['username'] ?? 'Unknown'; ?></td>
                                                    <td>
                                                        <a href="../uploads/<?php echo $file['filename']; ?>"
                                                            class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="../download.php?file=<?php echo urlencode($file['filename']); ?>"
                                                            class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const selectedFile = document.getElementById('selectedFile');
        const uploadBtn = document.getElementById('uploadBtn');

        // Drag & drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                showSelectedFile();
            }
        });

        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', showSelectedFile);

        function showSelectedFile() {
            if (fileInput.files.length) {
                const file = fileInput.files[0];
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = formatSize(file.size);
                selectedFile.style.display = 'block';
                uploadBtn.disabled = false;
            }
        }

        function clearFile() {
            fileInput.value = '';
            selectedFile.style.display = 'none';
            uploadBtn.disabled = true;
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    </script>
</body>

</html>