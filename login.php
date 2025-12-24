<?php
/**
 * HALAMAN LOGIN
 * Portal Wisata & Berita Kota
 */

require_once 'config.php';

$error = '';
$success = '';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($username) && !empty($password)) {
        // Query login
        $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";

        try {
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                $success = "Login berhasil! Selamat datang, " . $user['full_name'];

                // Redirect setelah 2 detik
                header("Refresh: 2; URL=index.php");
            } else {
                $error = "Username atau password salah!";
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    } else {
        $error = "Username dan password wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Login Kontributor - <?php echo SITE_NAME; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-dark: #152a45;
            --secondary: #047857;
            --accent: #0ea5e9;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border-color: #e2e8f0;
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            min-height: 100dvh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            overflow-x: hidden;
        }

        /* Mobile First - Form centered */
        .login-container {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
        }

        /* Left Panel - Hidden on mobile */
        .login-illustration {
            display: none;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .login-illustration::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            animation: pulse 15s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .illustration-content {
            position: relative;
            z-index: 10;
            text-align: center;
            color: #fff;
            max-width: 380px;
        }

        .illustration-icon {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .illustration-icon i {
            font-size: 3rem;
            color: #fff;
        }

        .illustration-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .illustration-text {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.7;
        }

        .illustration-features {
            margin-top: 2rem;
            display: flex;
            gap: 1.5rem;
            justify-content: center;
        }

        .feature-item {
            text-align: center;
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
        }

        .feature-icon i {
            font-size: 1.25rem;
        }

        .feature-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Right Panel - Form - Mobile First */
        .login-form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            background: var(--bg-white);
        }

        .login-form-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary);
            gap: 0.75rem;
        }

        .form-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.375rem;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 0.9375rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.875rem;
        }

        .form-control {
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-light);
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.1);
            background: var(--bg-white);
            outline: none;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper .form-control {
            padding-left: 2.75rem;
        }

        .input-icon-wrapper .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.125rem;
        }

        .form-check {
            margin-bottom: 1.25rem;
        }

        .form-check-input {
            width: 1.125rem;
            height: 1.125rem;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            font-size: 0.875rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--text-muted);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .divider span {
            padding: 0 1rem;
            font-size: 0.8125rem;
        }

        .demo-credentials {
            background: linear-gradient(135deg, rgba(4, 120, 87, 0.1), rgba(14, 165, 233, 0.1));
            border: 1px solid rgba(4, 120, 87, 0.2);
            border-radius: 10px;
            padding: 1rem;
        }

        .demo-credentials h6 {
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .demo-credentials p {
            font-size: 0.8125rem;
            margin-bottom: 0.25rem;
        }

        .demo-credentials p:last-child {
            margin-bottom: 0;
        }

        .demo-credentials code {
            background: var(--bg-white);
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-size: 0.8125rem;
            color: var(--text-dark);
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background: rgba(4, 120, 87, 0.1);
            color: var(--secondary);
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
        }

        .alert i {
            font-size: 1.125rem;
            flex-shrink: 0;
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */

        /* Small devices (landscape phones, 576px and up) */
        @media (min-width: 576px) {
            .login-form-panel {
                padding: 2rem;
            }

            .form-header h1 {
                font-size: 2rem;
            }
        }

        /* Large devices (desktops, 992px and up) */
        @media (min-width: 992px) {
            .login-container {
                flex-direction: row;
            }

            .login-illustration {
                display: flex;
                flex: 1;
            }

            .login-form-panel {
                flex: 1;
                padding: 3rem;
            }

            .login-form-wrapper {
                max-width: 420px;
            }

            .form-header {
                text-align: left;
            }

            .illustration-icon {
                width: 150px;
                height: 150px;
            }

            .illustration-icon i {
                font-size: 4rem;
            }

            .illustration-title {
                font-size: 2.25rem;
            }

            .illustration-text {
                font-size: 1rem;
            }

            .feature-icon {
                width: 50px;
                height: 50px;
            }

            .feature-icon i {
                font-size: 1.5rem;
            }

            .feature-label {
                font-size: 0.8125rem;
            }
        }

        /* X-Large devices (large desktops, 1200px and up) */
        @media (min-width: 1200px) {
            .illustration-icon {
                width: 180px;
                height: 180px;
            }

            .illustration-icon i {
                font-size: 5rem;
            }

            .illustration-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <!-- Left Panel - Illustration (Hidden on mobile) -->
        <div class="login-illustration">
            <div class="illustration-content">
                <div class="illustration-icon">
                    <i class="bi bi-globe-asia-australia"></i>
                </div>
                <h2 class="illustration-title">Portal Wisata & Berita</h2>
                <p class="illustration-text">
                    Masuk sebagai kontributor untuk mengelola konten berita dan destinasi wisata. Bagikan informasi
                    terbaik untuk masyarakat.
                </p>
                <div class="illustration-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <span class="feature-label">Tulis Artikel</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <span class="feature-label">Statistik</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <span class="feature-label">Komentar</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="login-form-panel">
            <div class="login-form-wrapper">
                <a href="index.php" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    Kembali ke Beranda
                </a>

                <div class="form-header">
                    <h1>Selamat Datang</h1>
                    <p>Masuk ke akun kontributor Anda</p>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" class="form-control" name="username" placeholder="Masukkan username"
                                required autocomplete="off">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" class="form-control" name="password" placeholder="Masukkan password"
                                required>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label" for="remember">
                            Ingat saya di perangkat ini
                        </label>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Masuk
                    </button>
                </form>

                <div class="divider">
                    <span>Demo Akun</span>
                </div>

                <div class="demo-credentials">
                    <h6><i class="bi bi-info-circle"></i> Kredensial Demo</h6>
                    <p>
                        <strong>Admin:</strong> <code>admin</code> / <code>admin123</code>
                    </p>
                    <p>
                        <strong>Editor:</strong> <code>editor</code> / <code>editor123</code>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>