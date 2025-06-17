<?php
header("Content-Type: application/json");

require_once 'config/database.php';
require_once 'includes/functions.php';

$data = json_decode(file_get_contents("php://input"), true);

$token = $data['token'] ?? '';
$newPassword = $data['newPassword'] ?? '';
$confirmPassword = $data['confirmPassword'] ?? '';

if (!$token || !$newPassword || !$confirmPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

if (!isStrongPassword($newPassword)) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

// Check token validity
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Hash new password
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

// Update password and clear reset token
$stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
$stmt->execute([$hashedPassword, $user['id']]);

echo json_encode(['message' => 'Password reset successfully']);
