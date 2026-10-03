<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = querySingle("SELECT u.*, mp.name as membership_name, mp.slug as membership_slug, mp.comment_banner as membership_comment_banner FROM users u LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id WHERE u.id = ?", [$_SESSION['user_id']]);

$myList = queryAll("
    SELECT m.*, ml.created_at as added_at
    FROM movies m
    INNER JOIN my_lists ml ON m.id = ml.movie_id
    WHERE ml.user_id = ?
    ORDER BY ml.created_at DESC
", [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My List - Streamtube</title>
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
                <li><a href="membership.php" class="nav-link">Membership</a></li>
                <li><a href="mylist.php" class="nav-link active">My List</a></li>
            </ul>
            <div class="nav-actions">
                <div class="user-menu">
                    <button class="user-btn" onclick="toggleUserMenu()">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=e50914&color=fff" alt="Avatar">
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-info">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=e50914&color=fff" alt="Avatar">
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

    <div class="search-container">
        <div class="search-header">
            <h1><i class="fas fa-list"></i> My List</h1>
            <p style="color: var(--text-secondary); margin-top: 10px;"><?= count($myList) ?> films in your list</p>
        </div>

        <div class="search-results">
            <?php if (count($myList) > 0): ?>
                <div class="results-grid">
                    <?php foreach ($myList as $movie): ?>
                        <?php $avgRating = querySingle("SELECT AVG(rating) as avg FROM ratings WHERE movie_id = ?", [$movie['id']]); $rating = $avgRating['avg'] ? round($avgRating['avg'], 1) : 'N/A'; ?>
                        <div class="movie-card" onclick="location.href='detail.php?id=<?= $movie['id'] ?>'">
                            <div class="poster-container">
                                <img src="<?= htmlspecialchars($movie['poster']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="poster">
                                <div class="overlay"><a href="detail.php?id=<?= $movie['id'] ?>" class="btn-play-small"><i class="fas fa-play"></i></a></div>
                                <span class="type-badge <?= $movie['type'] ?>"><?= ucfirst($movie['type']) ?></span>
                            </div>
                            <div class="movie-info">
                                <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                                <div class="movie-meta"><span class="genre"><?= htmlspecialchars($movie['genre']) ?></span><span class="year"><?= $movie['year'] ?></span></div>
                                <div class="movie-rating"><i class="fas fa-star"></i> <?= $rating ?></div>
                                <p style="color: var(--text-muted); font-size: 11px; margin-top: 5px;">Added: <?= date('d M Y', strtotime($movie['added_at'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="login-notice" style="max-width: 500px; margin: 0 auto;"><i class="fas fa-list"></i><h3 style="margin-bottom: 10px;">Your List is Empty</h3><p>Login dulu untuk menambahkan ke My List.</p><a href="index.php" class="btn-play" style="display: inline-flex; margin-top: 20px;"><i class="fas fa-home"></i> Browse Movies</a></div>
            <?php endif; ?>
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