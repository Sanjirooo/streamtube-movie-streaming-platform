<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pendingMembershipCount = querySingle("SELECT COUNT(*) as count FROM membership_transactions WHERE status = 'pending'");

$users = queryAll("SELECT u.*, mp.name as membership_name, mp.slug as membership_slug,
    (SELECT COUNT(*) FROM my_lists WHERE user_id = u.id) as list_count,
    (SELECT COUNT(*) FROM histories WHERE user_id = u.id) as history_count,
    (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count,
    (SELECT COUNT(*) FROM ratings WHERE user_id = u.id) as rating_count
    FROM users u LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id WHERE u.role = 'user' ORDER BY u.created_at DESC");

$selectedUser = null;
$userMyList = [];
$userHistory = [];
$userComments = [];

if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $selectedUserId = (int)$_GET['view'];
    $selectedUser = querySingle("SELECT u.*, mp.name as membership_name, mp.slug as membership_slug FROM users u LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id WHERE u.id = ? AND u.role = 'user'", [$selectedUserId]);

    if ($selectedUser) {
        $userMyList = queryAll("SELECT m.*, ml.created_at as added_at FROM movies m JOIN my_lists ml ON m.id = ml.movie_id WHERE ml.user_id = ? ORDER BY ml.created_at DESC", [$selectedUserId]);
        $userHistory = queryAll("SELECT m.*, h.watched_at FROM movies m JOIN histories h ON m.id = h.movie_id WHERE h.user_id = ? ORDER BY h.watched_at DESC", [$selectedUserId]);
        $userComments = queryAll("SELECT c.*, m.title as movie_title FROM comments c JOIN movies m ON c.movie_id = m.id WHERE c.user_id = ? ORDER BY c.created_at DESC", [$selectedUserId]);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Streamtube Admin</title>
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
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> <span>Users</span></a></li>
                <li><a href="comments.php"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
                <li><a href="membership_verification.php"><i class="fas fa-crown"></i> <span>Verifikasi Membership</span><?php if (($pendingMembershipCount['count'] ?? 0) > 0): ?><b class="admin-menu-badge"><?= $pendingMembershipCount['count'] ?></b><?php endif; ?></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <?php if ($selectedUser): ?>
                <div class="admin-header">
                    <div><h1><i class="fas fa-user"></i> <?= getUserDisplayName($selectedUser) ?></h1><p style="color: var(--text-secondary);"><?= htmlspecialchars($selectedUser['email']) ?> • <?= getMembershipBadge($selectedUser) ?></p></div>
                    <a href="users.php" class="btn-play" style="border: none; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Users</a>
                </div>

                <div class="admin-stats">
                    <div class="stat-card"><i class="fas fa-list"></i><div class="stat-info"><h3><?= count($userMyList) ?></h3><p>My List</p></div></div>
                    <div class="stat-card"><i class="fas fa-history"></i><div class="stat-info"><h3><?= count($userHistory) ?></h3><p>History</p></div></div>
                    <div class="stat-card"><i class="fas fa-comments"></i><div class="stat-info"><h3><?= count($userComments) ?></h3><p>Comments</p></div></div>
                </div>

                <div style="margin-bottom: 40px;">
                    <h2 style="margin-bottom: 20px;"><i class="fas fa-list"></i> My List</h2>
                    <?php if (count($userMyList) > 0): ?>
                        <div class="results-grid">
                            <?php foreach ($userMyList as $movie): ?>
                                <div class="movie-card">
                                    <div class="poster-container"><img src="<?= getMoviePoster($movie, true) ?>" alt="" class="poster"><span class="type-badge <?= $movie['type'] ?>"><?= ucfirst($movie['type']) ?></span></div>
                                    <div class="movie-info"><h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3><p style="color: var(--text-muted); font-size: 11px;">Added: <?= date('d M Y', strtotime($movie['added_at'])) ?></p></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?><div class="login-notice"><p>No movies in My List</p></div><?php endif; ?>
                </div>

                <div style="margin-bottom: 40px;">
                    <h2 style="margin-bottom: 20px;"><i class="fas fa-history"></i> Watch History</h2>
                    <?php if (count($userHistory) > 0): ?>
                        <div class="results-grid">
                            <?php foreach ($userHistory as $movie): ?>
                                <div class="movie-card">
                                    <div class="poster-container"><img src="<?= getMoviePoster($movie, true) ?>" alt="" class="poster"><span class="type-badge <?= $movie['type'] ?>"><?= ucfirst($movie['type']) ?></span></div>
                                    <div class="movie-info"><h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3><p style="color: var(--text-muted); font-size: 11px;">Watched: <?= date('d M Y', strtotime($movie['watched_at'])) ?></p></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?><div class="login-notice"><p>No watch history</p></div><?php endif; ?>
                </div>

                <div style="margin-bottom: 40px;">
                    <h2 style="margin-bottom: 20px;"><i class="fas fa-comments"></i> Comments</h2>
                    <?php if (count($userComments) > 0): ?>
                        <div class="comments-list">
                            <?php foreach ($userComments as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-header"><div><div class="comment-user"><?= htmlspecialchars($comment['movie_title']) ?></div><div class="comment-date"><?= date('d M Y, H:i', strtotime($comment['created_at'])) ?></div></div></div>
                                    <div class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?><div class="login-notice"><p>No comments yet</p></div><?php endif; ?>
                </div>
            <?php else: ?>
                <div class="admin-header"><div><h1>Manage Users</h1><p style="color: var(--text-secondary);">View all registered users and their activities</p></div></div>

                <div class="data-table">
                    <table>
                        <thead><tr><th>User</th><th>Email</th><th>Membership</th><th>My List</th><th>History</th><th>Comments</th><th>Ratings</th><th>Joined</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><div style="display: flex; align-items: center; gap: 15px;"><img src="<?= getUserAvatar($user, true) ?>" alt="" style="width: 40px; height: 40px; border-radius: 50%;"><strong><?= getUserDisplayName($user) ?></strong></div></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= getMembershipBadge($user) ?></td>
                                    <td><?= $user['list_count'] ?></td>
                                    <td><?= $user['history_count'] ?></td>
                                    <td><?= $user['comment_count'] ?></td>
                                    <td><?= $user['rating_count'] ?></td>
                                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                    <td><a href="users.php?view=<?= $user['id'] ?>" class="btn-action btn-edit" style="text-decoration: none;"><i class="fas fa-eye"></i> View</a></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($users) === 0): ?>
                                <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 40px;">No users registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>