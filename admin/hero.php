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
        if ($_POST['action'] === 'save') {
            $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $movie_id = (int)$_POST['movie_id'];
            $title = trim($_POST['title']);
            $subtitle = trim($_POST['subtitle']);
            $banner = trim($_POST['banner']);
            $slide_order = (int)$_POST['slide_order'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($id) {
                $result = execute("UPDATE hero_slides SET movie_id = ?, title = ?, subtitle = ?, banner = ?, slide_order = ?, is_active = ? WHERE id = ?", [$movie_id, $title, $subtitle, $banner, $slide_order, $is_active, $id]);
            } else {
                $result = execute("INSERT INTO hero_slides (movie_id, title, subtitle, banner, slide_order, is_active) VALUES (?, ?, ?, ?, ?, ?)", [$movie_id, $title, $subtitle, $banner, $slide_order, $is_active]);
            }

            if ($result['success']) {
                $message = 'Slide saved successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to save slide.';
                $messageType = 'error';
            }
        }

        if ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $result = execute("DELETE FROM hero_slides WHERE id = ?", [$id]);
            if ($result['success']) {
                $message = 'Slide deleted successfully!';
                $messageType = 'success';
            }
        }
    }
}

$slides = queryAll("SELECT hs.*, m.title as movie_title, m.poster FROM hero_slides hs LEFT JOIN movies m ON hs.movie_id = m.id ORDER BY hs.slide_order ASC");
$movies = queryAll("SELECT id, title, type, poster FROM movies ORDER BY title ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Slider - Streamtube Admin</title>
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
                <li><a href="hero.php" class="active"><i class="fas fa-images"></i> <span>Hero Slider</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                <li><a href="comments.php"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
                <li><a href="membership_verification.php"><i class="fas fa-crown"></i> <span>Verifikasi Membership</span><?php if (($pendingMembershipCount['count'] ?? 0) > 0): ?><b class="admin-menu-badge"><?= $pendingMembershipCount['count'] ?></b><?php endif; ?></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <div><h1>Hero Slider</h1><p style="color: var(--text-secondary);">Manage the hero slides on the homepage</p></div>
                <button class="btn-play" onclick="openModal('addModal')" style="border: none; cursor: pointer;"><i class="fas fa-plus"></i> Add Slide</button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 20px; padding: 15px; background: var(--card-bg); border-radius: 8px; border-left: 4px solid var(--primary);">
                <i class="fas fa-info-circle"></i> The hero section displays up to 3 slides. Slides will be shown in order (1, 2, 3).
            </div>

            <div class="data-table">
                <table>
                    <thead><tr><th>Order</th><th>Preview</th><th>Title</th><th>Movie</th><th>Subtitle</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($slides as $slide): ?>
                            <tr>
                                <td><strong><?= $slide['slide_order'] ?></strong></td>
                                <td><img src="<?= getHeroBanner($slide, true) ?>" alt="" style="width: 80px; height: 45px; object-fit: cover; border-radius: 4px;" onerror="this.src='https://via.placeholder.com/80x45/e50914/ffffff?text=Banner'"></td>
                                <td><?= htmlspecialchars($slide['title']) ?></td>
                                <td><?= $slide['movie_title'] ? '<span style="font-size: 12px; color: var(--text-secondary);">'.htmlspecialchars($slide['movie_title']).'</span>' : '<span style="color: var(--text-muted);">No movie selected</span>' ?></td>
                                <td style="max-width: 200px;"><span style="color: var(--text-secondary); font-size: 12px;"><?= htmlspecialchars(substr($slide['subtitle'], 0, 50)) ?><?= strlen($slide['subtitle']) > 50 ? '...' : '' ?></span></td>
                                <td><?= $slide['is_active'] ? '<span style="color: var(--success);"><i class="fas fa-check-circle"></i> Active</span>' : '<span style="color: var(--text-muted);"><i class="fas fa-circle"></i> Inactive</span>' ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-action btn-edit" onclick="editSlide(<?= htmlspecialchars(json_encode($slide)) ?>)"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="btn-action btn-delete" onclick="confirmDeleteSlide(<?= $slide['id'] ?>)"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($slides) === 0): ?>
                            <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">No slides created yet. Click "Add Slide" to create one.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header"><h2><i class="fas fa-images"></i> <span id="modalTitle">Add New Slide</span></h2><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="slide_id">
                <div class="admin-form">
                    <div class="form-row">
                        <div class="form-group"><label>Select Movie</label><select name="movie_id" id="slide_movie_id" onchange="updateSlideFromMovie()"><option value="">-- Select Movie --</option><?php foreach ($movies as $movie): ?><option value="<?= $movie['id'] ?>"><?= htmlspecialchars($movie['title']) ?> (<?= ucfirst($movie['type']) ?>)</option><?php endforeach; ?></select></div>
                        <div class="form-group"><label>Slide Order (1-3)</label><select name="slide_order" id="slide_order"><option value="1">1 - First</option><option value="2">2 - Second</option><option value="3">3 - Third</option></select></div>
                    </div>
                    <div class="form-group full-width"><label>Slide Title</label><input type="text" name="title" id="slide_title" required></div>
                    <div class="form-group full-width"><label>Subtitle / Short Synopsis</label><textarea name="subtitle" id="slide_subtitle" rows="2"></textarea></div>
                    <div class="form-group full-width"><label>Banner Image URL</label><input type="url" name="banner" id="slide_banner" placeholder="https://..."></div>
                    <div class="form-group"><label><input type="checkbox" name="is_active" id="slide_is_active" checked> Active (Show on homepage)</label></div>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Slide</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" id="deleteForm" style="display: none;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_slide_id"></form>

    <script src="../assets/js/script.js"></script>
    <script>
        function editSlide(slide) {
            document.getElementById('modalTitle').textContent = 'Edit Slide';
            document.getElementById('slide_id').value = slide.id;
            document.getElementById('slide_movie_id').value = slide.movie_id || '';
            document.getElementById('slide_title').value = slide.title || '';
            document.getElementById('slide_subtitle').value = slide.subtitle || '';
            document.getElementById('slide_banner').value = slide.banner || '';
            document.getElementById('slide_order').value = slide.slide_order;
            document.getElementById('slide_is_active').checked = slide.is_active == 1;
            openModal('addModal');
        }
        function updateSlideFromMovie() {
            const movieSelect = document.getElementById('slide_movie_id');
            const selectedOption = movieSelect.options[movieSelect.selectedIndex];
            document.getElementById('slide_title').value = selectedOption ? selectedOption.text.split(' (')[0] : '';
        }
        function confirmDeleteSlide(id) {
            if (confirm('Are you sure you want to delete this slide?')) {
                document.getElementById('delete_slide_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>