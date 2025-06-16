<?php
header("Content-Type: application/json");

require_once 'config/database.php';
require_once 'includes/functions.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$data = json_decode(file_get_contents("php://input"), true);

// Validate required inputs
if (empty($data['email']) || empty($data['password']) || empty($data['role'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email, password, and role are required']);
    exit;
}

$email = sanitize($data['email']);
$password = $data['password'];
$role = strtolower(sanitize($data['role'])); // normalize to lowercase
$rememberMe = isset($data['rememberMe']) && $data['rememberMe'] ? true : false;

// Allow only these 4 roles
$allowedRoles = ['admin', 'instructor', 'student', 'parent'];
if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

// Fetch user by email
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check email, password and role
if (!$user || !password_verify($password, $user['password']) || strtolower($user['role']) !== $role) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials or role']);
    exit;
}

// Set expiry time for JWT
$expiry = $rememberMe ? (60 * 60 * 24 * 7) : (60 * 60); // 1 hour or 7 days

$payload = [
    "id" => $user['id'],
    "email" => $user['email'],
    "role" => $user['role'],
    "iat" => time(),
    "exp" => time() + $expiry
];

$jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

// Successful login response
echo json_encode([
    "message" => "Login successful",
    "token" => $jwt,
    "user" => [
        "id" => $user['id'],
        "firstName" => $user['firstName'],
        "lastName" => $user['lastName'],
        "email" => $user['email'],
        "role" => $user['role']
    ]
]);
