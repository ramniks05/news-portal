<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: ../login.php?error=csrf");
    exit();
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: ../login.php?error=empty");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        header("Location: ../dashboard.php");
        exit();
    } else {
        header("Location: ../login.php?error=invalid");
        exit();
    }
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    header("Location: ../login.php?error=system");
    exit();
}
