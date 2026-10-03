<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pendingMembershipCount = querySingle("SELECT COUNT(*) as count FROM membership_transactions WHERE status = 'pending'");

$adminName = $_SESSION['admin_name'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    $result = execute("DELETE FROM comments WHERE id = ?", [$id]);
    if ($result['success']) {
        $message = 'Comment deleted successfully!';
        $messageType = 'success';
    }
}

$comments = queryAll("SELECT c.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar, m.title as movie_title, m.id as movie_id FROM comments c JOIN users u ON c.user_id = u.id JOIN movies m ON c.movie_id = m.id ORDER BY c.created_at DESC");
$ratingStats = queryAll("SELECT r.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar, m.title as movie_title, m.id as movie_id FROM ratings r JOIN users u ON r.user_id = u.id JOIN movies m ON r.movie_id = m.id ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments & Ratings - Streamtube Admin</title>
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
                <li><a href="comments.php" class="active"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
                <li><a href="membership_verification.php"><i class="fas fa-crown"></i> <span>Verifikasi Membership</span><?php if (($pendingMembershipCount['count'] ?? 0) > 0): ?><b class="admin-menu-badge"><?= $pendingMembershipCount['count'] ?></b><?php endif; ?></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header"><div><h1>Comments & Ratings</h1><p style="color: var(--text-secondary);">Monitor user comments and ratings</p></div></div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 50px;">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-comment"></i> All Comments (<?= count($comments) ?>)</h2>
                <div class="data-table">
                    <table>
                        <thead><tr><th>User</th><th>Movie</th><th>Comment</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><div style="display: flex; align-items: center; gap: 10px;"><img src="<?= getUserAvatar(['name' => $comment['user_name'], 'avatar' => $comment['user_avatar'] ?? null], true) ?>" alt="" style="width: 35px; height: 35px; border-radius: 50%;"><div><strong><?= htmlspecialchars($comment['user_name']) ?></strong><p style="color: var(--text-muted); font-size: 11px;"><?= htmlspecialchars($comment['user_email']) ?></p></div></div></td>
                                    <td><a href="../detail.php?id=<?= $comment['movie_id'] ?>" target="_blank" style="color: var(--primary);"><?= htmlspecialchars($comment['movie_title']) ?></a></td>
                                    <td style="max-width: 300px;"><span style="color: var(--text-secondary);"><?= htmlspecialchars(substr($comment['comment'], 0, 80)) ?><?= strlen($comment['comment']) > 80 ? '...' : '' ?></span></td>
                                    <td style="color: var(--text-muted); font-size: 12px;"><?= date('d M Y, H:i', strtotime($comment['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this comment?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $comment['id'] ?>"><button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i></button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($comments) === 0): ?>
                                <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">No comments yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2 style="margin-bottom: 20px;"><i class="fas fa-star"></i> All Ratings (<?= count($ratingStats) ?>)</h2>
                <div class="data-table">
                    <table>
                        <thead><tr><th>User</th><th>Movie</th><th>Rating</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($ratingStats as $rating): ?>
                                <tr>
                                    <td><div style="display: flex; align-items: center; gap: 10px;"><img src="<?= getUserAvatar(['name' => $rating['user_name'], 'avatar' => $rating['user_avatar'] ?? null], true) ?>" alt="" style="width: 35px; height: 35px; border-radius: 50%;"><div><strong><?= htmlspecialchars($rating['user_name']) ?></strong><p style="color: var(--text-muted); font-size: 11px;"><?= htmlspecialchars($rating['user_email']) ?></p></div></div></td>
                                    <td><a href="../detail.php?id=<?= $rating['movie_id'] ?>" target="_blank" style="color: var(--primary);"><?= htmlspecialchars($rating['movie_title']) ?></a></td>
                                    <td><div style="display: flex; gap: 3px;"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fas fa-star" style="color: <?= $i <= $rating['rating'] ? 'var(--warning)' : 'var(--text-muted)' ?>; font-size: 12px;"></i><?php endfor; ?><span style="margin-left: 5px; color: var(--text-muted);">(<?= $rating['rating'] ?>/5)</span></div></td>
                                    <td style="color: var(--text-muted); font-size: 12px;"><?= date('d M Y, H:i', strtotime($rating['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($ratingStats) === 0): ?>
                                <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px;">No ratings yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>