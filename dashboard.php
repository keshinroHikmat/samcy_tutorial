<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["message" => "Access denied. Please log in."]));
}

if ($_SESSION['role'] === 'admin') {
    echo json_encode(["message" => "Welcome, Admin! You have full access."]);
} else {
    echo json_encode(["message" => "Welcome, Student! You have limited access."]);
}
?>
