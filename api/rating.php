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
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $userId = $_SESSION['user_id'];

    if ($movieId <= 0 || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        exit;
    }

    $movie = querySingle("SELECT id FROM movies WHERE id = ?", [$movieId]);
    if (!$movie) {
        echo json_encode(['success' => false, 'message' => 'Movie not found']);
        exit;
    }

    $existing = querySingle("SELECT id FROM ratings WHERE user_id = ? AND movie_id = ?", [$userId, $movieId]);

    if ($existing) {
        execute("UPDATE ratings SET rating = ?, created_at = NOW() WHERE user_id = ? AND movie_id = ?", [$rating, $userId, $movieId]);
    } else {
        execute("INSERT INTO ratings (user_id, movie_id, rating) VALUES (?, ?, ?)", [$userId, $movieId, $rating]);
    }

    $avgData = querySingle("SELECT AVG(rating) as avg, COUNT(*) as count FROM ratings WHERE movie_id = ?", [$movieId]);
    echo json_encode(['success' => true, 'avg_rating' => $avgData['avg'] ? round($avgData['avg'], 1) : 0, 'rating_count' => $avgData['count']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>