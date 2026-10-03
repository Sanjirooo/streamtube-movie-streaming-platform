<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pendingMembershipCount = querySingle("SELECT COUNT(*) as count FROM membership_transactions WHERE status = 'pending'");

$adminName = $_SESSION['admin_name'];

$totalMovies = querySingle("SELECT COUNT(*) as count FROM movies");
$totalUsers = querySingle("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$totalComments = querySingle("SELECT COUNT(*) as count FROM comments");
$activeMembers = querySingle("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND membership_status = 'active'");
$avgRatingData = querySingle("SELECT AVG(rating) as avg FROM ratings");

$mostWatched = querySingle("SELECT m.title, COUNT(h.id) as watch_count FROM movies m LEFT JOIN histories h ON m.id = h.movie_id GROUP BY m.id ORDER BY watch_count DESC LIMIT 1");
$mostInList = querySingle("SELECT m.title, COUNT(ml.id) as list_count FROM movies m LEFT JOIN my_lists ml ON m.id = ml.movie_id GROUP BY m.id ORDER BY list_count DESC LIMIT 1");

$recentComments = queryAll("SELECT c.*, u.name as user_name, m.title as movie_title FROM comments c JOIN users u ON c.user_id = u.id JOIN movies m ON c.movie_id = m.id ORDER BY c.created_at DESC LIMIT 5");
$recentUsers = queryAll("SELECT u.*, mp.name as membership_name, mp.slug as membership_slug FROM users u LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id WHERE u.role = 'user' ORDER BY u.created_at DESC LIMIT 5");
$recentMembershipTransactions = queryAll("SELECT mt.*, u.name as user_name, mp.name as package_name, mp.slug as package_slug FROM membership_transactions mt JOIN users u ON mt.user_id = u.id JOIN membership_packages mp ON mt.package_id = mp.id ORDER BY mt.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Streamtube</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo"><i class="fas fa-play"></i> <span>Streamtube</span></a>
            <ul class="admin-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="movies.php"><i class="fas fa-film"></i> <span>Movies</span></a></li>
                <li><a href="hero.php"><i class="fas fa-images"></i> <span>Hero Slider</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                <li><a href="comments.php"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
                <li><a href="membership_verification.php"><i class="fas fa-crown"></i> <span>Verifikasi Membership</span><?php if (($pendingMembershipCount['count'] ?? 0) > 0): ?><b class="admin-menu-badge"><?= $pendingMembershipCount['count'] ?></b><?php endif; ?></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1>Dashboard</h1>
                    <p style="color: var(--text-secondary);">Welcome back, <?= htmlspecialchars($adminName) ?>!</p>
                </div>
            </div>

            <div class="admin-stats">
                <div class="stat-card"><i class="fas fa-film"></i><div class="stat-info"><h3><?= $totalMovies['count'] ?></h3><p>Total Films</p></div></div>
                <div class="stat-card"><i class="fas fa-users"></i><div class="stat-info"><h3><?= $totalUsers['count'] ?></h3><p>Total Users</p></div></div>
                <div class="stat-card"><i class="fas fa-crown"></i><div class="stat-info"><h3><?= $activeMembers['count'] ?></h3><p>Active Members</p></div></div>
                <div class="stat-card"><i class="fas fa-hourglass-half"></i><div class="stat-info"><h3><?= $pendingMembershipCount['count'] ?? 0 ?></h3><p>Pending Membership</p></div></div>
                <div class="stat-card"><i class="fas fa-comments"></i><div class="stat-info"><h3><?= $totalComments['count'] ?></h3><p>Total Comments</p></div></div>
                <div class="stat-card"><i class="fas fa-star"></i><div class="stat-info"><h3><?= $avgRatingData['avg'] ? round($avgRatingData['avg'], 1) : '0.0' ?></h3><p>Average Rating</p></div></div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; margin-bottom: 40px;">
                <div class="data-table">
                    <div style="padding: 20px; border-bottom: 1px solid var(--border-color);"><h3><i class="fas fa-eye"></i> Most Watched</h3></div>
                    <table><tr><td><strong><?= htmlspecialchars($mostWatched['title'] ?? 'N/A') ?></strong></td><td style="text-align: right; color: var(--primary);"><?= $mostWatched['watch_count'] ?? 0 ?> views</td></tr></table>
                </div>
                <div class="data-table">
                    <div style="padding: 20px; border-bottom: 1px solid var(--border-color);"><h3><i class="fas fa-list"></i> Most in My List</h3></div>
                    <table><tr><td><strong><?= htmlspecialchars($mostInList['title'] ?? 'N/A') ?></strong></td><td style="text-align: right; color: var(--primary);"><?= $mostInList['list_count'] ?? 0 ?> users</td></tr></table>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
                <div class="data-table">
                    <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;"><h3><i class="fas fa-comment"></i> Recent Comments</h3><a href="comments.php" style="color: var(--primary); font-size: 14px;">View All</a></div>
                    <table>
                        <?php if (count($recentComments) > 0): ?>
                            <?php foreach ($recentComments as $comment): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($comment['user_name']) ?></strong><span style="color: var(--text-muted); font-size: 12px;"> on <?= htmlspecialchars($comment['movie_title']) ?></span><p style="color: var(--text-secondary); font-size: 13px; margin-top: 5px;"><?= htmlspecialchars(substr($comment['comment'], 0, 50)) ?>...</p></td>
                                    <td style="text-align: right; color: var(--text-muted); font-size: 12px;"><?= date('d M', strtotime($comment['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: var(--text-muted);">No comments yet</td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <div class="data-table">
                    <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;"><h3><i class="fas fa-crown"></i> Recent Membership</h3><a href="membership_verification.php" style="color: var(--primary); font-size: 14px;">View All</a></div>
                    <table>
                        <?php if (count($recentMembershipTransactions) > 0): ?>
                            <?php foreach ($recentMembershipTransactions as $trx): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($trx['user_name']) ?></strong><p style="color: var(--text-muted); font-size: 12px;"><span class="membership-badge membership-<?= htmlspecialchars($trx['package_slug']) ?>"><?= htmlspecialchars($trx['package_name']) ?></span></p></td>
                                    <td style="text-align: right;"><span class="status-pill status-<?= htmlspecialchars($trx['status']) ?>"><?= ucfirst($trx['status']) ?></span><p style="color: var(--text-muted); font-size: 12px; margin-top: 5px;"><?= date('d M', strtotime($trx['created_at'])) ?></p></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: var(--text-muted);">No membership transactions yet</td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <div class="data-table">
                    <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;"><h3><i class="fas fa-user-plus"></i> Recent Users</h3><a href="users.php" style="color: var(--primary); font-size: 14px;">View All</a></div>
                    <table>
                        <?php if (count($recentUsers) > 0): ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><div style="display: flex; align-items: center; gap: 15px;"><img src="<?= getUserAvatar($user, true) ?>" alt="" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"><div><strong><?= getUserDisplayName($user) ?></strong><p style="color: var(--text-muted); font-size: 12px;"><?= htmlspecialchars($user['email']) ?></p></div></div></td>
                                    <td style="text-align: right; color: var(--text-muted); font-size: 12px;"><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: var(--text-muted);">No users yet</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>