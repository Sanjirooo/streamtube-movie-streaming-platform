<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = $_SESSION['user_id'];

// Check if file is uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File terlalu besar',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar',
        UPLOAD_ERR_PARTIAL => 'File upload tidak lengkap',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file dipilih',
    ];
    $error = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
    $message = $errorMessages[$error] ?? 'Upload gagal';
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$file = $_FILES['avatar'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$fileInfo = pathinfo($file['name']);
$extension = strtolower($fileInfo['extension']);
$mimeType = $file['type'];

if (!in_array($extension, $allowedExtensions) || !in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Hanya file gambar (JPG, PNG, GIF, WebP) yang diizinkan']);
    exit;
}

// Validate file size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File terlalu besar (maksimal 2MB)']);
    exit;
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/../assets/uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get current user to delete old avatar
$currentUser = querySingle("SELECT avatar FROM users WHERE id = ?", [$userId]);
$oldAvatar = $currentUser['avatar'] ?? null;

// Generate unique filename
$newFilename = 'user_' . $userId . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $newFilename;
$dbPath = 'assets/uploads/avatars/' . $newFilename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    // Delete old avatar if exists and is a local file
    if (!empty($oldAvatar) && strpos($oldAvatar, 'http') === false) {
        $oldFilePath = __DIR__ . '/../' . $oldAvatar;
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }

    // Update database
    $result = execute("UPDATE users SET avatar = ? WHERE id = ?", [$dbPath, $userId]);

    if ($result['success']) {
        // Update session name if needed
        echo json_encode([
            'success' => true,
            'message' => 'Avatar berhasil diupdate!',
            'avatar' => $dbPath
        ]);
    } else {
        // Delete uploaded file if database update failed
        unlink($uploadPath);
        echo json_encode(['success' => false, 'message' => 'Gagal update database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal upload file']);
}
?>