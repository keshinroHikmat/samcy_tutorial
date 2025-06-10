<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please login.");
}

if ($_SESSION['role'] === 'admin') {
    echo "<h1>Welcome, Admin!</h1>";
    echo "<p>You have full access.</p>";
} else {
    echo "<h1>Welcome, Student!</h1>";
    echo "<p>You have limited access.</p>";
}
?>
