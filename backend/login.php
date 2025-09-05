<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

// Validate CSRF token
if (!validate_csrf_token($_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, full_name, password, role FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'] ?? 'customer';
        header("Location: dashboard.php");
        exit;
    } else {
        die("Invalid email or password.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
