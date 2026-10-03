<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'not_logged_in' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $userId = $_SESSION['user_id'];

    if ($movieId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid movie']);
        exit;
    }

    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }

    $movie = querySingle("SELECT id FROM movies WHERE id = ?", [$movieId]);
    if (!$movie) {
        echo json_encode(['success' => false, 'message' => 'Movie not found']);
        exit;
    }

    $result = execute("INSERT INTO comments (user_id, movie_id, comment) VALUES (?, ?, ?)", [$userId, $movieId, $comment]);

    if ($result['success']) {
        $user = querySingle("SELECT u.name, u.membership_status, mp.name as membership_name, mp.slug as membership_slug, mp.comment_banner as membership_comment_banner FROM users u LEFT JOIN membership_packages mp ON u.membership_package_id = mp.id WHERE u.id = ?", [$userId]);
        echo json_encode(['success' => true, 'comment' => [
            'id' => $result['insert_id'],
            'user_name' => $user['name'],
            'membership_name' => getMembershipName($user),
            'membership_slug' => $user['membership_slug'] ?? '',
            'has_membership' => hasActiveMembership($user),
            'membership_badge' => getMembershipBadge($user),
            'comment_banner' => hasActiveMembership($user) ? ($user['membership_comment_banner'] ?? '') : '',
            'comment' => htmlspecialchars($comment),
            'created_at' => date('d M Y, H:i')
        ]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save comment']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>