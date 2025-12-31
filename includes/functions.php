<?php
/**
 * =====================================================
 * FUNCTIONS.PHP - Helper Functions
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * Kumpulan fungsi helper yang digunakan di seluruh website
 */

/**
 * Format tanggal ke format Indonesia
 * @param string $date - Tanggal dalam format MySQL
 * @param bool $withTime - Sertakan waktu
 * @return string
 */
function formatTanggal($date, $withTime = false)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($date);
    $hari = date('j', $timestamp);
    $bulanNum = date('n', $timestamp);
    $tahun = date('Y', $timestamp);

    $result = "$hari {$bulan[$bulanNum]} $tahun";

    if ($withTime) {
        $result .= ' ' . date('H:i', $timestamp) . ' WIB';
    }

    return $result;
}

/**
 * Format tanggal relatif (time ago)
 * @param string $date - Tanggal dalam format MySQL
 * @return string
 */
function timeAgo($date)
{
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' minggu yang lalu';
    } else {
        return formatTanggal($date);
    }
}

/**
 * Generate URL slug dari string
 * @param string $text
 * @return string
 */
function generateSlug($text)
{
    // Lowercase
    $text = strtolower($text);
    // Remove special characters
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    // Replace spaces with dashes
    $text = preg_replace('/[\s-]+/', '-', $text);
    // Trim dashes from ends
    $text = trim($text, '-');
    return $text;
}

/**
 * Truncate text ke panjang tertentu
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncateText($text, $length = 150, $suffix = '...')
{
    $text = strip_tags($text);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Hitung estimasi waktu baca artikel
 * @param string $content
 * @return int - Menit
 */
function readingTime($content)
{
    $text = strip_tags($content);
    $wordCount = str_word_count($text);
    $minutes = ceil($wordCount / 200); // Average 200 words per minute
    return max(1, $minutes);
}

/**
 * Format angka views
 * @param int $number
 * @return string
 */
function formatViews($number)
{
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return number_format($number);
}

/**
 * Get avatar color based on name
 * @param string $name
 * @return string - Hex color
 */
function getAvatarColor($name)
{
    $colors = ['#1e3a5f', '#047857', '#7c3aed', '#db2777', '#ea580c', '#0891b2', '#4f46e5', '#dc2626'];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

/**
 * Get initials from name
 * @param string $name
 * @return string
 */
function getInitials($name)
{
    $words = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials ?: 'U';
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is editor or higher
 * @return bool
 */
function isEditor()
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'editor']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 * @return string|null
 */
function getCurrentUserName()
{
    return $_SESSION['full_name'] ?? null;
}

/**
 * Sanitize output untuk HTML (SAFE version)
 * NOTE: Ini adalah versi aman, tidak digunakan di area yang vulnerable
 * @param string $text
 * @return string
 */
function safeOutput($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Get setting value from database
 * VULNERABILITY: Information Disclosure - menampilkan semua settings
 * @param mysqli $conn
 * @param string $key
 * @return string|null
 */
function getSetting($conn, $key)
{
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = '$key'");
    if ($result && $row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

/**
 * Get multiple settings
 * @param mysqli $conn
 * @param array $keys
 * @return array
 */
function getSettings($conn, $keys = [])
{
    $settings = [];
    $query = "SELECT setting_key, setting_value FROM settings";
    if (!empty($keys)) {
        $keyList = "'" . implode("','", $keys) . "'";
        $query .= " WHERE setting_key IN ($keyList)";
    }
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

/**
 * Get breaking news
 * @param mysqli $conn
 * @param int $limit
 * @return array
 */
function getBreakingNews($conn, $limit = 5)
{
    $news = [];
    // Fallback to featured articles if breaking_news table doesn't exist
    $query = "SELECT id, title, CONCAT('view.php?id=', id) as link 
              FROM articles 
              WHERE is_breaking = 1 AND status = 'published'
              ORDER BY created_at DESC 
              LIMIT $limit";
    $result = @$conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $news[] = $row;
        }
    }
    return $news;
}

/**
 * Get trending articles (most viewed)
 * @param mysqli $conn
 * @param int $limit
 * @return array
 */
function getTrendingArticles($conn, $limit = 5)
{
    $articles = [];
    $query = "SELECT a.id, a.title, a.slug, a.views, a.type, a.created_at,
                     c.name as category_name, c.slug as category_slug
              FROM articles a
              LEFT JOIN categories c ON a.category_id = c.id
              WHERE a.status = 'published'
              ORDER BY a.views DESC
              LIMIT $limit";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    return $articles;
}

/**
 * Get recent articles
 * @param mysqli $conn
 * @param int $limit
 * @param string $type - 'berita', 'wisata', or null for all
 * @return array
 */
function getRecentArticles($conn, $limit = 6, $type = null)
{
    $articles = [];
    $where = "a.status = 'published'";
    if ($type) {
        $where .= " AND a.type = '$type'";
    }

    $query = "SELECT a.*, c.name as category_name, c.slug as category_slug,
                     u.full_name as author_name
              FROM articles a
              LEFT JOIN categories c ON a.category_id = c.id
              LEFT JOIN users u ON a.author_id = u.id
              WHERE $where
              ORDER BY a.created_at DESC
              LIMIT $limit";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    return $articles;
}

/**
 * Get featured articles
 * @param mysqli $conn
 * @param int $limit
 * @return array
 */
function getFeaturedArticles($conn, $limit = 5)
{
    $articles = [];
    $query = "SELECT a.*, c.name as category_name, c.slug as category_slug,
                     u.full_name as author_name
              FROM articles a
              LEFT JOIN categories c ON a.category_id = c.id
              LEFT JOIN users u ON a.author_id = u.id
              WHERE a.status = 'published' AND a.is_featured = 1
              ORDER BY a.created_at DESC
              LIMIT $limit";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    return $articles;
}

/**
 * Get categories by type
 * @param mysqli $conn
 * @param string $type - 'berita' or 'wisata'
 * @return array
 */
function getCategoriesByType($conn, $type)
{
    $categories = [];
    $query = "SELECT * FROM categories WHERE type = '$type' ORDER BY sort_order ASC";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

/**
 * Get all categories grouped by type
 * @param mysqli $conn
 * @return array
 */
function getAllCategories($conn)
{
    $categories = ['berita' => [], 'wisata' => []];
    $query = "SELECT * FROM categories ORDER BY type, sort_order ASC";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $categories[$row['type']][] = $row;
    }
    return $categories;
}

/**
 * Get popular tags
 * @param mysqli $conn
 * @param int $limit
 * @return array
 */
function getPopularTags($conn, $limit = 10)
{
    $tags = [];
    $query = "SELECT t.*, COUNT(at.article_id) as article_count
              FROM tags t
              LEFT JOIN article_tags at ON t.id = at.tag_id
              GROUP BY t.id
              ORDER BY article_count DESC
              LIMIT $limit";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    return $tags;
}

/**
 * Log activity
 * VULNERABILITY: Logs sensitive data for Information Disclosure
 * @param mysqli $conn
 * @param string $action
 * @param string $details
 */
function logActivity($conn, $action, $details)
{
    $userId = getCurrentUserId() ?? 'NULL';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // VULNERABLE: Logs sensitive information
    $query = "INSERT INTO activity_log (user_id, action, details, ip_address) 
              VALUES ($userId, '$action', '$details', '$ip')";
    $conn->query($query);
}

/**
 * Generate breadcrumb
 * @param array $items - Array of ['label' => '', 'url' => '']
 * @return string - HTML breadcrumb
 */
function generateBreadcrumb($items)
{
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    foreach ($items as $i => $item) {
        if ($i === count($items) - 1) {
            $html .= '<li class="breadcrumb-item active">' . safeOutput($item['label']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . safeOutput($item['label']) . '</a></li>';
        }
    }
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Get article by ID
 * VULNERABILITY: IDOR - No ownership/permission check
 * @param mysqli $conn
 * @param int $id
 * @return array|null
 */
function getArticleById($conn, $id)
{
    // VULNERABLE: Returns ANY article including draft/pending without permission check
    $query = "SELECT a.*, c.name as category_name, c.slug as category_slug,
                     u.full_name as author_name, u.bio as author_bio, u.avatar as author_avatar
              FROM articles a
              LEFT JOIN categories c ON a.category_id = c.id
              LEFT JOIN users u ON a.author_id = u.id
              WHERE a.id = $id";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Increment article views
 * @param mysqli $conn
 * @param int $articleId
 */
function incrementViews($conn, $articleId)
{
    // Just update view count, skip logging to avoid missing table errors
    @$conn->query("UPDATE articles SET views = views + 1 WHERE id = $articleId");
}

/**
 * Check if article is bookmarked
 * @param mysqli $conn
 * @param int $articleId
 * @return bool
 */
function isBookmarked($conn, $articleId)
{
    $sessionId = session_id();
    $result = $conn->query("SELECT id FROM bookmarks WHERE session_id = '$sessionId' AND article_id = $articleId");
    return $result && $result->num_rows > 0;
}

/**
 * Toggle bookmark
 * @param mysqli $conn
 * @param int $articleId
 * @return bool - true if added, false if removed
 */
function toggleBookmark($conn, $articleId)
{
    $sessionId = session_id();

    if (isBookmarked($conn, $articleId)) {
        $conn->query("DELETE FROM bookmarks WHERE session_id = '$sessionId' AND article_id = $articleId");
        return false;
    } else {
        $conn->query("INSERT INTO bookmarks (session_id, article_id) VALUES ('$sessionId', $articleId)");
        return true;
    }
}

/**
 * Get user bookmarks
 * @param mysqli $conn
 * @return array
 */
function getUserBookmarks($conn)
{
    $sessionId = session_id();
    $articles = [];

    $query = "SELECT a.*, c.name as category_name
              FROM bookmarks b
              JOIN articles a ON b.article_id = a.id
              LEFT JOIN categories c ON a.category_id = c.id
              WHERE b.session_id = '$sessionId'
              ORDER BY b.created_at DESC";

    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    return $articles;
}

/**
 * Get related articles
 * @param mysqli $conn
 * @param int $articleId
 * @param int $categoryId
 * @param int $limit
 * @return array
 */
function getRelatedArticles($conn, $articleId, $categoryId, $limit = 5)
{
    $articles = [];
    $query = "SELECT a.id, a.title, a.slug, a.created_at, a.views, a.type,
                     c.name as category_name
              FROM articles a
              LEFT JOIN categories c ON a.category_id = c.id
              WHERE a.category_id = $categoryId 
              AND a.id != $articleId 
              AND a.status = 'published'
              ORDER BY a.created_at DESC
              LIMIT $limit";

    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    return $articles;
}

/**
 * Get comments for article
 * VULNERABILITY: Stored XSS - Comments displayed without sanitization
 * @param mysqli $conn
 * @param int $articleId
 * @return array
 */
function getArticleComments($conn, $articleId)
{
    $comments = [];
    $query = "SELECT * FROM comments 
              WHERE article_id = $articleId 
              ORDER BY created_at DESC";

    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    return $comments;
}

/**
 * Get article tags
 * @param mysqli $conn
 * @param int $articleId
 * @return array
 */
function getArticleTags($conn, $articleId)
{
    $tags = [];
    $query = "SELECT t.* FROM tags t
              JOIN article_tags at ON t.id = at.tag_id
              WHERE at.article_id = $articleId";

    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    return $tags;
}

/**
 * Count comments for article
 * @param mysqli $conn
 * @param int $articleId
 * @return int
 */
function countComments($conn, $articleId)
{
    $result = $conn->query("SELECT COUNT(*) as count FROM comments WHERE article_id = $articleId");
    return $result ? $result->fetch_assoc()['count'] : 0;
}
