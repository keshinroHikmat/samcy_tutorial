<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_registration";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Collect user data
$data = json_decode(file_get_contents('php://input'), true);

$firstName = $data['firstName'] ?? null;
$lastName = $data['lastName'] ?? null;
$email = $data['email'] ?? null;
$phone = $data['phone'] ?? null;
$password = password_hash($data['password'], PASSWORD_BCRYPT);
$role = $data['role'] ?? 'student';


// Insert into database
$sql = "INSERT INTO users (id, firstName, lastName, email, phone, password, role) VALUES (UUID(), ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $password, $role);

if ($stmt->execute()) {
    echo "Registration successful!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
