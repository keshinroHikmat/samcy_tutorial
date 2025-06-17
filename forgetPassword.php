<?php
header("Content-Type: application/json");

require_once 'config/database.php';
require_once 'includes/functions.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"), true);
$email = sanitize($data['email'] ?? '');

if (!isValidEmail($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id, firstName FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'No user found with this email']);
    exit;
}

// Generate secure token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 60 * 30); // 30 minutes

// Store token in DB
$stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
$stmt->execute([$token, $expires, $user['id']]);

// Send email
$mail = new PHPMailer(true);
try {
    $resetLink = $_ENV['RESET_PASSWORD_URL'] . "?token=$token";

    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USER'];
    $mail->Password = $_ENV['MAIL_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $_ENV['MAIL_PORT'];

    $mail->setFrom($_ENV['MAIL_FROM'], 'Samcy Support');
    $mail->addReplyTo($_ENV['MAIL_FROM'], 'Samcy Support');
    $mail->addAddress($email, $user['firstName']);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body = "
    <html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
        <p>Hi {$user['firstName']},</p>
        <p>You requested to reset your password. Click the button below:</p>
        <p>
            <a href=\"$resetLink\" style=\"padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;\">
                Reset Password
            </a>
        </p>
        <p>If the button doesn't work, copy and paste this link into your browser:</p>
        <p><a href=\"$resetLink\">$resetLink</a></p>
        <p>This link expires in 30 minutes.</p>
        <p>Regards,<br>Samcy Support</p>
    </body>
    </html>
";
    $mail->AltBody = "Hi {$user['firstName']},\n\nClick the link to reset your password:\n$resetLink\n\nThis link expires in 30 minutes.";



    $mail->send();
    echo json_encode(['message' => 'Password reset link sent']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Email could not be sent.', 'details' => $mail->ErrorInfo]);
}
