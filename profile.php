<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = querySingle("SELECT u.*, mp.name as membership_name, mp.slug as membership_slug, mp.comment_banner as membership_comment_banner FROM users u LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id WHERE u.id = ?", [$_SESSION['user_id']]);

$myListCount = querySingle("SELECT COUNT(*) as count FROM my_lists WHERE user_id = ?", [$_SESSION['user_id']]);
$historyCount = querySingle("SELECT COUNT(*) as count FROM histories WHERE user_id = ?", [$_SESSION['user_id']]);
$commentsCount = querySingle("SELECT COUNT(*) as count FROM comments WHERE user_id = ?", [$_SESSION['user_id']]);
$ratingsCount = querySingle("SELECT COUNT(*) as count FROM ratings WHERE user_id = ?", [$_SESSION['user_id']]);

$userHistory = queryAll("
    SELECT h.*, m.title, m.poster, m.genre, m.year, m.type
    FROM histories h
    JOIN movies m ON h.movie_id = m.id
    WHERE h.user_id = ?
    ORDER BY h.watched_at DESC
    LIMIT 10
", [$_SESSION['user_id']]);

$userComments = queryAll("
    SELECT c.*, m.title as movie_title, m.poster as movie_poster, m.poster as poster
    FROM comments c
    JOIN movies m ON c.movie_id = m.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
    LIMIT 10
", [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Streamtube</title>
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

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="<?= getUserAvatar($user) ?>" alt="<?= htmlspecialchars($user['name']) ?>" id="profileAvatar" style="cursor: pointer;" onclick="openAvatarModal()">
                <div class="avatar-edit-icon" onclick="openAvatarModal()"><i class="fas fa-camera"></i></div>
            </div>
            <div class="profile-info">
                <h1><?= getUserDisplayName($user) ?></h1>
                <p><i class="fas fa-crown"></i> Membership: <?= getMembershipBadge($user) ?></p>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                <p><i class="fas fa-calendar"></i> Joined <?= date('F Y', strtotime($user['created_at'])) ?></p>
                <div class="profile-stats">
                    <div class="stat-item"><div class="stat-num"><?= $myListCount['count'] ?></div><div class="stat-label">My List</div></div>
                    <div class="stat-item"><div class="stat-num"><?= $historyCount['count'] ?></div><div class="stat-label">Watched</div></div>
                    <div class="stat-item"><div class="stat-num"><?= $commentsCount['count'] ?></div><div class="stat-label">Comments</div></div>
                    <div class="stat-item"><div class="stat-num"><?= $ratingsCount['count'] ?></div><div class="stat-label">Ratings</div></div>
                </div>
            </div>
        </div>

        <div class="profile-tabs">
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="openTab('history')"><i class="fas fa-history"></i> Watch History</button>
                <button class="tab-btn" onclick="openTab('comments')"><i class="fas fa-comments"></i> My Comments</button>
            </div>

            <div id="history" class="tab-content active">
                <?php if (count($userHistory) > 0): ?>
                    <div class="history-list">
                        <?php foreach ($userHistory as $history): ?>
                            <a href="detail.php?id=<?= $history['movie_id'] ?>" class="history-item">
                                <img src="<?= getMoviePoster($history) ?>" alt="<?= htmlspecialchars($history['title']) ?>">
                                <div class="history-info">
                                    <h4><?= htmlspecialchars($history['title']) ?></h4>
                                    <span><?= htmlspecialchars($history['genre']) ?> • <?= $history['year'] ?></span>
                                </div>
                                <div style="text-align: right;">
                                    <span class="type-badge <?= $history['type'] ?>" style="position: static; display: inline-block;"><?= ucfirst($history['type']) ?></span>
                                    <p style="color: var(--text-muted); font-size: 12px; margin-top: 5px;"><?= date('d M Y, H:i', strtotime($history['watched_at'])) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="login-notice"><i class="fas fa-history"></i><p>Login dulu untuk melihat riwayat tontonan.</p></div>
                <?php endif; ?>
            </div>

            <div id="comments" class="tab-content">
                <?php if (count($userComments) > 0): ?>
                    <div class="comments-list">
                        <?php foreach ($userComments as $comment): ?>
                            <a href="detail.php?id=<?= $comment['movie_id'] ?>" class="comment-item" style="display: flex; gap: 20px; text-decoration: none;">
                                <img src="<?= getMoviePoster($comment) ?>" alt="" style="width: 80px; height: 120px; border-radius: 8px; object-fit: cover;">
                                <div style="flex: 1;">
                                    <h4 style="margin-bottom: 5px;"><?= htmlspecialchars($comment['movie_title']) ?></h4>
                                    <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 10px;"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                    <span style="color: var(--text-muted); font-size: 12px;"><?= date('d M Y, H:i', strtotime($comment['created_at'])) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="login-notice"><i class="fas fa-comments"></i><p>You haven't made any comments yet.</p></div>
                <?php endif; ?>
            </div>
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

    <!-- Avatar Upload Modal -->
    <div class="modal" id="avatarModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2><i class="fas fa-camera"></i> Change Avatar</h2>
                <button class="modal-close" onclick="closeAvatarModal()">&times;</button>
            </div>
            <div style="padding: 20px;">
                <div id="avatarPreview" style="width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 20px; overflow: hidden; border: 3px solid var(--primary); display: flex; align-items: center; justify-content: center; background: var(--card-bg);">
                    <img src="<?= getUserAvatar($user) ?>" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <form id="avatarForm" enctype="multipart/form-data">
                    <div style="border: 2px dashed var(--border-color); border-radius: 10px; padding: 30px; text-align: center; cursor: pointer; transition: border-color 0.3s;" id="dropZone" ondragover="event.preventDefault(); this.style.borderColor='var(--primary)'" ondragleave="this.style.borderColor='var(--border-color)'" onclick="document.getElementById('avatarInput').click()">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: var(--text-muted); margin-bottom: 10px;"></i>
                        <p style="color: var(--text-secondary); margin-bottom: 10px;">Drag & drop atau klik untuk pilih foto</p>
                        <p style="color: var(--text-muted); font-size: 12px;">JPG, PNG, GIF, WebP (max 2MB)</p>
                    </div>
                    <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewAvatar(this)">
                </form>
                <div id="avatarMessage" style="margin-top: 15px; padding: 10px; border-radius: 5px; display: none;"></div>
                <button type="button" onclick="uploadAvatar()" id="uploadBtn" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 5px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 15px; display: none;">
                    <i class="fas fa-upload"></i> Upload Avatar
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        let selectedFile = null;

        function openAvatarModal() {
            document.getElementById('avatarModal').style.display = 'flex';
        }

        function closeAvatarModal() {
            document.getElementById('avatarModal').style.display = 'none';
            document.getElementById('avatarInput').value = '';
            document.getElementById('uploadBtn').style.display = 'none';
            document.getElementById('avatarMessage').style.display = 'none';
            selectedFile = null;
        }

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                selectedFile = input.files[0];

                // Validate file size
                if (selectedFile.size > 2 * 1024 * 1024) {
                    showAvatarMessage('File terlalu besar! Maksimal 2MB', 'error');
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(selectedFile.type)) {
                    showAvatarMessage('Format tidak didukung! Gunakan JPG, PNG, GIF, atau WebP', 'error');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('#avatarPreview img').src = e.target.result;
                    document.getElementById('uploadBtn').style.display = 'block';
                    document.getElementById('avatarMessage').style.display = 'none';
                }
                reader.readAsDataURL(selectedFile);
            }
        }

        function showAvatarMessage(msg, type) {
            const el = document.getElementById('avatarMessage');
            el.textContent = msg;
            el.style.display = 'block';
            el.style.background = type === 'success' ? 'rgba(70, 211, 105, 0.2)' : 'rgba(229, 9, 20, 0.2)';
            el.style.color = type === 'success' ? '#46d369' : '#e50914';
            el.style.border = `1px solid ${type === 'success' ? '#46d369' : '#e50914'}`;
        }

        function uploadAvatar() {
            if (!selectedFile) return;

            const formData = new FormData();
            formData.append('avatar', selectedFile);

            document.getElementById('uploadBtn').disabled = true;
            document.getElementById('uploadBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            fetch('api/upload_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAvatarMessage('Avatar berhasil diupdate! ✓', 'success');

                    // Update all avatars on page
                    document.querySelectorAll('.user-btn img, .user-info img, #profileAvatar').forEach(img => {
                        img.src = data.avatar + '?t=' + new Date().getTime();
                    });

                    // Close modal after 1.5 seconds
                    setTimeout(() => {
                        closeAvatarModal();
                        document.getElementById('uploadBtn').disabled = false;
                        document.getElementById('uploadBtn').innerHTML = '<i class="fas fa-upload"></i> Upload Avatar';
                    }, 1500);
                } else {
                    showAvatarMessage(data.message, 'error');
                    document.getElementById('uploadBtn').disabled = false;
                    document.getElementById('uploadBtn').innerHTML = '<i class="fas fa-upload"></i> Upload Avatar';
                }
            })
            .catch(error => {
                showAvatarMessage('Terjadi kesalahan!', 'error');
                document.getElementById('uploadBtn').disabled = false;
                document.getElementById('uploadBtn').innerHTML = '<i class="fas fa-upload"></i> Upload Avatar';
            });
        }

        // Close modal on outside click
        document.getElementById('avatarModal').addEventListener('click', function(e) {
            if (e.target === this) closeAvatarModal();
        });
    </script>
</body>
</html>