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

    $existing = querySingle("SELECT id FROM histories WHERE user_id = ? AND movie_id = ?", [$userId, $movieId]);

    if ($existing) {
        execute("UPDATE histories SET watched_at = NOW() WHERE user_id = ? AND movie_id = ?", [$userId, $movieId]);
    } else {
        execute("INSERT INTO histories (user_id, movie_id) VALUES (?, ?)", [$userId, $movieId]);
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>