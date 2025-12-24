-- =====================================================
-- DATABASE SCHEMA: Portal Wisata & Berita Kota
-- Purpose: Vulnerable Lab untuk praktik IT Security
-- =====================================================

-- Buat database
CREATE DATABASE IF NOT EXISTS portal_wisata_berita;
USE portal_wisata_berita;

-- =====================================================
-- TABEL USERS (untuk login dengan SQLi vulnerability)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'contributor') DEFAULT 'contributor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL CATEGORIES (kategori berita/wisata)
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type ENUM('berita', 'wisata') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL ARTICLES (berita dan destinasi wisata)
-- =====================================================
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    image VARCHAR(255),
    category_id INT,
    author_id INT,
    type ENUM('berita', 'wisata') NOT NULL,
    views INT DEFAULT 0,
    status ENUM('published', 'draft') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- TABEL COMMENTS (untuk Stored XSS vulnerability)
-- =====================================================
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT DUMMY DATA
-- =====================================================

-- Users (Password: password123 - stored as plain text for demo purposes)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', 'admin123', 'Administrator', 'admin@portal.com', 'admin'),
('kontributor1', 'pass123', 'Budi Santoso', 'budi@portal.com', 'contributor'),
('kontributor2', 'pass456', 'Siti Rahayu', 'siti@portal.com', 'contributor');

-- Categories
INSERT INTO categories (name, type, description) VALUES
('Politik', 'berita', 'Berita seputar politik dan pemerintahan'),
('Ekonomi', 'berita', 'Berita ekonomi dan bisnis'),
('Sosial', 'berita', 'Berita sosial dan kemasyarakatan'),
('Budaya', 'berita', 'Berita budaya dan tradisi'),
('Pantai', 'wisata', 'Destinasi wisata pantai'),
('Gunung', 'wisata', 'Destinasi wisata pegunungan'),
('Kuliner', 'wisata', 'Wisata kuliner dan makanan khas'),
('Sejarah', 'wisata', 'Wisata sejarah dan heritage');

-- Articles - Berita
INSERT INTO articles (title, slug, content, excerpt, image, category_id, author_id, type, views) VALUES
('Pembangunan Jalan Tol Baru Dimulai Tahun Ini', 'pembangunan-jalan-tol-baru', 
'<p>Pemerintah kota mengumumkan dimulainya proyek pembangunan jalan tol baru yang akan menghubungkan pusat kota dengan kawasan industri di pinggiran kota.</p>
<p>Proyek ini diharapkan dapat mengurangi kemacetan yang selama ini menjadi masalah utama di kota kita. Dengan investasi sebesar 2 triliun rupiah, jalan tol sepanjang 25 kilometer ini akan selesai dalam waktu 3 tahun.</p>
<p>Walikota menyatakan bahwa proyek ini akan menciptakan ribuan lapangan kerja baru bagi masyarakat setempat.</p>', 
'Pemerintah kota mengumumkan dimulainya proyek pembangunan jalan tol baru...', 
'tol.jpg', 1, 1, 'berita', 150),

('Festival Budaya Tahunan Akan Digelar Bulan Depan', 'festival-budaya-tahunan', 
'<p>Festival Budaya Tahunan yang menjadi agenda rutin setiap tahunnya akan kembali digelar bulan depan di alun-alun kota.</p>
<p>Festival ini akan menampilkan berbagai kesenian tradisional dari berbagai daerah, pameran kerajinan tangan, serta kuliner khas nusantara.</p>
<p>Panitia memperkirakan lebih dari 50.000 pengunjung akan hadir selama 5 hari penyelenggaraan festival.</p>', 
'Festival Budaya Tahunan akan kembali digelar bulan depan...', 
'festival.jpg', 4, 2, 'berita', 230),

('Harga Bahan Pokok Stabil Menjelang Akhir Tahun', 'harga-bahan-pokok-stabil', 
'<p>Dinas Perdagangan melaporkan bahwa harga bahan pokok di pasar tradisional tetap stabil menjelang akhir tahun.</p>
<p>Kepala Dinas menyatakan bahwa stok beras, gula, dan minyak goreng mencukupi untuk memenuhi kebutuhan masyarakat.</p>
<p>Operasi pasar akan tetap dilakukan untuk menjaga stabilitas harga.</p>', 
'Harga bahan pokok di pasar tradisional tetap stabil...', 
'pasar.jpg', 2, 1, 'berita', 89),

('Program Bantuan Sosial Diperluas untuk Warga Kurang Mampu', 'program-bantuan-sosial', 
'<p>Pemerintah kota memperluas program bantuan sosial untuk menjangkau lebih banyak warga kurang mampu.</p>
<p>Program ini meliputi bantuan langsung tunai, sembako, dan bantuan pendidikan untuk anak-anak dari keluarga prasejahtera.</p>
<p>Pendaftaran dapat dilakukan di kelurahan masing-masing dengan membawa dokumen identitas yang diperlukan.</p>', 
'Pemerintah kota memperluas program bantuan sosial...', 
'bansos.jpg', 3, 3, 'berita', 312);

-- Articles - Wisata
INSERT INTO articles (title, slug, content, excerpt, image, category_id, author_id, type, views) VALUES
('Pantai Indah: Surga Tersembunyi di Pesisir Selatan', 'pantai-indah-pesisir-selatan', 
'<p>Pantai Indah merupakan salah satu destinasi wisata tersembunyi yang terletak di pesisir selatan kota kita.</p>
<p>Dengan pasir putih yang lembut dan air laut yang jernih, pantai ini menawarkan pengalaman liburan yang menyegarkan. Pengunjung dapat menikmati berbagai aktivitas seperti berenang, snorkeling, atau sekadar bersantai menikmati sunset.</p>
<p><strong>Fasilitas:</strong> Area parkir luas, toilet umum, warung makan, penyewaan ban renang.</p>
<p><strong>Harga Tiket:</strong> Rp 15.000/orang</p>
<p><strong>Jam Operasional:</strong> 06.00 - 18.00 WIB</p>', 
'Pantai Indah merupakan destinasi wisata tersembunyi yang menawarkan keindahan alam...', 
'pantai.jpg', 5, 2, 'wisata', 520),

('Gunung Merapi: Pendakian Favorit Para Pecinta Alam', 'gunung-merapi-pendakian', 
'<p>Gunung Merapi menjadi destinasi favorit para pecinta alam dan pendaki gunung dari berbagai daerah.</p>
<p>Dengan ketinggian 2.968 meter, gunung ini menawarkan pemandangan sunrise yang spektakuler dari puncaknya. Jalur pendakian yang tersedia cukup menantang namun aman bagi pendaki pemula dengan pendamping berpengalaman.</p>
<p><strong>Tips Pendakian:</strong></p>
<ul>
<li>Mulai pendakian dari basecamp pukul 23.00 untuk sampai puncak saat sunrise</li>
<li>Bawa perlengkapan hangat karena suhu di puncak bisa mencapai 5°C</li>
<li>Wajib menggunakan jasa pemandu lokal</li>
</ul>', 
'Gunung Merapi menjadi destinasi favorit para pecinta alam...', 
'gunung.jpg', 6, 1, 'wisata', 445),

('Wisata Kuliner: 10 Makanan Khas yang Wajib Dicoba', 'wisata-kuliner-makanan-khas', 
'<p>Kota kita terkenal dengan berbagai makanan khas yang menggugah selera. Berikut 10 kuliner yang wajib Anda coba:</p>
<ol>
<li><strong>Nasi Gudeg</strong> - Makanan legendaris dengan cita rasa manis gurih</li>
<li><strong>Sate Klathak</strong> - Sate kambing dengan tusuk besi khas</li>
<li><strong>Bakpia</strong> - Kue khas dengan berbagai varian rasa</li>
<li><strong>Es Dawet</strong> - Minuman segar dengan santan dan gula merah</li>
<li><strong>Mie Ayam Pak Karso</strong> - Mie ayam legendaris sejak 1970</li>
</ol>
<p>Semua kuliner ini dapat Anda temukan di kawasan Malioboro dan sekitarnya.</p>', 
'Nikmati berbagai makanan khas yang menggugah selera di kota kita...', 
'kuliner.jpg', 7, 3, 'wisata', 678),

('Museum Sejarah: Menelusuri Jejak Peradaban Masa Lalu', 'museum-sejarah-peradaban', 
'<p>Museum Sejarah Kota menyimpan berbagai artefak dan koleksi bersejarah yang menceritakan perjalanan peradaban kota kita dari masa ke masa.</p>
<p>Dengan koleksi lebih dari 5.000 benda bersejarah, museum ini menjadi destinasi edukatif yang wajib dikunjungi terutama bagi pelajar dan mahasiswa.</p>
<p><strong>Informasi Kunjungan:</strong></p>
<ul>
<li>Tiket: Rp 10.000 (dewasa), Rp 5.000 (pelajar)</li>
<li>Jam buka: Selasa-Minggu, 08.00-16.00 WIB</li>
<li>Tersedia pemandu wisata berbahasa Indonesia dan Inggris</li>
</ul>', 
'Museum Sejarah menyimpan berbagai artefak bersejarah...', 
'museum.jpg', 8, 2, 'wisata', 234);

-- Comments (beberapa sudah ada untuk demo)
INSERT INTO comments (article_id, name, email, comment) VALUES
(1, 'Ahmad Wijaya', 'ahmad@email.com', 'Semoga pembangunan jalan tol ini bisa berjalan lancar dan segera selesai!'),
(1, 'Dewi Lestari', 'dewi@email.com', 'Apakah ada jalur alternatif saat pembangunan berlangsung?'),
(2, 'Rizki Pratama', 'rizki@email.com', 'Tahun lalu festival ini sangat meriah, semoga tahun ini lebih baik lagi!'),
(5, 'Maria Sari', 'maria@email.com', 'Pantai yang sangat indah! Recommended untuk liburan keluarga.'),
(6, 'Andi Susanto', 'andi@email.com', 'Sudah 3x naik ke Merapi, pemandangannya selalu memukau!'),
(7, 'Lina Kusuma', 'lina@email.com', 'Gudeg memang yang terbaik! Jangan lupa coba yang di Wijilan.');
