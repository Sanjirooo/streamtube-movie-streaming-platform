<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$adminName = $_SESSION['admin_name'];
$adminId = (int)$_SESSION['admin_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
    $action = $_POST['action'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    $transaction = querySingle("SELECT mt.*, mp.duration_days, mp.name as package_name
        FROM membership_transactions mt
        JOIN membership_packages mp ON mt.package_id = mp.id
        WHERE mt.id = ?", [$transactionId]);

    if (!$transaction) {
        $error = 'Transaksi tidak ditemukan.';
    } elseif ($transaction['status'] !== 'pending') {
        $error = 'Transaksi ini sudah diverifikasi sebelumnya.';
    } elseif ($action === 'approve') {
        $approve = execute("UPDATE membership_transactions
            SET status = 'approved', admin_note = ?, verified_by = ?, verified_at = NOW()
            WHERE id = ?", [$adminNote, $adminId, $transactionId]);

        if ($approve['success']) {
            execute("UPDATE users
                SET membership_package_id = ?, membership_status = 'active', membership_started_at = NOW(), membership_expired_at = DATE_ADD(NOW(), INTERVAL ? DAY)
                WHERE id = ?", [(int)$transaction['package_id'], (int)$transaction['duration_days'], (int)$transaction['user_id']]);
            $message = 'Transaksi disetujui. Membership user sekarang aktif.';
        } else {
            $error = 'Gagal menyetujui transaksi.';
        }
    } elseif ($action === 'reject') {
        if ($adminNote === '') {
            $adminNote = 'Pembayaran belum valid.';
        }

        $reject = execute("UPDATE membership_transactions
            SET status = 'rejected', admin_note = ?, verified_by = ?, verified_at = NOW()
            WHERE id = ?", [$adminNote, $adminId, $transactionId]);

        if ($reject['success']) {
            $message = 'Transaksi ditolak.';
        } else {
            $error = 'Gagal menolak transaksi.';
        }
    } else {
        $error = 'Aksi tidak valid.';
    }
}

$pendingMembershipCount = querySingle("SELECT COUNT(*) as count FROM membership_transactions WHERE status = 'pending'");
$pendingTransactions = queryAll("SELECT mt.*, u.name as user_name, u.email, mp.name as package_name, mp.slug as package_slug, mp.benefits
    FROM membership_transactions mt
    JOIN users u ON mt.user_id = u.id
    JOIN membership_packages mp ON mt.package_id = mp.id
    WHERE mt.status = 'pending'
    ORDER BY mt.created_at ASC");

$allTransactions = queryAll("SELECT mt.*, u.name as user_name, u.email, mp.name as package_name, mp.slug as package_slug, verifier.name as verifier_name
    FROM membership_transactions mt
    JOIN users u ON mt.user_id = u.id
    JOIN membership_packages mp ON mt.package_id = mp.id
    LEFT JOIN users verifier ON mt.verified_by = verifier.id
    ORDER BY mt.created_at DESC
    LIMIT 50");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Membership - Streamtube Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo"><i class="fas fa-play"></i> <span>Streamtube</span></a>
            <ul class="admin-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="movies.php"><i class="fas fa-film"></i> <span>Movies</span></a></li>
                <li><a href="hero.php"><i class="fas fa-images"></i> <span>Hero Slider</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                <li><a href="comments.php"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
                <li><a href="membership_verification.php" class="active"><i class="fas fa-crown"></i> <span>Verifikasi Membership</span><?php if (($pendingMembershipCount['count'] ?? 0) > 0): ?><b class="admin-menu-badge"><?= $pendingMembershipCount['count'] ?></b><?php endif; ?></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1><i class="fas fa-crown"></i> Verifikasi Membership</h1>
                    <p style="color: var(--text-secondary);">Cek pembayaran user, lalu approve atau reject transaksi membership.</p>
                </div>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="admin-stats">
                <div class="stat-card"><i class="fas fa-hourglass-half"></i><div class="stat-info"><h3><?= count($pendingTransactions) ?></h3><p>Pending Verification</p></div></div>
                <div class="stat-card"><i class="fas fa-receipt"></i><div class="stat-info"><h3><?= count($allTransactions) ?></h3><p>Recent Transactions</p></div></div>
            </div>

            <h2 style="margin-bottom: 20px;"><i class="fas fa-clock"></i> Transaksi Menunggu Verifikasi</h2>
            <?php if (count($pendingTransactions) > 0): ?>
                <div class="verification-grid">
                    <?php foreach ($pendingTransactions as $trx): ?>
                        <div class="verification-card">
                            <div class="verification-card-head">
                                <div>
                                    <h3><?= htmlspecialchars($trx['user_name']) ?></h3>
                                    <p><?= htmlspecialchars($trx['email']) ?></p>
                                </div>
                                <span class="membership-badge membership-<?= htmlspecialchars($trx['package_slug']) ?>"><?= htmlspecialchars($trx['package_name']) ?></span>
                            </div>
                            <div class="verification-detail">
                                <p><strong>Nominal:</strong> Rp<?= number_format($trx['amount'], 0, ',', '.') ?></p>
                                <p><strong>Metode:</strong> <?= htmlspecialchars($trx['payment_method']) ?></p>
                                <p><strong>Nama Pengirim:</strong> <?= htmlspecialchars($trx['payment_account_name']) ?></p>
                                <p><strong>No. Referensi:</strong> <?= htmlspecialchars($trx['payment_reference']) ?></p>
                                <p><strong>Catatan User:</strong> <?= htmlspecialchars($trx['payment_note'] ?: '-') ?></p>
                                <p><strong>Dikirim:</strong> <?= date('d M Y, H:i', strtotime($trx['created_at'])) ?></p>
                            </div>
                            <form method="POST" class="verification-actions">
                                <input type="hidden" name="transaction_id" value="<?= $trx['id'] ?>">
                                <textarea name="admin_note" placeholder="Catatan admin, contoh: Pembayaran valid / Nominal tidak sesuai"></textarea>
                                <div>
                                    <button type="submit" name="action" value="approve" class="btn-approve"><i class="fas fa-check"></i> Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn-reject"><i class="fas fa-times"></i> Reject</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="login-notice"><i class="fas fa-check-circle"></i><p>Tidak ada transaksi yang menunggu verifikasi.</p></div>
            <?php endif; ?>

            <h2 style="margin: 40px 0 20px;"><i class="fas fa-list"></i> Riwayat Transaksi</h2>
            <div class="data-table">
                <table>
                    <thead>
                        <tr><th>User</th><th>Paket</th><th>Nominal</th><th>Status</th><th>Tanggal</th><th>Diverifikasi Oleh</th><th>Catatan</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($allTransactions) > 0): ?>
                            <?php foreach ($allTransactions as $trx): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($trx['user_name']) ?></strong><br><small><?= htmlspecialchars($trx['email']) ?></small></td>
                                    <td><span class="membership-badge membership-<?= htmlspecialchars($trx['package_slug']) ?>"><?= htmlspecialchars($trx['package_name']) ?></span></td>
                                    <td>Rp<?= number_format($trx['amount'], 0, ',', '.') ?></td>
                                    <td><span class="status-pill status-<?= htmlspecialchars($trx['status']) ?>"><?= ucfirst($trx['status']) ?></span></td>
                                    <td><?= date('d M Y, H:i', strtotime($trx['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($trx['verifier_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($trx['admin_note'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; color: var(--text-muted); padding: 40px;">Belum ada transaksi membership.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>
