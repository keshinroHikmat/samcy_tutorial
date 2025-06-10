<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_registration";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle JSON input for registration
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    // Retrieve form data safely
    $id = uniqid();
    $firstName = $data['firstName'] ?? null;
    $lastName = $data['lastName'] ?? null;
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $maritalStatus = $data['maritalStatus'] ?? null;
    $dob = $data['dob'] ?? null;
    $state = $data['state'] ?? null;
    $localGovt = $data['localGovt'] ?? null;
    $address = $data['address'] ?? null;
    $nationality = $data['nationality'] ?? null;
    $nin = $data['nin'] ?? null;
    $department = $data['department'] ?? null;
    $gender = $data['gender'] ?? null;
    $privacyPolicy = $data['privacyPolicy'] ?? true;
    $role = $data['role'] ?? 'student';

    // Insert user into the database
    $insertSQL = "INSERT INTO users (id, firstName, lastName, email, phone, password, maritalStatus, dob, state, localGovt, address, nationality, nin, department, gender, privacyPolicy, role) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param("sssssssssssssssis", $id, $firstName, $lastName, $email, $phone, $password, $maritalStatus, $dob, $state, $localGovt, $address, $nationality, $nin, $department, $gender, $privacyPolicy, $role);

    if ($stmt->execute()) {
        echo "User registered successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid data received.";
}

$conn->close();
?>

