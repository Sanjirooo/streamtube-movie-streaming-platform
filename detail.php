<?php
session_start();
require_once 'config/database.php';

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$movie = querySingle("SELECT * FROM movies WHERE id = ?", [$movieId]);

if (!$movie) {
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    $existingHistory = querySingle("SELECT id FROM histories WHERE user_id = ? AND movie_id = ?", [$_SESSION['user_id'], $movieId]);
    if ($existingHistory) {
        execute("UPDATE histories SET watched_at = NOW() WHERE user_id = ? AND movie_id = ?", [$_SESSION['user_id'], $movieId]);
    } else {
        execute("INSERT INTO histories (user_id, movie_id) VALUES (?, ?)", [$_SESSION['user_id'], $movieId]);
    }
}

$avgRating = querySingle("SELECT AVG(rating) as avg, COUNT(*) as count FROM ratings WHERE movie_id = ?", [$movieId]);
$rating = $avgRating['avg'] ? round($avgRating['avg'], 1) : '0.0';
$ratingCount = $avgRating['count'] ?? 0;

$userRating = null;
if (isset($_SESSION['user_id'])) {
    $userRatingData = querySingle("SELECT rating FROM ratings WHERE user_id = ? AND movie_id = ?", [$_SESSION['user_id'], $movieId]);
    $userRating = $userRatingData['rating'] ?? null;
}

$comments = queryAll("
    SELECT c.*, u.name as user_name, u.avatar as user_avatar, u.membership_status,
           mp.name as membership_name, mp.slug as membership_slug, mp.comment_banner as membership_comment_banner
    FROM comments c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id
    WHERE c.movie_id = ?
    ORDER BY c.created_at DESC
", [$movieId]);

$isInMyList = false;
if (isset($_SESSION['user_id'])) {
    $checkList = querySingle("SELECT id FROM my_lists WHERE user_id = ? AND movie_id = ?", [$_SESSION['user_id'], $movieId]);
    $isInMyList = (bool)$checkList;
}

$user = null;
if (isset($_SESSION['user_id'])) {
    $user = querySingle("SELECT u.*, mp.name as membership_name, mp.slug as membership_slug, mp.comment_banner as membership_comment_banner FROM users u LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id WHERE u.id = ?", [$_SESSION['user_id']]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> - Streamtube</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo"><i class="fas fa-play"></i> Streamtube</a>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php#hero" class="nav-link">Home</a></li>
                <li><a href="index.php#movies" class="nav-link">Movies</a></li>
                <li><a href="index.php#series" class="nav-link">Series</a></li>
                <li><a href="index.php#trending" class="nav-link">Trending</a></li>
                <?php if (isset($_SESSION['user_id'])): ?><li><a href="membership.php" class="nav-link">Membership</a></li><li><a href="mylist.php" class="nav-link">My List</a></li><?php endif; ?>
            </ul>
            <div class="nav-actions">
                <form action="search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Search films..." class="search-input">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
                <?php if (isset($_SESSION['user_id'])): ?>
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
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="detail-hero" style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(20,20,20,1)), url('<?= getMovieBanner($movie) ?>')">
        <div class="detail-hero-content">
            <div class="detail-poster">
                <img src="<?= getMoviePoster($movie) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
            </div>
            <div class="detail-info">
                <h1><?= htmlspecialchars($movie['title']) ?></h1>
                <div class="detail-meta">
                    <span class="type-badge <?= $movie['type'] ?>"><?= ucfirst($movie['type']) ?></span>
                    <span><i class="far fa-calendar"></i> <?= $movie['year'] ?></span>
                    <span><i class="fas fa-film"></i> <?= htmlspecialchars($movie['genre']) ?></span>
                    <span><i class="fas fa-star"></i> <?= $rating ?> (<?= $ratingCount ?>)</span>
                </div>
                <div class="detail-actions">
                    <a href="#" class="btn-play" onclick="playVideo()"><i class="fas fa-play"></i> Watch Now</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="btn-list <?= $isInMyList ? 'added' : '' ?>" onclick="toggleMyList(<?= $movie['id'] ?>, this)">
                            <i class="fas fa-<?= $isInMyList ? 'check' : 'plus' ?>"></i> <?= $isInMyList ? 'Added to My List' : 'Add to My List' ?>
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="btn-list"><i class="fas fa-plus"></i> My List</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div id="videoContainer" style="display: none; margin-bottom: 40px;">
            <div style="background: #000; border-radius: 10px; overflow: hidden; aspect-ratio: 16/9;">
                <iframe id="videoPlayer" class="video-player" data-movie-id="<?= $movieId ?>" width="100%" height="100%" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>

        <div class="synopsis">
            <h2 style="margin-bottom: 15px;">Synopsis</h2>
            <p><?= nl2br(htmlspecialchars($movie['synopsis'])) ?></p>
        </div>

        <div class="rating-section">
            <h3><i class="fas fa-star"></i> Rating</h3>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="rating-form">
                    <span>Your Rating:</span>
                    <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" onclick="setRating(<?= $i ?>, <?= $movieId ?>)" class="<?= $userRating && $i <= $userRating ? 'active' : '' ?>"><i class="fas fa-star"></i></button>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="login-notice"><i class="fas fa-lock"></i><p>Login dulu untuk memberi rating.</p></div>
            <?php endif; ?>
            <div class="avg-rating">
                <i class="fas fa-star"></i>
                <span class="rating-num"><?= $rating ?></span>
                <span class="rating-count">(<?= $ratingCount ?> ratings)</span>
            </div>
        </div>

        <div class="comments-section">
            <h3><i class="fas fa-comments"></i> Comments</h3>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="comment-form">
                    <textarea id="commentInput" placeholder="Share your thoughts about this movie..."></textarea>
                    <button type="button" onclick="submitComment(<?= $movieId ?>)"><i class="fas fa-paper-plane"></i> Post Comment</button>
                </div>
            <?php else: ?>
                <div class="login-notice"><i class="fas fa-comments"></i><p>Login dulu untuk bisa komen ya.</p></div>
            <?php endif; ?>
            <div class="comments-list">
                <?php if (count($comments) > 0): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <img src="<?= getUserAvatar(['name' => $comment['user_name'], 'avatar' => $comment['user_avatar'] ?? null]) ?>" alt="<?= htmlspecialchars($comment['user_name']) ?>">
                                <div>
                                    <div class="comment-user"><?= getUserDisplayName(['name' => $comment['user_name'], 'membership_status' => $comment['membership_status'] ?? 'none', 'membership_name' => $comment['membership_name'] ?? null, 'membership_slug' => $comment['membership_slug'] ?? null]) ?> <?= getMembershipBadge(['membership_status' => $comment['membership_status'] ?? 'none', 'membership_name' => $comment['membership_name'] ?? null, 'membership_slug' => $comment['membership_slug'] ?? null]) ?></div>
                                    <?php if (!empty($comment['membership_comment_banner']) && (($comment['membership_status'] ?? 'none') === 'active')): ?>
                                        <div class="comment-member-banner"><?= htmlspecialchars($comment['membership_comment_banner']) ?></div>
                                    <?php endif; ?>
                                    <div class="comment-date"><?= date('d M Y, H:i', strtotime($comment['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="login-notice"><p>Belum ada komentar. Jadilah yang pertama!</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand"><a href="index.php" class="logo"><i class="fas fa-play"></i> Streamtube</a><p>Your favorite streaming platform.</p></div>
            <div class="footer-links"><h4>Navigation</h4><ul><li><a href="index.php#hero">Home</a></li><li><a href="index.php#movies">Movies</a></li><li><a href="index.php#series">Series</a></li><li><a href="index.php#trending">Trending</a></li></ul></div>
            <div class="footer-links"><h4>Account</h4><ul><?php if (isset($_SESSION['user_id'])): ?><li><a href="profile.php">Profile</a></li><li><a href="membership.php">Membership</a></li><li><a href="history.php">History</a></li><li><a href="mylist.php">My List</a></li><li><a href="logout.php">Logout</a></li><?php else: ?><li><a href="login.php">Login</a></li><li><a href="register.php">Register</a></li><?php endif; ?></ul></div>
            <div class="footer-social"><h4>Follow Us</h4><div class="social-icons"><a href="#"><i class="fab fa-facebook"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-youtube"></i></a></div></div>
        </div>
        <div class="footer-bottom"><p>&copy; 2024 Streamtube. All rights reserved.</p></div>
    </footer>

    <script src="assets/js/script.js"></script>
    <script>
        function playVideo() {
            const videoContainer = document.getElementById('videoContainer');
            const videoPlayer = document.getElementById('videoPlayer');
            videoContainer.style.display = 'block';
            videoPlayer.src = '<?= htmlspecialchars($movie['video_url']) ?>';
            videoContainer.scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>