<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "stdent_registration";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve user input
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

// Verify credentials
$sql = "SELECT id, role, password FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = $row['role'];
        echo json_encode(["message" => "Login successful!", "role" => $row['role']]);
    } else {
        echo json_encode(["message" => "Invalid password!"]);
    }
} else {
    echo json_encode(["message" => "User not found!"]);
}

$stmt->close();
$conn->close();
?>
