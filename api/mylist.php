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
    $userId = $_SESSION['user_id'];

    if ($movieId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid movie']);
        exit;
    }

    $movie = querySingle("SELECT id FROM movies WHERE id = ?", [$movieId]);
    if (!$movie) {
        echo json_encode(['success' => false, 'message' => 'Movie not found']);
        exit;
    }

    $existing = querySingle("SELECT id FROM my_lists WHERE user_id = ? AND movie_id = ?", [$userId, $movieId]);

    if ($existing) {
        execute("DELETE FROM my_lists WHERE user_id = ? AND movie_id = ?", [$userId, $movieId]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        execute("INSERT INTO my_lists (user_id, movie_id) VALUES (?, ?)", [$userId, $movieId]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>