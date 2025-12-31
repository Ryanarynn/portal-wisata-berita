-- =====================================================
-- DATABASE SCHEMA: Portal Wisata & Berita Kota
-- For Fresh Hosting Deployment
-- =====================================================
-- 
-- HOW TO IMPORT:
-- 1. Create database in cPanel/phpMyAdmin: portal_wisata_berita
-- 2. Select the database
-- 3. Import this file via phpMyAdmin
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- =====================================================
-- TABEL USERS
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100),
    `bio` TEXT,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('admin', 'editor', 'contributor') DEFAULT 'contributor',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL CATEGORIES
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `type` ENUM('berita', 'wisata') NOT NULL,
    `description` TEXT,
    `icon` VARCHAR(50) DEFAULT 'bi-folder',
    `color` VARCHAR(20) DEFAULT '#1e3a5f',
    `parent_id` INT DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL ARTICLES
-- =====================================================
CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `excerpt` TEXT,
    `image` VARCHAR(255),
    `category_id` INT,
    `author_id` INT,
    `type` ENUM('berita', 'wisata') NOT NULL,
    `views` INT DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_breaking` TINYINT(1) DEFAULT 0,
    `status` ENUM('published', 'draft', 'pending') DEFAULT 'published',
    `published_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL TAGS
-- =====================================================
CREATE TABLE IF NOT EXISTS `tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL ARTICLE_TAGS
-- =====================================================
CREATE TABLE IF NOT EXISTS `article_tags` (
    `article_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    PRIMARY KEY (`article_id`, `tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL COMMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100),
    `comment` TEXT NOT NULL,
    `status` ENUM('approved', 'pending', 'spam') DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL SUBSCRIBERS
-- =====================================================
CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(100),
    `status` ENUM('active', 'unsubscribed') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL SETTINGS
-- =====================================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL BOOKMARKS
-- =====================================================
CREATE TABLE IF NOT EXISTS `bookmarks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(100) NOT NULL,
    `article_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_bookmark` (`session_id`, `article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL UPLOADS
-- =====================================================
CREATE TABLE IF NOT EXISTS `uploads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100),
    `file_size` INT,
    `uploaded_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL ACTIVITY_LOG
-- =====================================================
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(100),
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DATA: USERS
-- =====================================================
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `bio`, `role`) VALUES
('admin', 'admin123', 'Administrator', 'admin@portal.com', 'Administrator sistem portal berita dan wisata', 'admin'),
('editor1', 'editor123', 'Budi Santoso', 'budi@portal.com', 'Editor senior dengan pengalaman 5 tahun di bidang jurnalistik', 'editor'),
('writer1', 'writer123', 'Siti Rahayu', 'siti@portal.com', 'Penulis travel dan kuliner', 'contributor'),
('writer2', 'writer123', 'Ahmad Fauzi', 'ahmad@portal.com', 'Penulis berita politik dan ekonomi', 'contributor');

-- =====================================================
-- INSERT DATA: CATEGORIES BERITA
-- =====================================================
INSERT INTO `categories` (`name`, `slug`, `type`, `description`, `icon`, `color`, `sort_order`) VALUES
('Politik', 'politik', 'berita', 'Berita politik terkini', 'bi-bank', '#dc2626', 1),
('Ekonomi', 'ekonomi', 'berita', 'Berita ekonomi dan keuangan', 'bi-graph-up', '#2563eb', 2),
('Teknologi', 'teknologi', 'berita', 'Berita teknologi dan inovasi', 'bi-cpu', '#7c3aed', 3),
('Olahraga', 'olahraga', 'berita', 'Berita olahraga nasional dan internasional', 'bi-trophy', '#16a34a', 4),
('Hiburan', 'hiburan', 'berita', 'Berita hiburan dan selebriti', 'bi-film', '#ec4899', 5),
('Pendidikan', 'pendidikan', 'berita', 'Berita pendidikan dan akademik', 'bi-mortarboard', '#f59e0b', 6);

-- =====================================================
-- INSERT DATA: CATEGORIES WISATA
-- =====================================================
INSERT INTO `categories` (`name`, `slug`, `type`, `description`, `icon`, `color`, `sort_order`) VALUES
('Pantai', 'pantai', 'wisata', 'Destinasi wisata pantai', 'bi-water', '#0ea5e9', 1),
('Gunung', 'gunung', 'wisata', 'Destinasi wisata pegunungan', 'bi-triangle', '#22c55e', 2),
('Kuliner', 'kuliner', 'wisata', 'Wisata kuliner dan makanan khas', 'bi-cup-hot', '#f97316', 3),
('Budaya', 'budaya', 'wisata', 'Wisata budaya dan sejarah', 'bi-building', '#a855f7', 4),
('Taman', 'taman', 'wisata', 'Taman dan tempat rekreasi', 'bi-tree', '#10b981', 5);

-- =====================================================
-- INSERT DATA: TAGS
-- =====================================================
INSERT INTO `tags` (`name`, `slug`) VALUES
('trending', 'trending'),
('viral', 'viral'),
('breaking', 'breaking'),
('terpopuler', 'terpopuler'),
('Indonesia', 'indonesia'),
('Jakarta', 'jakarta'),
('Bali', 'bali'),
('wisata-alam', 'wisata-alam'),
('liburan', 'liburan'),
('kuliner', 'kuliner'),
('politik', 'politik'),
('ekonomi', 'ekonomi'),
('teknologi', 'teknologi'),
('olahraga', 'olahraga');

-- =====================================================
-- INSERT DATA: ARTICLES BERITA
-- =====================================================
INSERT INTO `articles` (`title`, `slug`, `content`, `excerpt`, `category_id`, `author_id`, `type`, `views`, `is_featured`, `is_breaking`, `status`) VALUES
('Pembangunan Jalan Tol Baru Dimulai Tahun Ini', 'pembangunan-jalan-tol-baru', 'Pemerintah kota mengumumkan dimulainya proyek pembangunan jalan tol baru yang akan menghubungkan pusat kota dengan kawasan industri. Proyek ini diharapkan selesai dalam waktu dua tahun.', 'Pemerintah kota mengumumkan dimulainya proyek pembangunan jalan tol baru yang akan menghubungkan pusat kota dengan kawasan industri.', 1, 1, 'berita', 1523, 1, 1, 'published'),
('Timnas Indonesia Lolos ke Piala Asia 2024', 'timnas-indonesia-lolos-piala-asia', 'Timnas Indonesia berhasil meloloskan diri ke Piala Asia 2024 setelah mengalahkan Vietnam dengan skor telak 3-0.', 'Timnas Indonesia berhasil meloloskan diri ke Piala Asia 2024 setelah mengalahkan Vietnam dengan skor 3-0.', 4, 2, 'berita', 5689, 0, 1, 'published'),
('Apple Luncurkan iPhone 16 dengan Fitur AI', 'apple-luncurkan-iphone-16', 'Apple resmi meluncurkan iPhone 16 dengan berbagai fitur kecerdasan buatan (AI) yang revolusioner.', 'Apple resmi meluncurkan iPhone 16 dengan berbagai fitur kecerdasan buatan (AI) yang revolusioner.', 3, 1, 'berita', 3456, 0, 1, 'published'),
('Update Pemilu: Partisipasi Pemilih Capai 82.5%', 'update-pemilu-partisipasi', 'Komisi Pemilihan Umum (KPU) mengumumkan bahwa tingkat partisipasi pemilih pada Pemilu 2024 mencapai 82.5%.', 'KPU mengumumkan tingkat partisipasi pemilih pada Pemilu 2024 mencapai 82.5%.', 1, 4, 'berita', 2234, 0, 1, 'published'),
('Inflasi Terkendali di Level 2.8%', 'inflasi-terkendali', 'Bank Indonesia melaporkan bahwa tingkat inflasi tahunan berhasil dijaga di level 2.8%, sesuai dengan target.', 'Bank Indonesia melaporkan tingkat inflasi tahunan berhasil dijaga di level 2.8%.', 2, 4, 'berita', 1876, 0, 0, 'published'),
('Startup Lokal Raih Pendanaan Rp 500 Miliar', 'startup-lokal-pendanaan', 'Sebuah startup teknologi lokal berhasil meraih pendanaan Seri B senilai Rp 500 miliar dari investor global.', 'Startup teknologi lokal berhasil meraih pendanaan Seri B senilai Rp 500 miliar.', 3, 2, 'berita', 1245, 0, 0, 'published');

-- =====================================================
-- INSERT DATA: ARTICLES WISATA
-- =====================================================
INSERT INTO `articles` (`title`, `slug`, `content`, `excerpt`, `category_id`, `author_id`, `type`, `views`, `is_featured`, `is_breaking`, `status`) VALUES
('Wisata Kuliner: 10 Makanan Khas yang Wajib Dicoba', 'wisata-kuliner-10-makanan', 'Indonesia memiliki kekayaan kuliner yang luar biasa beragam. Dari Sabang sampai Merauke, setiap daerah memiliki makanan khas.', 'Jelajahi 10 makanan khas Indonesia yang wajib dicoba saat berkunjung ke berbagai daerah.', 9, 3, 'wisata', 6789, 1, 0, 'published'),
('Pantai Indah: Surga Tersembunyi di Pesisir Selatan', 'pantai-indah-pesisir-selatan', 'Jauh dari hiruk pikuk kota, terdapat sebuah pantai yang masih alami dan jarang dikunjungi wisatawan.', 'Temukan pantai tersembunyi dengan pasir putih dan air jernih di pesisir selatan.', 7, 3, 'wisata', 4532, 0, 0, 'published'),
('Pendakian Gunung Rinjani: Tips dan Persiapan', 'pendakian-gunung-rinjani', 'Gunung Rinjani di Lombok adalah salah satu destinasi pendakian paling populer di Indonesia.', 'Panduan lengkap pendakian Gunung Rinjani dengan tips dan persiapan yang perlu diketahui.', 8, 2, 'wisata', 3421, 0, 0, 'published'),
('Menjelajah Candi Borobudur di Pagi Hari', 'menjelajah-candi-borobudur', 'Candi Borobudur adalah salah satu keajaiban dunia yang wajib dikunjungi.', 'Pengalaman menjelajah Candi Borobudur dan menyaksikan sunrise yang memukau.', 10, 3, 'wisata', 2876, 0, 0, 'published'),
('Taman Nasional Komodo: Rumah Kadal Raksasa', 'taman-nasional-komodo', 'Taman Nasional Komodo adalah satu-satunya tempat di dunia di mana Anda bisa melihat komodo di habitat aslinya.', 'Jelajahi Taman Nasional Komodo dan temui kadal raksasa di habitat aslinya.', 7, 2, 'wisata', 5234, 1, 0, 'published');

-- Draft article for IDOR testing
INSERT INTO `articles` (`title`, `slug`, `content`, `excerpt`, `category_id`, `author_id`, `type`, `views`, `is_featured`, `is_breaking`, `status`) VALUES
('DRAFT: Rahasia Wisata Tersembunyi', 'draft-rahasia-wisata', 'Artikel ini masih dalam tahap penulisan dan belum seharusnya bisa diakses oleh publik Jean-luc picard.', 'Artikel draft yang seharusnya tidak bisa diakses publik.', 7, 1, 'wisata', 0, 0, 0, 'draft');

-- =====================================================
-- INSERT DATA: ARTICLE_TAGS
-- =====================================================
INSERT INTO `article_tags` (`article_id`, `tag_id`) VALUES
(1, 5), (1, 6), (2, 1), (2, 5), (2, 14), (3, 1), (3, 13), (4, 1), (4, 5), (4, 11),
(5, 12), (6, 13), (7, 1), (7, 4), (7, 10), (8, 7), (8, 8), (8, 9), (9, 8), (9, 9), (10, 5), (10, 6), (11, 7), (11, 8);

-- =====================================================
-- INSERT DATA: COMMENTS
-- =====================================================
INSERT INTO `comments` (`article_id`, `name`, `email`, `comment`) VALUES
(1, 'Budi Warga', 'budi@email.com', 'Semoga proyek ini berjalan lancar!'),
(1, 'Siti Wati', 'siti@email.com', 'Akhirnya! Sudah lama menunggu jalan tol ini.'),
(2, 'Andi Suporter', 'andi@email.com', 'INDONESIA MAJU! Bangga dengan Timnas kita!'),
(7, 'Food Lover', 'foodie@email.com', 'Rendang memang nomor 1!'),
(7, 'Traveler123', 'travel@email.com', 'Wah lengkap banget! Saved untuk referensi.'),
(8, 'Beach Lover', 'beach@email.com', 'Pantainya indah sekali!');

-- =====================================================
-- INSERT DATA: SETTINGS
-- =====================================================
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Portal Wisata & Berita Kota'),
('site_tagline', 'Informasi Terkini & Destinasi Terbaik'),
('site_email', 'info@portal-wisata-berita.com'),
('site_phone', '+62 21 1234567'),
('site_address', 'Jl. Media Raya No. 123, Jakarta Pusat'),
('social_facebook', 'https://facebook.com/portalwisata'),
('social_twitter', 'https://twitter.com/portalwisata'),
('social_instagram', 'https://instagram.com/portalwisata'),
('social_youtube', 'https://youtube.com/portalwisata'),
('debug_mode', '1'),
('show_sql_errors', '1');

-- =====================================================
-- INSERT DATA: SAMPLE SUBSCRIBERS
-- =====================================================
INSERT INTO `subscribers` (`email`, `name`, `status`) VALUES
('subscriber1@email.com', 'John Doe', 'active'),
('subscriber2@email.com', 'Jane Smith', 'active'),
('subscriber3@email.com', 'Test User', 'active');

-- =====================================================
-- DATABASE READY FOR HOSTING!
-- =====================================================
