<?php
header("Content-Type: application/json");
require_once 'config/database.php';
require_once 'includes/functions.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$data = json_decode(file_get_contents("php://input"), true);
$requiredFields = ['firstName', 'lastName', 'email', 'phone', 'password', 'confirmPassword', 'maritalStatus', 'dob', 'state', 'localGovt', 'address', 'nationality', 'nin', 'department', 'gender', 'role', 'privacyPolicy'];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "$field is required"]);
        exit;
    }
}

$firstName = sanitize($data['firstName']);
$lastName = sanitize($data['lastName']);
$email = sanitize($data['email']);
$phone = sanitize($data['phone']);
$password = $data['password'];
$confirmPassword = $data['confirmPassword'];
$maritalStatus = sanitize($data['maritalStatus']);
$dob = sanitize($data['dob']);
$state = sanitize($data['state']);
$localGovt = sanitize($data['localGovt']);
$address = sanitize($data['address']);
$nationality = sanitize($data['nationality']);
$nin = sanitize($data['nin']);
$department = sanitize($data['department']);
$gender = sanitize($data['gender']);
$role = sanitize($data['role']);
$privacyPolicy = filter_var($data['privacyPolicy'], FILTER_VALIDATE_BOOLEAN);

if (!isValidEmail($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

if (!isStrongPassword($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters long']);
    exit;
}

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Validate role
$allowedRoles = ['admin', 'instructor', 'student', 'parent'];
$role = strtolower(sanitize($data['role']));
if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role selected']);
    exit;
}


// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

// Check if full name already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE firstName = ? AND lastName = ?");
$stmt->execute([$firstName, $lastName]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'A user with this name already exists']);
    exit;
}


// Check if phone number already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Phone number already exists']);
    exit;
}

// Check if NIN already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE nin = ?");
$stmt->execute([$nin]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'NIN already exists']);
    exit;
}

$userId = generateCustomUserId($pdo);


// Insert new user
$stmt = $pdo->prepare("INSERT INTO users (id, firstName, lastName, email, phone, password, maritalStatus, dob, state, localGovt, address, nationality, nin, department, gender, role, privacyPolicy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$userId, $firstName, $lastName, $email, $phone, $hashedPassword, $maritalStatus, $dob, $state, $localGovt, $address, $nationality, $nin, $department, $gender, $role, $privacyPolicy]);


// JWT generation
$payload = [
    "id" => $userId,
    "email" => $email,
    "role" => $role,
    "iat" => time(),
    "exp" => time() + (60 * 60 * 24 * 7) // 7 days
];

$jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

// Response
echo json_encode([
    "message" => "User registered successfully",
    "token" => $jwt,
    "user" => [
        "id" => $userId,
        "firstName" => $firstName,
        "lastName" => $lastName,
        "email" => $email,
        "role" => $role
    ]
]);
