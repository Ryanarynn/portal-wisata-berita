# Portal Wisata & Berita Kota

Website Portal Berita & Destinasi Wisata berbasis PHP Native dengan Bootstrap 5.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)

---

## Deskripsi

Portal Wisata & Berita Kota adalah website portal informasi yang menyajikan berita terkini dan destinasi wisata menarik di berbagai kota di Indonesia.

### Fitur Utama

- 📰 **Berita Terkini** - Informasi berita terbaru dari berbagai kategori
- 🏝️ **Destinasi Wisata** - Eksplorasi tempat wisata menarik
- 🔍 **Pencarian** - Fitur pencarian artikel dan wisata
- 💬 **Komentar** - Interaksi dengan pembaca melalui komentar
- 📱 **Responsive** - Tampilan optimal di semua perangkat
- 👤 **Admin Panel** - Manajemen konten yang mudah

---

## Instalasi

### Prasyarat

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10+
- Web Server (Apache/Nginx/Laragon/XAMPP)

### Langkah Instalasi

**1. Clone repository**

```bash
git clone https://github.com/username/portal-wisata-berita.git
```

**2. Pindah ke direktori project**

```bash
cd portal-wisata-berita
```

**3. Buat database MySQL**

```sql
CREATE DATABASE portal_wisata_berita;
```

**4. Import schema database**

```bash
mysql -u root -p portal_wisata_berita < database/schema.sql
```

**5. Konfigurasi database**

Salin file `config.sample.php` menjadi `config.php` dan sesuaikan kredensial:

```bash
cp config.sample.php config.php
```

Edit `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'portal_wisata_berita');
```

**6. Akses website**

```
http://localhost/portal-wisata-berita/
```

---

## Struktur Project

```
portal-wisata-berita/
├── admin/                 # Panel administrasi
│   ├── index.php         # Dashboard admin
│   ├── articles.php      # Manajemen artikel
│   ├── categories.php    # Manajemen kategori
│   └── ...
├── assets/               # Asset statis (CSS, JS, images)
├── database/             # Schema database
│   └── schema.sql
├── includes/             # File include PHP
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── functions.php
├── uploads/              # Direktori upload gambar
├── index.php             # Halaman utama
├── berita.php            # Halaman berita
├── wisata.php            # Halaman wisata
├── search.php            # Halaman pencarian
├── view.php              # Detail artikel
├── login.php             # Halaman login
├── config.sample.php     # Template konfigurasi
└── README.md             # Dokumentasi
```

---

## Halaman Tersedia

| Halaman | File | Deskripsi |
|---------|------|-----------|
| Beranda | `index.php` | Halaman utama dengan hero section & artikel terbaru |
| Berita | `berita.php` | Daftar semua artikel berita |
| Wisata | `wisata.php` | Daftar destinasi wisata |
| Pencarian | `search.php` | Pencarian artikel |
| Detail | `view.php` | Detail artikel dengan komentar |
| Kategori | `category.php` | Artikel berdasarkan kategori |
| Tag | `tag.php` | Artikel berdasarkan tag |
| Penulis | `author.php` | Artikel berdasarkan penulis |
| Trending | `trending.php` | Artikel populer |
| Bookmark | `bookmark.php` | Artikel tersimpan |
| About | `about.php` | Halaman tentang kami |

---

## Admin Panel

Admin panel tersedia di `/admin/` dengan fitur:

- Dashboard statistik
- Manajemen artikel (CRUD)
- Manajemen kategori
- Moderasi komentar
- Manajemen subscriber
- Upload media
- Pengaturan website
- Activity logs

---

## Teknologi

- **Backend:** PHP Native dengan MySQLi
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework CSS:** Bootstrap 5.3
- **Icons:** Font Awesome 6
- **Database:** MySQL / MariaDB

---

## Lisensi

```
MIT License - Copyright (c) 2024

Proyek ini dilisensikan di bawah MIT License.
Silakan gunakan dan modifikasi sesuai kebutuhan.
```

---

**Made with ❤️ in Indonesia**