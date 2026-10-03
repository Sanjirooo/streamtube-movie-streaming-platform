<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

$user = querySingle("SELECT u.*, mp.name as membership_name, mp.slug as membership_slug, mp.benefits as membership_benefits
    FROM users u
    LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id
    WHERE u.id = ?", [$userId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageId = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $paymentAccountName = trim($_POST['payment_account_name'] ?? '');
    $paymentReference = trim($_POST['payment_reference'] ?? '');
    $paymentNote = trim($_POST['payment_note'] ?? '');

    $package = querySingle("SELECT * FROM membership_packages WHERE id = ? AND is_active = 1", [$packageId]);

    if (!$package) {
        $error = 'Paket membership tidak ditemukan.';
    } elseif (empty($paymentMethod) || empty($paymentAccountName) || empty($paymentReference)) {
        $error = 'Metode pembayaran, nama pengirim, dan nomor referensi wajib diisi.';
    } else {
        $pending = querySingle("SELECT id FROM membership_transactions WHERE user_id = ? AND status = 'pending'", [$userId]);

        if ($pending) {
            $error = 'Kamu masih punya transaksi membership yang menunggu verifikasi admin.';
        } else {
            $result = execute(
                "INSERT INTO membership_transactions (user_id, package_id, amount, payment_method, payment_account_name, payment_reference, payment_note, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')",
                [$userId, $packageId, (int)$package['price'], $paymentMethod, $paymentAccountName, $paymentReference, $paymentNote]
            );

            if ($result['success']) {
                $message = 'Pembelian paket ' . htmlspecialchars($package['name']) . ' berhasil dikirim. Tunggu admin memverifikasi pembayaran kamu.';
            } else {
                $error = 'Transaksi gagal dibuat. Silakan coba lagi.';
            }
        }
    }
}

$packages = queryAll("SELECT * FROM membership_packages WHERE is_active = 1 ORDER BY sort_order ASC, price ASC");
$transactions = queryAll("SELECT mt.*, mp.name as package_name, mp.slug as package_slug
    FROM membership_transactions mt
    JOIN membership_packages mp ON mt.package_id = mp.id
    WHERE mt.user_id = ?
    ORDER BY mt.created_at DESC
    LIMIT 10", [$userId]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership - Streamtube</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar scrolled">
        <div class="nav-container">
            <a href="index.php" class="logo"><i class="fas fa-play"></i> Streamtube</a>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php#hero" class="nav-link">Home</a></li>
                <li><a href="index.php#movies" class="nav-link">Movies</a></li>
                <li><a href="index.php#series" class="nav-link">Series</a></li>
                <li><a href="index.php#trending" class="nav-link">Trending</a></li>
                <li><a href="membership.php" class="nav-link active">Membership</a></li>
                <li><a href="mylist.php" class="nav-link">My List</a></li>
            </ul>
            <div class="nav-actions">
                <div class="user-menu">
                    <button class="user-btn" onclick="toggleUserMenu()">
                        <img src="<?= getUserAvatar($user) ?>" alt="Avatar">
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-info">
                            <img src="<?= getUserAvatar($user) ?>" alt="Avatar">
                            <span><?= getUserDisplayName($user) ?></span>
                        </div>
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="membership.php"><i class="fas fa-crown"></i> Membership</a>
                        <a href="history.php"><i class="fas fa-history"></i> History</a>
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="membership-container">
        <div class="membership-hero-box">
            <div>
                <p class="eyebrow"><i class="fas fa-crown"></i> Streamtube Membership</p>
                <h1>Upgrade akun kamu</h1>
                <p>Pilih paket membership, kirim data pembayaran, lalu admin akan memverifikasi transaksi. Setelah disetujui, status membership akan muncul di profil kamu.</p>
            </div>
            <div class="current-membership-card">
                <span>Status Akun</span>
                <h3><?= getMembershipName($user) ?></h3>
                <?= getMembershipBadge($user) ?>
                <?php if (hasActiveMembership($user)): ?>
                    <p>Aktif sampai <?= $user['membership_expired_at'] ? date('d M Y', strtotime($user['membership_expired_at'])) : '-' ?></p>
                <?php else: ?>
                    <p>User biasa, belum memiliki membership aktif.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <h2 class="membership-section-title"><i class="fas fa-layer-group"></i> Pilih Paket Membership</h2>
        <div class="membership-grid">
            <?php foreach ($packages as $package): ?>
                <div class="membership-card membership-card-<?= htmlspecialchars($package['slug']) ?>">
                    <div class="membership-card-head">
                        <span class="membership-badge membership-<?= htmlspecialchars($package['slug']) ?>"><?= htmlspecialchars($package['name']) ?></span>
                        <h3>Rp<?= number_format($package['price'], 0, ',', '.') ?></h3>
                        <p><?= (int)$package['duration_days'] ?> hari</p>
                    </div>
                    <ul class="benefit-list">
                        <?php foreach (preg_split('/\r\n|\r|\n/', $package['benefits']) as $benefit): ?>
                            <?php if (trim($benefit) !== ''): ?>
                                <li><i class="fas fa-check"></i> <?= htmlspecialchars(trim($benefit)) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>

                    <form method="POST" class="membership-form">
                        <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                        <label>Metode Pembayaran</label>
                        <select name="payment_method" required>
                            <option value="">Pilih metode</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="E-Wallet">E-Wallet</option>
                            <option value="QRIS">QRIS</option>
                        </select>

                        <label>Nama Pengirim</label>
                        <input type="text" name="payment_account_name" placeholder="Contoh: Budi Santoso" required>

                        <label>No. Referensi / Bukti Bayar</label>
                        <input type="text" name="payment_reference" placeholder="Contoh: TRX-2026-0001" required>

                        <label>Catatan</label>
                        <textarea name="payment_note" placeholder="Opsional, contoh: transfer dari BCA jam 13.00"></textarea>

                        <button type="submit"><i class="fas fa-paper-plane"></i> Beli Paket Ini</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="membership-section-title"><i class="fas fa-receipt"></i> Riwayat Transaksi Membership</h2>
        <div class="data-table membership-history-table">
            <table>
                <thead>
                    <tr><th>Paket</th><th>Nominal</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th><th>Catatan Admin</th></tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $trx): ?>
                            <tr>
                                <td><span class="membership-badge membership-<?= htmlspecialchars($trx['package_slug']) ?>"><?= htmlspecialchars($trx['package_name']) ?></span></td>
                                <td>Rp<?= number_format($trx['amount'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($trx['payment_method']) ?><br><small><?= htmlspecialchars($trx['payment_reference']) ?></small></td>
                                <td><span class="status-pill status-<?= htmlspecialchars($trx['status']) ?>"><?= ucfirst($trx['status']) ?></span></td>
                                <td><?= date('d M Y, H:i', strtotime($trx['created_at'])) ?></td>
                                <td><?= htmlspecialchars($trx['admin_note'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 30px;">Belum ada transaksi membership.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand"><a href="index.php" class="logo"><i class="fas fa-play"></i> Streamtube</a><p>Your favorite streaming platform.</p></div>
            <div class="footer-links"><h4>Navigation</h4><ul><li><a href="index.php#hero">Home</a></li><li><a href="index.php#movies">Movies</a></li><li><a href="index.php#series">Series</a></li><li><a href="index.php#trending">Trending</a></li></ul></div>
            <div class="footer-links"><h4>Account</h4><ul><li><a href="profile.php">Profile</a></li><li><a href="membership.php">Membership</a></li><li><a href="history.php">History</a></li><li><a href="mylist.php">My List</a></li><li><a href="logout.php">Logout</a></li></ul></div>
            <div class="footer-social"><h4>Follow Us</h4><div class="social-icons"><a href="#"><i class="fab fa-facebook"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-youtube"></i></a></div></div>
        </div>
        <div class="footer-bottom"><p>&copy; 2024 Streamtube. All rights reserved.</p></div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
