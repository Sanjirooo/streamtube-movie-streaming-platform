<?php
session_start();
require_once 'config/database.php';

// Get hero slides
$heroSlides = queryAll("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY slide_order LIMIT 3");

// Get movies by category
$trendingMovies = queryAll("SELECT * FROM movies WHERE type = 'trending' ORDER BY view_count DESC LIMIT 10");
$allMovies = queryAll("SELECT * FROM movies WHERE type = 'movie' ORDER BY created_at DESC LIMIT 10");
$allSeries = queryAll("SELECT * FROM movies WHERE type = 'series' ORDER BY created_at DESC LIMIT 10");

// Get user's my list if logged in
$userMyList = [];
if (isset($_SESSION['user_id'])) {
    $userMyListResult = queryAll("SELECT movie_id FROM my_lists WHERE user_id = ?", [$_SESSION['user_id']]);
    $userMyList = array_column($userMyListResult, 'movie_id');
}

// Get user info if logged in
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
    <title>Streamtube - Film Streaming Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-play"></i> Streamtube
            </a>

            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li><a href="#hero" class="nav-link active">Home</a></li>
                <li><a href="#movies" class="nav-link">Movies</a></li>
                <li><a href="#series" class="nav-link">Series</a></li>
                <li><a href="#trending" class="nav-link">Trending</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="membership.php" class="nav-link">Membership</a></li>
                    <li><a href="#mylist" class="nav-link">My List</a></li>
                <?php endif; ?>
            </ul>

            <div class="nav-actions">
                <form action="search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Search films..." class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
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

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <div class="hero-slider" id="heroSlider">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <?php
                $movie = querySingle("SELECT * FROM movies WHERE id = ?", [$slide['movie_id']]);
                ?>
                <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>" style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(20,20,20,1)), url('<?= getHeroBanner($slide) ?>')">
                    <div class="hero-content">
                        <h1><?= htmlspecialchars($slide['title']) ?></h1>
                        <p><?= htmlspecialchars($slide['subtitle']) ?></p>
                        <div class="hero-buttons">
                            <?php if ($movie): ?>
                                <a href="detail.php?id=<?= $movie['id'] ?>" class="btn-play">
                                    <i class="fas fa-play"></i> Watch Now
                                </a>
                                <?php
                                $isInList = isset($_SESSION['user_id']) && in_array($movie['id'], $userMyList);
                                ?>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn-list <?= $isInList ? 'added' : '' ?>" onclick="toggleMyList(<?= $movie['id'] ?>, this)">
                                        <i class="fas fa-<?= $isInList ? 'check' : 'plus' ?>"></i>
                                        <?= $isInList ? 'Added' : 'My List' ?>
                                    </button>
                                <?php else: ?>
                                    <a href="login.php" class="btn-list">
                                        <i class="fas fa-plus"></i> My List
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="hero-dots">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <span class="hero-dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)"></span>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Trending Section -->
    <section class="section" id="trending">
        <div class="section-header">
            <h2><i class="fas fa-fire"></i> Trending Now</h2>
            <a href="category.php?type=trending" class="see-all">See All <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="movie-carousel" id="trendingCarousel">
            <button class="carousel-btn prev" onclick="scrollCarousel('trendingCarousel', -1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="carousel-container">
                <?php foreach ($trendingMovies as $movie): ?>
                    <div class="movie-card">
                        <div class="poster-container">
                            <img src="<?= getMoviePoster($movie) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="poster">
                            <div class="overlay">
                                <a href="detail.php?id=<?= $movie['id'] ?>" class="btn-play-small">
                                    <i class="fas fa-play"></i>
                                </a>
                            </div>
                            <span class="type-badge <?= $movie['type'] ?>"><?= ucfirst($movie['type']) ?></span>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                            <div class="movie-meta">
                                <span class="genre"><?= htmlspecialchars($movie['genre']) ?></span>
                                <span class="year"><?= $movie['year'] ?></span>
                            </div>
                            <?php
                            $avgRating = querySingle("SELECT AVG(rating) as avg FROM ratings WHERE movie_id = ?", [$movie['id']]);
                            $rating = $avgRating['avg'] ? round($avgRating['avg'], 1) : 'N/A';
                            ?>
                            <div class="movie-rating">
                                <i class="fas fa-star"></i> <?= $rating ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-btn next" onclick="scrollCarousel('trendingCarousel', 1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </section>

    <!-- Movies Section -->
    <section class="section" id="movies">
        <div class="section-header">
            <h2><i class="fas fa-film"></i> Movies</h2>
            <a href="category.php?type=movie" class="see-all">See All <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="movie-carousel" id="moviesCarousel">
            <button class="carousel-btn prev" onclick="scrollCarousel('moviesCarousel', -1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="carousel-container">
                <?php foreach ($allMovies as $movie): ?>
                    <div class="movie-card">
                        <div class="poster-container">
                            <img src="<?= getMoviePoster($movie) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="poster">
                            <div class="overlay">
                                <a href="detail.php?id=<?= $movie['id'] ?>" class="btn-play-small">
                                    <i class="fas fa-play"></i>
                                </a>
                            </div>
                            <span class="type-badge movie">Movie</span>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                            <div class="movie-meta">
                                <span class="genre"><?= htmlspecialchars($movie['genre']) ?></span>
                                <span class="year"><?= $movie['year'] ?></span>
                            </div>
                            <?php
                            $avgRating = querySingle("SELECT AVG(rating) as avg FROM ratings WHERE movie_id = ?", [$movie['id']]);
                            $rating = $avgRating['avg'] ? round($avgRating['avg'], 1) : 'N/A';
                            ?>
                            <div class="movie-rating">
                                <i class="fas fa-star"></i> <?= $rating ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-btn next" onclick="scrollCarousel('moviesCarousel', 1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </section>

    <!-- Series Section -->
    <section class="section" id="series">
        <div class="section-header">
            <h2><i class="fas fa-tv"></i> Series</h2>
            <a href="category.php?type=series" class="see-all">See All <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="movie-carousel" id="seriesCarousel">
            <button class="carousel-btn prev" onclick="scrollCarousel('seriesCarousel', -1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="carousel-container">
                <?php foreach ($allSeries as $movie): ?>
                    <div class="movie-card">
                        <div class="poster-container">
                            <img src="<?= getMoviePoster($movie) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="poster">
                            <div class="overlay">
                                <a href="detail.php?id=<?= $movie['id'] ?>" class="btn-play-small">
                                    <i class="fas fa-play"></i>
                                </a>
                            </div>
                            <span class="type-badge series">Series</span>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                            <div class="movie-meta">
                                <span class="genre"><?= htmlspecialchars($movie['genre']) ?></span>
                                <span class="year"><?= $movie['year'] ?></span>
                            </div>
                            <?php
                            $avgRating = querySingle("SELECT AVG(rating) as avg FROM ratings WHERE movie_id = ?", [$movie['id']]);
                            $rating = $avgRating['avg'] ? round($avgRating['avg'], 1) : 'N/A';
                            ?>
                            <div class="movie-rating">
                                <i class="fas fa-star"></i> <?= $rating ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-btn next" onclick="scrollCarousel('seriesCarousel', 1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </section>

    <!-- My List Section (Only for logged in users) -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php
        $userMovies = queryAll("
            SELECT m.* FROM movies m
            INNER JOIN my_lists ml ON m.id = ml.movie_id
            WHERE ml.user_id = ?
            ORDER BY ml.created_at DESC
        ", [$_SESSION['user_id']]);
        ?>
        <?php if (count($userMovies) > 0): ?>
            <section class="section" id="mylist">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> My List</h2>
                    <a href="mylist.php" class="see-all">See All <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="movie-carousel" id="mylistCarousel">
                    <button class="carousel-btn prev" onclick="scrollCarousel('mylistCarousel', -1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="carousel-container">
                        <?php foreach ($userMovies as $movie): ?>
                            <div class="movie-card">
                                <div class="poster-container">
                                    <img src="<?= getMoviePoster($movie) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="poster">
                                    <div class="overlay">
                                        <a href="detail.php?id=<?= $movie['id'] ?>" class="btn-play-small">
                                            <i class="fas fa-play"></i>
                                        </a>
                                    </div>
                                    <span class="type-badge <?= $movie['type'] ?>"><?= ucfirst($movie['type']) ?></span>
                                </div>
                                <div class="movie-info">
                                    <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                                    <div class="movie-meta">
                                        <span class="genre"><?= htmlspecialchars($movie['genre']) ?></span>
                                        <span class="year"><?= $movie['year'] ?></span>
                                    </div>
                                    <?php
                                    $avgRating = querySingle("SELECT AVG(rating) as avg FROM ratings WHERE movie_id = ?", [$movie['id']]);
                                    $rating = $avgRating['avg'] ? round($avgRating['avg'], 1) : 'N/A';
                                    ?>
                                    <div class="movie-rating">
                                        <i class="fas fa-star"></i> <?= $rating ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-btn next" onclick="scrollCarousel('mylistCarousel', 1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <a href="index.php" class="logo">
                    <i class="fas fa-play"></i> Streamtube
                </a>
                <p>Your favorite streaming platform for movies and series.</p>
            </div>

            <div class="footer-links">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="#hero">Home</a></li>
                    <li><a href="#movies">Movies</a></li>
                    <li><a href="#series">Series</a></li>
                    <li><a href="#trending">Trending</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Account</h4>
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="history.php">History</a></li>
                        <li><a href="mylist.php">My List</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-social">
                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2024 Streamtube. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>