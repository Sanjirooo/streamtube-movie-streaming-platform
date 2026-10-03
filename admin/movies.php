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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $title = trim($_POST['title']);
            $genre = trim($_POST['genre']);
            $type = $_POST['type'];
            $year = (int)$_POST['year'];
            $synopsis = trim($_POST['synopsis']);
            $poster = trim($_POST['poster']);
            $banner = trim($_POST['banner']);
            $video_url = trim($_POST['video_url']);

            if (empty($title) || empty($genre)) {
                $message = 'Title and genre are required.';
                $messageType = 'error';
            } else {
                $result = execute("INSERT INTO movies (title, genre, type, year, synopsis, poster, banner, video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [$title, $genre, $type, $year, $synopsis, $poster, $banner, $video_url]);
                if ($result['success']) {
                    $message = 'Movie added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add movie.';
                    $messageType = 'error';
                }
            }
        }

        if ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $genre = trim($_POST['genre']);
            $type = $_POST['type'];
            $year = (int)$_POST['year'];
            $synopsis = trim($_POST['synopsis']);
            $poster = trim($_POST['poster']);
            $banner = trim($_POST['banner']);
            $video_url = trim($_POST['video_url']);

            $result = execute("UPDATE movies SET title = ?, genre = ?, type = ?, year = ?, synopsis = ?, poster = ?, banner = ?, video_url = ? WHERE id = ?", [$title, $genre, $type, $year, $synopsis, $poster, $banner, $video_url, $id]);
            if ($result['success']) {
                $message = 'Movie updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update movie.';
                $messageType = 'error';
            }
        }

        if ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $result = execute("DELETE FROM movies WHERE id = ?", [$id]);
            if ($result['success']) {
                $message = 'Movie deleted successfully!';
                $messageType = 'success';
            }
        }
    }
}

$movies = queryAll("SELECT * FROM movies ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - Streamtube Admin</title>
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
                <li><a href="movies.php" class="active"><i class="fas fa-film"></i> <span>Movies</span></a></li>
                <li><a href="hero.php"><i class="fas fa-images"></i> <span>Hero Slider</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                <li><a href="comments.php"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
                <li><a href="membership_verification.php"><i class="fas fa-crown"></i> <span>Verifikasi Membership</span><?php if (($pendingMembershipCount['count'] ?? 0) > 0): ?><b class="admin-menu-badge"><?= $pendingMembershipCount['count'] ?></b><?php endif; ?></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <div><h1>Manage Movies</h1><p style="color: var(--text-secondary);">Add, edit, and delete movies</p></div>
                <button class="btn-play" onclick="openModal('addModal')" style="border: none; cursor: pointer;"><i class="fas fa-plus"></i> Add New Movie</button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="data-table">
                <table>
                    <thead><tr><th>Poster</th><th>Title</th><th>Genre</th><th>Type</th><th>Year</th><th>Views</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($movies as $movie): ?>
                            <tr>
                                <td><img src="<?= getMoviePoster($movie, true) ?>" alt="" class="poster-thumb" onerror="this.src='https://via.placeholder.com/80x120/e50914/ffffff?text=Poster'"></td>
                                <td><strong><?= htmlspecialchars($movie['title']) ?></strong></td>
                                <td><?= htmlspecialchars($movie['genre']) ?></td>
                                <td><span class="type-badge <?= $movie['type'] ?>" style="position: static; display: inline-block; font-size: 11px; padding: 3px 8px;"><?= ucfirst($movie['type']) ?></span></td>
                                <td><?= $movie['year'] ?></td>
                                <td><?= number_format($movie['view_count']) ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-action btn-edit" onclick="editMovie(<?= htmlspecialchars(json_encode($movie)) ?>)"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="btn-action btn-delete" onclick="confirmDelete(<?= $movie['id'] ?>)"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header"><h2><i class="fas fa-plus"></i> Add New Movie</h2><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="admin-form">
                    <div class="form-row">
                        <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
                        <div class="form-group"><label>Genre</label><input type="text" name="genre" placeholder="Action, Drama" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Type</label><select name="type"><option value="movie">Movie</option><option value="series">Series</option><option value="trending">Trending</option></select></div>
                        <div class="form-group"><label>Year</label><input type="number" name="year" min="1900" max="2030" value="2024" required></div>
                    </div>
                    <div class="form-group full-width"><label>Synopsis</label><textarea name="synopsis" rows="3"></textarea></div>
                    <div class="form-group full-width"><label>Poster URL</label><input type="url" name="poster" placeholder="https://..."></div>
                    <div class="form-group full-width"><label>Banner URL</label><input type="url" name="banner" placeholder="https://..."></div>
                    <div class="form-group full-width"><label>Video URL (YouTube Embed)</label><input type="url" name="video_url" placeholder="https://www.youtube.com/embed/..."></div>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Movie</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header"><h2><i class="fas fa-edit"></i> Edit Movie</h2><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="admin-form">
                    <div class="form-row">
                        <div class="form-group"><label>Title</label><input type="text" name="title" id="edit_title" required></div>
                        <div class="form-group"><label>Genre</label><input type="text" name="genre" id="edit_genre" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Type</label><select name="type" id="edit_type"><option value="movie">Movie</option><option value="series">Series</option><option value="trending">Trending</option></select></div>
                        <div class="form-group"><label>Year</label><input type="number" name="year" id="edit_year" min="1900" max="2030" required></div>
                    </div>
                    <div class="form-group full-width"><label>Synopsis</label><textarea name="synopsis" id="edit_synopsis" rows="3"></textarea></div>
                    <div class="form-group full-width"><label>Poster URL</label><input type="url" name="poster" id="edit_poster"></div>
                    <div class="form-group full-width"><label>Banner URL</label><input type="url" name="banner" id="edit_banner"></div>
                    <div class="form-group full-width"><label>Video URL</label><input type="url" name="video_url" id="edit_video_url"></div>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Movie</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" id="deleteForm" style="display: none;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"></form>

    <script src="../assets/js/script.js"></script>
    <script>
        function editMovie(movie) {
            document.getElementById('edit_id').value = movie.id;
            document.getElementById('edit_title').value = movie.title;
            document.getElementById('edit_genre').value = movie.genre;
            document.getElementById('edit_type').value = movie.type;
            document.getElementById('edit_year').value = movie.year;
            document.getElementById('edit_synopsis').value = movie.synopsis || '';
            document.getElementById('edit_poster').value = movie.poster || '';
            document.getElementById('edit_banner').value = movie.banner || '';
            document.getElementById('edit_video_url').value = movie.video_url || '';
            openModal('editModal');
        }
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this movie?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>