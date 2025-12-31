<?php
/**
 * =====================================================
 * RESET.PHP - Lab Reset Script
 * Portal Wisata & Berita Kota - Security Lab
 * =====================================================
 * 
 * Script ini akan mereset database dan file uploads
 * ke kondisi awal setelah di-retas/diuji
 * 
 * Cara penggunaan:
 * 1. Manual: Akses /reset.php?key=YOUR_SECRET_KEY
 * 2. Cron Job: curl "https://yoursite.com/reset.php?key=YOUR_SECRET_KEY"
 * 
 * Untuk InfinityFree, gunakan external cron service:
 * - cron-job.org (gratis)
 * - easycron.com
 * - setcronjob.com
 */

// =====================================================
// KONFIGURASI RESET
// =====================================================

// Secret key untuk akses reset (GANTI INI!)
define('RESET_SECRET_KEY', 'X7kP2mN9qR4wT8yB5vL3'); // Secret key yang kuat

// Aktifkan/nonaktifkan fitur reset
define('ALLOW_RESET', true);

// Log reset activity
define('LOG_RESET', true);

// =====================================================
// SECURITY CHECK
// =====================================================

// Cek apakah reset diaktifkan
if (!ALLOW_RESET) {
    die(json_encode(['error' => 'Reset adalah dinonaktifkan']));
}

// Validasi secret key
$providedKey = $_GET['key'] ?? '';
if ($providedKey !== RESET_SECRET_KEY) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid reset key', 'hint' => 'Tambahkan ?key=YOUR_SECRET_KEY']));
}

// =====================================================
// DATABASE CONNECTION
// =====================================================

require_once 'config.php';

// =====================================================
// DATA ASLI UNTUK RESET
// =====================================================

// PENTING: Password disimpan dalam PLAIN TEXT agar sesuai dengan
// login.php yang menggunakan SQL Injection vulnerable query
// (membandingkan password langsung tanpa password_verify)

// Data users default - PASSWORD PLAIN TEXT!
$defaultUsers = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'email' => 'admin@portal.com',
        'full_name' => 'Administrator',
        'role' => 'admin',
        'bio' => 'Administrator sistem Portal Wisata & Berita'
    ],
    [
        'username' => 'editor',
        'password' => 'editor123',
        'email' => 'editor@portal.com',
        'full_name' => 'Sarah Editor',
        'role' => 'editor',
        'bio' => 'Editor konten berita dan wisata'
    ],
    [
        'username' => 'contributor',
        'password' => 'contrib123',
        'email' => 'contributor@portal.com',
        'full_name' => 'Budi Contributor',
        'role' => 'contributor',
        'bio' => 'Kontributor artikel wisata'
    ]
];

// Data settings default
$defaultSettings = [
    ['setting_key' => 'site_name', 'setting_value' => 'Portal Wisata & Berita', 'setting_group' => 'general'],
    ['setting_key' => 'site_tagline', 'setting_value' => 'Informasi Terkini & Destinasi Menarik', 'setting_group' => 'general'],
    ['setting_key' => 'contact_email', 'setting_value' => 'info@portal.com', 'setting_group' => 'contact'],
    ['setting_key' => 'contact_phone', 'setting_value' => '+62 21 1234567', 'setting_group' => 'contact'],
    ['setting_key' => 'contact_address', 'setting_value' => 'Jl. Contoh No. 123, Jakarta', 'setting_group' => 'contact'],
    ['setting_key' => 'debug_mode', 'setting_value' => '1', 'setting_group' => 'advanced'],
    ['setting_key' => 'show_sql_errors', 'setting_value' => '1', 'setting_group' => 'advanced'],
];

// Komentar sampel yang AMAN (tanpa XSS)
$sampleComments = [
    ['article_id' => 1, 'name' => 'Budi Santoso', 'email' => 'budi@email.com', 'comment' => 'Artikel yang sangat informatif! Terima kasih atas informasinya.'],
    ['article_id' => 1, 'name' => 'Siti Aminah', 'email' => 'siti@email.com', 'comment' => 'Saya setuju dengan poin-poin yang disampaikan penulis.'],
    ['article_id' => 2, 'name' => 'Ahmad Fauzi', 'email' => 'ahmad@email.com', 'comment' => 'Berita yang sangat menarik untuk diikuti perkembangannya.'],
    ['article_id' => 3, 'name' => 'Dewi Lestari', 'email' => 'dewi@email.com', 'comment' => 'Bagus sekali! Semoga ekonomi kita semakin stabil.'],
];

// =====================================================
// RESET FUNCTIONS
// =====================================================

$resetLog = [];

function logReset($message)
{
    global $resetLog;
    $resetLog[] = date('H:i:s') . ' - ' . $message;
}

function resetUsers($conn, $defaultUsers)
{
    // Hapus semua users
    $conn->query("DELETE FROM users");
    $conn->query("ALTER TABLE users AUTO_INCREMENT = 1");

    // Insert users default
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, bio, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");

    foreach ($defaultUsers as $user) {
        $stmt->bind_param(
            "ssssss",
            $user['username'],
            $user['password'],
            $user['email'],
            $user['full_name'],
            $user['role'],
            $user['bio']
        );
        $stmt->execute();
    }
    $stmt->close();

    logReset("Reset users: " . count($defaultUsers) . " users restored");
    return count($defaultUsers);
}

function resetComments($conn, $sampleComments)
{
    // Hapus semua comments (termasuk yang mengandung XSS)
    $conn->query("DELETE FROM comments");
    $conn->query("ALTER TABLE comments AUTO_INCREMENT = 1");

    // Insert sample comments yang bersih
    $stmt = $conn->prepare("INSERT INTO comments (article_id, name, email, comment, status, created_at) VALUES (?, ?, ?, ?, 'approved', NOW())");

    foreach ($sampleComments as $comment) {
        $stmt->bind_param(
            "isss",
            $comment['article_id'],
            $comment['name'],
            $comment['email'],
            $comment['comment']
        );
        $stmt->execute();
    }
    $stmt->close();

    logReset("Reset comments: " . count($sampleComments) . " clean comments added");
    return count($sampleComments);
}

function resetSettings($conn, $defaultSettings)
{
    // Hapus semua settings
    $conn->query("DELETE FROM settings");

    // Insert settings default
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)");

    foreach ($defaultSettings as $setting) {
        $stmt->bind_param(
            "sss",
            $setting['setting_key'],
            $setting['setting_value'],
            $setting['setting_group']
        );
        $stmt->execute();
    }
    $stmt->close();

    logReset("Reset settings: " . count($defaultSettings) . " settings restored");
    return count($defaultSettings);
}

function resetSubscribers($conn)
{
    // Hapus semua subscribers (bersihkan XSS dari nama)
    $conn->query("DELETE FROM subscribers");
    $conn->query("ALTER TABLE subscribers AUTO_INCREMENT = 1");

    // Insert beberapa subscriber sample
    $conn->query("INSERT INTO subscribers (email, name, status) VALUES 
        ('subscriber1@email.com', 'John Doe', 'active'),
        ('subscriber2@email.com', 'Jane Smith', 'active'),
        ('subscriber3@email.com', 'Ahmad Ali', 'active')
    ");

    logReset("Reset subscribers: cleaned and restored 3 sample subscribers");
    return 3;
}

function resetActivityLog($conn)
{
    // Hapus activity log
    $conn->query("DELETE FROM activity_log");
    $conn->query("ALTER TABLE activity_log AUTO_INCREMENT = 1");

    // Insert log reset
    $conn->query("INSERT INTO activity_log (user_id, action, details, ip_address, created_at) VALUES 
        (1, 'system_reset', 'Lab berhasil di-reset ke kondisi awal', '" . $_SERVER['REMOTE_ADDR'] . "', NOW())
    ");

    logReset("Reset activity_log: cleared all logs");
    return true;
}

function cleanArticles($conn)
{
    // Reset artikel ke konten asli (hapus stored XSS)
    // Ini optional - jika ingin menjaga artikel asli

    // Contoh: bersihkan script dari content artikel
    $result = $conn->query("SELECT id, content FROM articles");
    $cleaned = 0;

    while ($row = $result->fetch_assoc()) {
        $originalContent = $row['content'];
        // Hapus script tags
        $cleanContent = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $originalContent);
        // Hapus event handlers
        $cleanContent = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $cleanContent);

        if ($cleanContent !== $originalContent) {
            $stmt = $conn->prepare("UPDATE articles SET content = ? WHERE id = ?");
            $stmt->bind_param("si", $cleanContent, $row['id']);
            $stmt->execute();
            $stmt->close();
            $cleaned++;
        }
    }

    logReset("Cleaned articles: $cleaned articles sanitized from XSS");
    return $cleaned;
}

function cleanUploads($uploadDir = 'uploads/')
{
    // Hapus file berbahaya dari uploads
    $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'sh', 'pl', 'py', 'rb', 'asp', 'aspx', 'jsp', 'cgi'];
    $deleted = 0;

    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..')
                continue;

            $filePath = $uploadDir . $file;
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            // Hapus file dengan ekstensi berbahaya
            if (in_array($extension, $dangerousExtensions)) {
                if (unlink($filePath)) {
                    logReset("Deleted dangerous file: $file");
                    $deleted++;
                }
            }

            // Hapus file dengan double extension (shell.php.jpg)
            if (preg_match('/\.(php|phtml|php\d|phar)\./i', $file)) {
                if (unlink($filePath)) {
                    logReset("Deleted double-extension file: $file");
                    $deleted++;
                }
            }
        }
    }

    logReset("Cleaned uploads: $deleted dangerous files removed");
    return $deleted;
}

function resetSessions()
{
    // Hapus semua session untuk logout paksa
    $sessionPath = session_save_path();
    if (is_dir($sessionPath)) {
        $files = glob($sessionPath . '/sess_*');
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    // Destroy current session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    logReset("Reset sessions: all sessions cleared");
    return true;
}

// =====================================================
// EXECUTE RESET
// =====================================================

$startTime = microtime(true);
$results = [];

try {
    // 1. Reset Users
    $results['users'] = resetUsers($conn, $defaultUsers);

    // 2. Reset Comments (hapus XSS)
    $results['comments'] = resetComments($conn, $sampleComments);

    // 3. Reset Settings
    $results['settings'] = resetSettings($conn, $defaultSettings);

    // 4. Reset Subscribers
    $results['subscribers'] = resetSubscribers($conn);

    // 5. Reset Activity Log
    $results['activity_log'] = resetActivityLog($conn);

    // 6. Clean Articles dari XSS
    $results['articles_cleaned'] = cleanArticles($conn);

    // 7. Clean Uploads dari malware
    $results['uploads_cleaned'] = cleanUploads('uploads/');

    // 8. Reset Sessions
    $results['sessions'] = resetSessions();

    $success = true;
    $message = 'Lab berhasil di-reset ke kondisi awal!';

} catch (Exception $e) {
    $success = false;
    $message = 'Error: ' . $e->getMessage();
    logReset("ERROR: " . $e->getMessage());
}

$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2);

// =====================================================
// LOG TO FILE
// =====================================================

if (LOG_RESET) {
    $logEntry = date('Y-m-d H:i:s') . " | IP: " . $_SERVER['REMOTE_ADDR'] . " | " . ($success ? 'SUCCESS' : 'FAILED') . " | " . $executionTime . "ms\n";
    @file_put_contents('reset_log.txt', $logEntry, FILE_APPEND);
}

// =====================================================
// OUTPUT RESPONSE
// =====================================================

header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $message,
    'timestamp' => date('Y-m-d H:i:s'),
    'execution_time_ms' => $executionTime,
    'results' => $results,
    'log' => $resetLog,
    'next_reset_hint' => 'Setup cron job untuk auto-reset setiap beberapa jam'
], JSON_PRETTY_PRINT);
