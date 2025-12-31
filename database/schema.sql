-- =====================================================
-- DATABASE SCHEMA: Portal Wisata & Berita Kota
-- Purpose: Vulnerable Lab untuk praktik IT Security
-- Enhanced Version with 9 Intentional Vulnerabilities
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
    password VARCHAR(255) NOT NULL,  -- Plain text for vuln demo
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    bio TEXT,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'editor', 'contributor') DEFAULT 'contributor',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL CATEGORIES (kategori berita/wisata)
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    type ENUM('berita', 'wisata') NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'bi-folder',
    color VARCHAR(20) DEFAULT '#1e3a5f',
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
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
    is_featured TINYINT(1) DEFAULT 0,
    is_breaking TINYINT(1) DEFAULT 0,
    status ENUM('published', 'draft', 'pending') DEFAULT 'published',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- TABEL TAGS (untuk tagging system)
-- =====================================================
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL ARTICLE_TAGS (many-to-many relationship)
-- =====================================================
CREATE TABLE IF NOT EXISTS article_tags (
    article_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- =====================================================
-- TABEL COMMENTS (untuk Stored XSS vulnerability)
-- =====================================================
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    comment TEXT NOT NULL,  -- No sanitization = Stored XSS
    is_approved TINYINT(1) DEFAULT 1,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- =====================================================
-- TABEL SUBSCRIBERS (untuk Newsletter - XSS vuln)
-- =====================================================
CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    name VARCHAR(100),  -- XSS vulnerability when displayed
    status ENUM('active', 'unsubscribed') DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL BREAKING_NEWS (Breaking news ticker)
-- =====================================================
CREATE TABLE IF NOT EXISTS breaking_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 0,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL BOOKMARKS (Save articles - session based)
-- =====================================================
CREATE TABLE IF NOT EXISTS bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    article_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);

-- =====================================================
-- TABEL VIEWS_LOG (untuk tracking views)
-- =====================================================
CREATE TABLE IF NOT EXISTS views_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer VARCHAR(255),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);

-- =====================================================
-- TABEL UPLOADS (untuk File Upload tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- TABEL SETTINGS (Site configuration)
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL ACTIVITY_LOG (untuk Information Disclosure)
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    details TEXT,  -- Contains sensitive info for disclosure
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- INSERT DUMMY DATA
-- =====================================================

-- Users (Password stored as plain text - INTENTIONAL VULNERABILITY)
INSERT INTO users (username, password, full_name, email, bio, role) VALUES
('admin', 'admin123', 'Administrator', 'admin@portal.com', 'System Administrator dengan akses penuh ke seluruh fitur portal.', 'admin'),
('editor', 'editor123', 'John Editor', 'editor@portal.com', 'Editor senior yang bertanggung jawab untuk review dan publikasi artikel.', 'editor'),
('budi', 'budi123', 'Budi Santoso', 'budi@portal.com', 'Kontributor aktif yang fokus pada berita politik dan ekonomi.', 'contributor'),
('siti', 'siti123', 'Siti Rahayu', 'siti@portal.com', 'Penulis perjalanan dan kuliner dengan pengalaman 5 tahun.', 'contributor'),
('ahmad', 'ahmad123', 'Ahmad Wijaya', 'ahmad@portal.com', 'Jurnalis muda yang passionate tentang teknologi dan startup.', 'contributor');

-- Categories dengan icon dan warna
INSERT INTO categories (name, slug, type, description, icon, color, sort_order) VALUES
-- Berita categories
('Politik', 'politik', 'berita', 'Berita seputar politik nasional dan internasional', 'bi-bank', '#dc2626', 1),
('Ekonomi', 'ekonomi', 'berita', 'Berita ekonomi, bisnis, dan keuangan', 'bi-graph-up-arrow', '#16a34a', 2),
('Teknologi', 'teknologi', 'berita', 'Berita teknologi, gadget, dan inovasi', 'bi-cpu', '#2563eb', 3),
('Olahraga', 'olahraga', 'berita', 'Berita olahraga nasional dan internasional', 'bi-trophy', '#ea580c', 4),
('Hiburan', 'hiburan', 'berita', 'Berita entertainment dan selebriti', 'bi-film', '#db2777', 5),
('Sosial', 'sosial', 'berita', 'Berita sosial dan kemasyarakatan', 'bi-people', '#7c3aed', 6),
-- Wisata categories
('Pantai', 'pantai', 'wisata', 'Destinasi wisata pantai dan laut', 'bi-water', '#0891b2', 7),
('Gunung', 'gunung', 'wisata', 'Destinasi wisata pegunungan dan alam', 'bi-mountains', '#059669', 8),
('Kuliner', 'kuliner', 'wisata', 'Wisata kuliner dan makanan khas daerah', 'bi-egg-fried', '#f59e0b', 9),
('Sejarah', 'sejarah', 'wisata', 'Wisata sejarah, museum, dan heritage', 'bi-building', '#6366f1', 10),
('Budaya', 'budaya', 'wisata', 'Wisata budaya dan tradisi nusantara', 'bi-mask', '#be185d', 11);

-- Tags
INSERT INTO tags (name, slug) VALUES
('Breaking News', 'breaking-news'),
('Viral', 'viral'),
('Trending', 'trending'),
('Eksklusif', 'eksklusif'),
('Wawancara', 'wawancara'),
('Opini', 'opini'),
('Review', 'review'),
('Tips', 'tips'),
('Hidden Gem', 'hidden-gem'),
('Family Friendly', 'family-friendly'),
('Budget', 'budget'),
('Luxury', 'luxury');

-- Articles - Berita dengan lebih banyak konten
INSERT INTO articles (title, slug, content, excerpt, image, category_id, author_id, type, views, is_featured, is_breaking, status, published_at) VALUES

-- BERITA POLITIK
('Pembangunan Jalan Tol Baru Dimulai Tahun Ini', 'pembangunan-jalan-tol-baru', 
'<p class="lead">Pemerintah kota mengumumkan dimulainya proyek pembangunan jalan tol baru yang akan menghubungkan pusat kota dengan kawasan industri di pinggiran kota.</p>

<p>Proyek infrastruktur ini diharapkan dapat secara signifikan mengurangi kemacetan yang selama ini menjadi masalah utama di kota kita. Dengan investasi sebesar <strong>Rp 2 triliun</strong>, jalan tol sepanjang 25 kilometer ini akan selesai dalam waktu 3 tahun.</p>

<h2>Dampak Ekonomi</h2>
<p>Walikota menyatakan bahwa proyek ini akan menciptakan ribuan lapangan kerja baru bagi masyarakat setempat. Selain itu, konektivitas yang lebih baik diharapkan dapat menarik investor untuk mengembangkan usaha di kawasan industri.</p>

<blockquote class="blockquote">
"Ini adalah investasi untuk masa depan kota kita. Dengan infrastruktur yang lebih baik, kita bisa menarik lebih banyak investasi dan menciptakan lapangan kerja." - Walikota
</blockquote>

<h2>Timeline Proyek</h2>
<ul>
<li><strong>2024:</strong> Pembebasan lahan dan persiapan</li>
<li><strong>2025:</strong> Konstruksi fase 1 (km 0-12)</li>
<li><strong>2026:</strong> Konstruksi fase 2 (km 12-25)</li>
<li><strong>2027:</strong> Peresmian dan operasional penuh</li>
</ul>',
'Pemerintah kota mengumumkan dimulainya proyek pembangunan jalan tol baru yang akan menghubungkan pusat kota dengan kawasan industri...', 
'tol.jpg', 1, 1, 'berita', 1520, 1, 0, 'published', NOW()),

('Pemilu 2024: Partisipasi Pemilih Meningkat', 'pemilu-2024-partisipasi-meningkat',
'<p class="lead">KPU mencatat tingkat partisipasi pemilih pada Pemilu 2024 mencapai rekor tertinggi dalam sejarah demokrasi Indonesia.</p>

<p>Berdasarkan data resmi yang dirilis hari ini, sebanyak <strong>82,5%</strong> pemilih terdaftar menggunakan hak suaranya pada pemilihan umum kemarin. Angka ini melampaui target KPU yang sebelumnya ditetapkan sebesar 77,5%.</p>

<h2>Faktor Peningkatan</h2>
<p>Beberapa faktor yang berkontribusi pada peningkatan partisipasi:</p>
<ol>
<li>Kemudahan akses TPS dengan penambahan 15.000 lokasi baru</li>
<li>Edukasi pemilih yang intensif melalui media sosial</li>
<li>Antusiasme generasi muda untuk berpartisipasi</li>
<li>Cuaca yang mendukung di sebagian besar wilayah</li>
</ol>',
'KPU mencatat tingkat partisipasi pemilih pada Pemilu 2024 mencapai rekor tertinggi dalam sejarah...', 
'pemilu.jpg', 1, 3, 'berita', 2340, 1, 1, 'published', NOW()),

-- BERITA EKONOMI
('Harga Bahan Pokok Stabil Menjelang Akhir Tahun', 'harga-bahan-pokok-stabil', 
'<p class="lead">Dinas Perdagangan melaporkan bahwa harga bahan pokok di pasar tradisional tetap stabil menjelang akhir tahun.</p>

<p>Kepala Dinas menyatakan bahwa stok beras, gula, dan minyak goreng mencukupi untuk memenuhi kebutuhan masyarakat hingga akhir tahun dan awal tahun depan.</p>

<h2>Daftar Harga Terkini</h2>
<table class="table">
<thead><tr><th>Komoditas</th><th>Harga/kg</th><th>Perubahan</th></tr></thead>
<tbody>
<tr><td>Beras Premium</td><td>Rp 14.000</td><td>Stabil</td></tr>
<tr><td>Gula Pasir</td><td>Rp 15.500</td><td>Turun 2%</td></tr>
<tr><td>Minyak Goreng</td><td>Rp 17.000</td><td>Stabil</td></tr>
<tr><td>Telur Ayam</td><td>Rp 28.000</td><td>Naik 5%</td></tr>
</tbody>
</table>

<p>Operasi pasar akan tetap dilakukan di 50 titik untuk menjaga stabilitas harga.</p>',
'Harga bahan pokok di pasar tradisional tetap stabil menjelang akhir tahun...', 
'pasar.jpg', 2, 1, 'berita', 890, 0, 0, 'published', NOW()),

('Startup Lokal Raih Pendanaan Seri A $10 Juta', 'startup-lokal-pendanaan-seri-a',
'<p class="lead">Startup teknologi finansial asal kota kita berhasil meraih pendanaan Seri A senilai $10 juta dari konsorsium investor global.</p>

<p>FinPay, startup yang fokus pada layanan pembayaran digital untuk UMKM, berhasil menarik perhatian investor ternama dari Silicon Valley dan Singapura.</p>

<h2>Rencana Ekspansi</h2>
<p>Dana yang diperoleh akan digunakan untuk:</p>
<ul>
<li>Ekspansi ke 10 kota baru di Indonesia</li>
<li>Pengembangan fitur pinjaman mikro</li>
<li>Rekrutmen 200 karyawan baru</li>
<li>Upgrade infrastruktur teknologi</li>
</ul>',
'Startup teknologi finansial asal kota kita berhasil meraih pendanaan Seri A senilai $10 juta...', 
'startup.jpg', 2, 5, 'berita', 1245, 1, 0, 'published', NOW()),

-- BERITA TEKNOLOGI
('Apple Luncurkan iPhone 16 dengan Fitur AI', 'apple-iphone-16-ai',
'<p class="lead">Apple resmi mengumumkan iPhone 16 series dengan fokus pada integrasi kecerdasan buatan (AI) yang revolusioner.</p>

<p>Peluncuran yang dilakukan di Apple Park, Cupertino, memperkenalkan empat model baru: iPhone 16, iPhone 16 Plus, iPhone 16 Pro, dan iPhone 16 Pro Max.</p>

<h2>Fitur Unggulan</h2>
<ul>
<li><strong>Apple Intelligence:</strong> Asisten AI on-device yang memahami konteks</li>
<li><strong>Camera Control:</strong> Tombol khusus untuk kontrol kamera</li>
<li><strong>A18 Pro Chip:</strong> Prosesor tercepat di smartphone</li>
<li><strong>Action Button:</strong> Tombol yang dapat dikustomisasi</li>
</ul>

<p>iPhone 16 akan tersedia di Indonesia mulai bulan depan dengan harga mulai dari Rp 18 juta.</p>',
'Apple resmi mengumumkan iPhone 16 series dengan fokus pada integrasi kecerdasan buatan...', 
'iphone.jpg', 3, 5, 'berita', 3450, 1, 1, 'published', NOW()),

-- BERITA OLAHRAGA
('Timnas Indonesia Lolos ke Piala Asia 2024', 'timnas-lolos-piala-asia',
'<p class="lead">Timnas Indonesia berhasil mengamankan tiket ke Piala Asia 2024 setelah kemenangan dramatis 3-2 atas Vietnam.</p>

<p>Pertandingan yang berlangsung di Stadion Utama Gelora Bung Karno menjadi saksi momen bersejarah sepak bola Indonesia.</p>

<h2>Jalannya Pertandingan</h2>
<p>Indonesia unggul 2-0 di babak pertama melalui gol Egy Maulana dan Asnawi. Vietnam membalas dengan dua gol di babak kedua, namun gol penentu dari Pratama Arhan di menit ke-89 memastikan kemenangan Garuda.</p>

<blockquote>"Ini adalah momen yang ditunggu seluruh rakyat Indonesia. Terima kasih atas dukungan luar biasa dari suporter!" - Shin Tae-yong</blockquote>',
'Timnas Indonesia berhasil mengamankan tiket ke Piala Asia 2024 setelah kemenangan dramatis...', 
'timnas.jpg', 4, 3, 'berita', 5670, 1, 1, 'published', NOW()),

-- WISATA
('Pantai Indah: Surga Tersembunyi di Pesisir Selatan', 'pantai-indah-pesisir-selatan', 
'<p class="lead">Pantai Indah merupakan salah satu destinasi wisata tersembunyi yang terletak di pesisir selatan kota kita, menawarkan keindahan alam yang masih sangat alami.</p>

<p>Dengan pasir putih yang lembut dan air laut yang jernih kebiruan, pantai ini menawarkan pengalaman liburan yang menyegarkan jauh dari keramaian kota. Pengunjung dapat menikmati berbagai aktivitas seperti berenang, snorkeling, atau sekadar bersantai menikmati sunset.</p>

<h2>Fasilitas</h2>
<ul>
<li>Area parkir luas (motor & mobil)</li>
<li>Toilet dan kamar mandi umum</li>
<li>Warung makan dengan seafood segar</li>
<li>Penyewaan ban renang dan pelampung</li>
<li>Gazebo untuk piknik keluarga</li>
<li>Spot foto instagramable</li>
</ul>

<h2>Informasi Kunjungan</h2>
<table class="table">
<tr><td><strong>Harga Tiket</strong></td><td>Rp 15.000/orang (weekday), Rp 20.000 (weekend)</td></tr>
<tr><td><strong>Jam Operasional</strong></td><td>06.00 - 18.00 WIB</td></tr>
<tr><td><strong>Waktu Terbaik</strong></td><td>Pagi hari atau saat sunset</td></tr>
<tr><td><strong>Jarak dari Kota</strong></td><td>±35 km (1 jam perjalanan)</td></tr>
</table>

<h2>Tips Berkunjung</h2>
<ol>
<li>Datang pagi untuk mendapat tempat parkir yang bagus</li>
<li>Bawa sunblock dan topi untuk perlindungan matahari</li>
<li>Bawa bekal atau nikmati seafood segar di warung sekitar</li>
<li>Jangan tinggalkan sampah, jaga kebersihan pantai</li>
</ol>',
'Pantai Indah merupakan destinasi wisata tersembunyi yang menawarkan keindahan alam yang masih alami...', 
'pantai.jpg', 7, 4, 'wisata', 4520, 1, 0, 'published', NOW()),

('Gunung Merapi: Pendakian Favorit Para Pecinta Alam', 'gunung-merapi-pendakian', 
'<p class="lead">Gunung Merapi menjadi destinasi favorit para pecinta alam dan pendaki gunung dari berbagai daerah di Indonesia.</p>

<p>Dengan ketinggian 2.968 meter di atas permukaan laut, gunung ini menawarkan pemandangan sunrise yang spektakuler dari puncaknya. Jalur pendakian yang tersedia cukup menantang namun tetap aman bagi pendaki pemula dengan pendampingan guide berpengalaman.</p>

<h2>Jalur Pendakian</h2>
<ul>
<li><strong>Jalur Selo:</strong> Paling populer, waktu tempuh ±4 jam</li>
<li><strong>Jalur New Selo:</strong> Lebih landai, cocok untuk pemula</li>
<li><strong>Jalur Babadan:</strong> Jalur alternatif yang lebih sepi</li>
</ul>

<h2>Tips Pendakian</h2>
<ol>
<li>Mulai pendakian dari basecamp pukul 23.00 untuk sampai puncak saat sunrise</li>
<li>Bawa perlengkapan hangat karena suhu di puncak bisa mencapai 5°C</li>
<li>Wajib menggunakan jasa pemandu lokal</li>
<li>Bawa air minimal 2 liter dan makanan ringan</li>
<li>Cek cuaca sebelum mendaki</li>
</ol>

<div class="alert alert-warning">
<strong>Peringatan:</strong> Pendakian ditutup saat status gunung di atas Level 2 (Waspada).
</div>',
'Gunung Merapi menjadi destinasi favorit para pecinta alam dan pendaki gunung...', 
'gunung.jpg', 8, 1, 'wisata', 3445, 1, 0, 'published', NOW()),

('Wisata Kuliner: 10 Makanan Khas yang Wajib Dicoba', 'wisata-kuliner-makanan-khas', 
'<p class="lead">Kota kita terkenal dengan berbagai makanan khas yang menggugah selera. Berikut 10 kuliner legendaris yang wajib Anda coba!</p>

<h2>1. Nasi Gudeg</h2>
<p>Makanan legendaris dengan cita rasa manis gurih dari nangka muda yang dimasak berjam-jam. Disajikan dengan ayam kampung, telur, dan sambal krecek.</p>

<h2>2. Sate Klathak</h2>
<p>Sate kambing khas dengan tusuk besi yang memberikan sensasi berbeda. Daging empuk dengan bumbu yang meresap sempurna.</p>

<h2>3. Bakpia Pathok</h2>
<p>Kue khas dengan berbagai varian rasa: kacang hijau original, coklat, keju, hingga matcha. Oleh-oleh wajib dari kota ini!</p>

<h2>4. Es Dawet Ayu</h2>
<p>Minuman segar dengan santan gurih dan gula merah yang legit. Sempurna untuk cuaca panas.</p>

<h2>5. Mie Ayam Pak Karso</h2>
<p>Mie ayam legendaris sejak 1970 dengan kuah kaldu yang kaya rasa. Antrean panjang adalah bukti kelezatannya!</p>

<p><em>Semua kuliner ini dapat Anda temukan di kawasan Malioboro dan sekitarnya.</em></p>',
'Nikmati berbagai makanan khas legendaris yang menggugah selera di kota kita...', 
'kuliner.jpg', 9, 4, 'wisata', 6780, 1, 0, 'published', NOW()),

('Museum Sejarah: Menelusuri Jejak Peradaban', 'museum-sejarah-peradaban', 
'<p class="lead">Museum Sejarah Kota menyimpan lebih dari 5.000 artefak bersejarah yang menceritakan perjalanan peradaban dari masa ke masa.</p>

<p>Dibangun pada era kolonial tahun 1897, bangunan museum ini sendiri sudah menjadi bagian dari sejarah. Arsitektur Indo-Eropa yang megah menjadi daya tarik tersendiri bagi pengunjung.</p>

<h2>Koleksi Utama</h2>
<ul>
<li><strong>Galeri Prasejarah:</strong> Fosil dan artefak zaman batu</li>
<li><strong>Galeri Hindu-Buddha:</strong> Arca dan prasasti kuno</li>
<li><strong>Galeri Kolonial:</strong> Dokumentasi masa penjajahan</li>
<li><strong>Galeri Kemerdekaan:</strong> Memorabilia perjuangan</li>
</ul>

<h2>Informasi Kunjungan</h2>
<table class="table">
<tr><td><strong>Tiket Dewasa</strong></td><td>Rp 10.000</td></tr>
<tr><td><strong>Tiket Pelajar</strong></td><td>Rp 5.000 (dengan kartu pelajar)</td></tr>
<tr><td><strong>Jam Buka</strong></td><td>Selasa-Minggu, 08.00-16.00 WIB</td></tr>
<tr><td><strong>Hari Libur</strong></td><td>Senin dan hari libur nasional</td></tr>
</table>

<p><em>Tersedia pemandu wisata berbahasa Indonesia dan Inggris dengan reservasi.</em></p>',
'Museum Sejarah menyimpan berbagai artefak bersejarah dari perjalanan peradaban...', 
'museum.jpg', 10, 4, 'wisata', 2340, 0, 0, 'published', NOW()),

-- Draft article for IDOR testing
('Rahasia Besar Perusahaan XYZ (DRAFT)', 'rahasia-perusahaan-xyz',
'<p>Ini adalah artikel draft yang berisi informasi sensitif tentang perusahaan XYZ...</p>
<p>Artikel ini seharusnya tidak bisa diakses oleh publik!</p>
<p>Password internal: secret123</p>
<p>API Key: sk-xxxx-yyyy-zzzz</p>',
'Artikel rahasia yang tidak boleh dipublikasikan...', 
'secret.jpg', 2, 1, 'berita', 5, 0, 0, 'draft', NULL),

-- Pending article for IDOR testing
('Investigasi Korupsi di Dinas ABC (PENDING)', 'investigasi-korupsi-dinas',
'<p>Hasil investigasi mendalam tentang dugaan korupsi...</p>
<p>Nama-nama terlibat: [REDACTED]</p>
<p>Bukti-bukti sedang dikumpulkan...</p>',
'Investigasi eksklusif yang masih menunggu verifikasi...', 
'investigasi.jpg', 1, 3, 'berita', 12, 0, 0, 'pending', NULL);

-- Article Tags
INSERT INTO article_tags (article_id, tag_id) VALUES
(1, 1), (1, 3),  -- Jalan tol: breaking, trending
(2, 1), (2, 4),  -- Pemilu: breaking, eksklusif
(3, 8),          -- Bahan pokok: tips
(4, 3), (4, 4),  -- Startup: trending, eksklusif
(5, 1), (5, 2),  -- iPhone: breaking, viral
(6, 1), (6, 2), (6, 3),  -- Timnas: breaking, viral, trending
(7, 9), (7, 10), -- Pantai: hidden gem, family friendly
(8, 8),          -- Gunung: tips
(9, 7), (9, 8),  -- Kuliner: review, tips
(10, 10);        -- Museum: family friendly

-- Breaking News
INSERT INTO breaking_news (title, link, is_active, priority) VALUES
('🔴 BREAKING: Timnas Indonesia Lolos ke Piala Asia 2024!', 'view.php?id=6', 1, 1),
('📱 Apple iPhone 16 Resmi Diluncurkan dengan Fitur AI', 'view.php?id=5', 1, 2),
('🗳️ Update Pemilu: Partisipasi Pemilih Capai 82.5%', 'view.php?id=2', 1, 3);

-- Comments (beberapa untuk demo Stored XSS)
INSERT INTO comments (article_id, name, email, comment) VALUES
(1, 'Ahmad Wijaya', 'ahmad@email.com', 'Semoga pembangunan jalan tol ini bisa berjalan lancar dan segera selesai! Sudah lama menunggu infrastruktur yang lebih baik.'),
(1, 'Dewi Lestari', 'dewi@email.com', 'Apakah ada jalur alternatif saat pembangunan berlangsung? Takutnya macet tambah parah.'),
(2, 'Rizki Pratama', 'rizki@email.com', 'Bangga dengan partisipasi masyarakat! Ini bukti demokrasi di Indonesia semakin matang.'),
(6, 'Supporter Garuda', 'garuda@email.com', 'MERDEKA!!! Akhirnya timnas kita lolos! 🇮🇩🔥'),
(6, 'Football Lover', 'footbal@email.com', 'Shin Tae-yong memang pelatih terbaik! Strategi nya jitu!'),
(7, 'Maria Sari', 'maria@email.com', 'Pantai yang sangat indah! Recommended untuk liburan keluarga. Air nya jernih banget! 🏖️'),
(7, 'Travel Enthusiast', 'traveler@email.com', 'Hidden gem! Belum terlalu ramai, semoga tetap terjaga kebersihannya.'),
(8, 'Andi Susanto', 'andi@email.com', 'Sudah 3x naik ke Merapi, pemandangannya selalu memukau! Sunrise terbaik!'),
(9, 'Lina Kusuma', 'lina@email.com', 'Gudeg Wijilan memang yang terbaik! Wajib coba kalau ke Jogja!'),
(9, 'Foodie ID', 'foodie@email.com', 'Sate klathak nya bikin nagih! Dagingnya empuk dan bumbunya meresap.');

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Portal Wisata & Berita Kota'),
('site_tagline', 'Sumber Informasi Terpercaya'),
('site_email', 'info@portal.com'),
('site_phone', '+62 123 456 7890'),
('site_address', 'Jl. Merdeka No. 123, Kota'),
('footer_copyright', '© 2024 Portal Wisata & Berita. All rights reserved.'),
('social_facebook', 'https://facebook.com/portalberita'),
('social_twitter', 'https://twitter.com/portalberita'),
('social_instagram', 'https://instagram.com/portalberita'),
('social_youtube', 'https://youtube.com/portalberita'),
('articles_per_page', '12'),
('enable_comments', '1'),
('enable_registration', '0'),
('debug_mode', '1'),  -- INTENTIONAL: Information Disclosure
('show_sql_errors', '1');  -- INTENTIONAL: Information Disclosure

-- Subscribers
INSERT INTO subscribers (email, name, status) VALUES
('user1@email.com', 'User Pertama', 'active'),
('user2@email.com', 'User Kedua', 'active'),
('user3@email.com', 'User Ketiga', 'unsubscribed');

-- Activity Log (for Information Disclosure)
INSERT INTO activity_log (user_id, action, details, ip_address) VALUES
(1, 'login', 'Admin login successful. Password: admin123', '192.168.1.100'),
(2, 'login', 'Editor login from new device', '192.168.1.101'),
(1, 'settings_update', 'Changed database password to: db_secret_pass', '192.168.1.100'),
(1, 'backup_created', 'Backup saved to /backup/db_20241225.sql', '192.168.1.100');
