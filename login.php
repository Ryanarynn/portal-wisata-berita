<?php
/**
 * =====================================================
 * LOGIN.PHP - User Login Page
 * Portal Wisata & Berita Kota
 * =====================================================
 */

require_once 'config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($username) && !empty($password)) {
        // VULNERABLE: SQL Injection
        $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";

        try {
            $result = @$conn->query($query);

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin' || $user['role'] === 'editor') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    } else {
        $error = 'Mohon isi username dan password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-dark: #152a45;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .login-brand {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: #fff;
            text-align: center;
        }

        .brand-logo {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 2rem;
            font-weight: 400;
            font-style: bold;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .brand-description {
            color: rgba(255, 255, 255, 0.8);
            max-width: 300px;
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }

        .brand-features {
            display: flex;
            gap: 2rem;
        }

        .brand-feature {
            text-align: center;
        }

        .brand-feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.25rem;
        }

        .brand-feature-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .login-form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            background: #fff;
        }

        .login-form-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 1.5rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .login-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 2rem;
            font-weight: 600;
            font-style: bold;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .form-control {
            padding: 0.875rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9375rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.1);
        }

        .input-group-text {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-right: none;
            color: #64748b;
        }

        .input-group .form-control {
            border-left: none;
        }

        .form-check {
            margin: 1rem 0 1.5rem;
        }

        .form-check-label {
            font-size: 0.875rem;
            color: #64748b;
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            color: #fff;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(30, 58, 95, 0.3);
            color: #fff;
        }

        @media (max-width: 992px) {
            .login-brand {
                display: none;
            }

            .login-form-panel {
                padding: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <!-- Left Panel - Branding -->
        <div class="login-brand">
            <div class="brand-logo">
                <i class="bi bi-globe-asia-australia"></i>
            </div>
            <h1 class="brand-title">Portal Wisata &<br>Berita</h1>
            <p class="brand-description">
                Masuk sebagai kontributor untuk mengelola konten berita dan destinasi wisata. Bagikan informasi terbaik
                untuk masyarakat.
            </p>
            <div class="brand-features">
                <div class="brand-feature">
                    <div class="brand-feature-icon"><i class="bi bi-pencil-square"></i></div>
                    <div class="brand-feature-label">Tulis Artikel</div>
                </div>
                <div class="brand-feature">
                    <div class="brand-feature-icon"><i class="bi bi-graph-up"></i></div>
                    <div class="brand-feature-label">Statistik</div>
                </div>
                <div class="brand-feature">
                    <div class="brand-feature-icon"><i class="bi bi-chat-dots"></i></div>
                    <div class="brand-feature-label">Komentar</div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="login-form-panel">
            <div class="login-form-container">
                <a href="index.php" class="back-link">
                    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                </a>

                <h2 class="login-title">Selamat Datang</h2>
                <p class="login-subtitle">Masuk ke akun kontributor Anda</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Masukkan username"
                                required autofocus
                                value="<?php echo isset($_POST['username']) ? safeOutput($_POST['username']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
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

                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>