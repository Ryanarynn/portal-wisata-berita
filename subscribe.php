<?php
/**
 * =====================================================
 * SUBSCRIBE.PHP - Newsletter Subscription Handler
 * Portal Wisata & Berita Kota
 * =====================================================
 * 
 * VULNERABILITIES:
 * - XSS: Name displayed without sanitization
 * - CSRF: No token validation
 */

require_once 'config.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';
$subscriberName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABILITY: No CSRF token validation
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';

    // Store name for display (XSS vulnerability)
    $subscriberName = $name;

    if (!empty($email)) {
        // Check if already subscribed
        $checkQuery = "SELECT id FROM subscribers WHERE email = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Email sudah terdaftar sebelumnya!";
            $messageType = "warning";
        } else {
            // VULNERABILITY: Name stored without sanitization (for Stored XSS when displayed)
            $insertQuery = "INSERT INTO subscribers (email, name, status) VALUES (?, ?, 'active')";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ss", $email, $name);

            if ($stmt->execute()) {
                $message = "Terima kasih telah berlangganan, " . $name . "!";
                $messageType = "success";

                // Log activity with sensitive info (Information Disclosure)
                logActivity($conn, 'newsletter_subscribe', "New subscriber: $email, Name: $name");
            } else {
                $message = "Gagal mendaftarkan email. Silakan coba lagi.";
                $messageType = "danger";
            }
        }
        $stmt->close();
    } else {
        $message = "Email wajib diisi!";
        $messageType = "danger";
    }
}

// Get recent subscribers for display (for Stored XSS demo)
$recentSubscribers = [];
$query = "SELECT name, subscribed_at FROM subscribers WHERE status = 'active' ORDER BY subscribed_at DESC LIMIT 5";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $recentSubscribers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berlangganan Newsletter - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="bi bi-envelope-paper me-2"></i>Berlangganan Newsletter</h1>
            <p>Dapatkan berita dan informasi wisata terbaru</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4 p-md-5">

                            <?php if (!empty($message)): ?>
                                <div class="alert alert-<?php echo $messageType; ?> d-flex align-items-center">
                                    <i
                                        class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>-fill me-2"></i>
                                    <!-- VULNERABILITY: XSS - Message contains unsanitized name -->
                                    <span><?php echo $message; ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="text-center mb-4">
                                <div class="subscribe-icon">
                                    <i class="bi bi-envelope-heart"></i>
                                </div>
                                <h2 class="mt-3">Tetap Update!</h2>
                                <p class="text-muted">Masukkan email Anda untuk menerima artikel terbaru langsung di
                                    inbox.</p>
                            </div>

                            <!-- VULNERABILITY: Form tanpa CSRF token -->
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Nama Anda</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" name="name"
                                            placeholder="Masukkan nama Anda"
                                            value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>">
                                        <!-- VULNERABILITY: Value tidak di-escape = Reflected XSS -->
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" name="email"
                                            placeholder="email@anda.com" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-send me-2"></i>Berlangganan Sekarang
                                </button>
                            </form>

                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Kami tidak akan membagikan email Anda kepada pihak lain.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Subscribers (for Stored XSS demo) -->
                    <?php if (!empty($recentSubscribers)): ?>
                        <div class="card mt-4 border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-people me-2"></i>Subscriber Terbaru</h6>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($recentSubscribers as $sub): ?>
                                        <li class="d-flex align-items-center py-2 border-bottom">
                                            <div class="avatar-sm me-2"
                                                style="background-color: <?php echo getAvatarColor($sub['name']); ?>">
                                                <?php echo getInitials($sub['name']); ?>
                                            </div>
                                            <!-- VULNERABILITY: Stored XSS - Name displayed without sanitization -->
                                            <span><?php echo $sub['name']; ?></span>
                                            <small
                                                class="text-muted ms-auto"><?php echo timeAgo($sub['subscribed_at']); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>