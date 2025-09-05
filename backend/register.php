<?php
require_once 'config.php';
require_once 'helpers.php';

// Validate CSRF token
if (!validate_csrf_token($_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

// Get input safely
$full_name = trim($_POST['full_name']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$address = trim($_POST['address']);

// Server-side validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.");
}
if (strlen($password) < 8) {
    die("Password must be at least 8 characters.");
}

try {
    $pdo = getPDO();

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->rowCount() > 0) {
        die("Email is already registered.");
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, address) VALUES (:full_name, :email, :password, :address)");
    $stmt->execute([
        ':full_name' => $full_name,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':address' => $address
    ]);

    echo "Registration successful. You can now <a href='login.html'>login</a>.";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
