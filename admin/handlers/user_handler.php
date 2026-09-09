<?php
session_start();
require_once '../../config/database.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../login.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';


function validatePassword($password, $confirm)
{
    if (empty($password)) return true;

    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if ($password !== $confirm) {
        return "Passwords do not match.";
    }
    return true;
}

function handleAvatarUpload($file)
{
    $upload_dir = "../../uploads/authors/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($ext, $allowed)) {
        $new_name = uniqid('user_') . "." . $ext;
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
            return "uploads/authors/$new_name";
        }
    }
    return null;
}

if ($action === 'create') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security token mismatch.";
        header("Location: ../authors.php");
        exit();
    }

    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS);
    $bio = trim(filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_SPECIAL_CHARS));
    $status = isset($_POST['status']) ? 1 : 0;
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (!$email || empty($name) || empty($password)) {
        $_SESSION['error'] = "Name, valid Email, and Password are required.";
        header("Location: ../authors.php");
        exit();
    }

    $pass_error = validatePassword($password, $confirm);
    if ($pass_error !== true) {
        $_SESSION['error'] = $pass_error;
        header("Location: ../authors.php");
        exit();
    }

    try {

        $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->rowCount() > 0) {
            $_SESSION['error'] = "Email address is already in use.";
            header("Location: ../authors.php");
            exit();
        }

        $avatar_path = handleAvatarUpload($_FILES['avatar'] ?? null);


        $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (name, email, password, role, bio, avatar, status) VALUES (:name, :email, :pass, :role, :bio, :avt, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':pass' => $hashed_pass,
            ':role' => $role,
            ':bio' => $bio,
            ':avt' => $avatar_path,
            ':status' => $status
        ]);

        $_SESSION['success'] = "New user created successfully.";
    } catch (PDOException $e) {
        error_log("User Create Error: " . $e->getMessage());
        $_SESSION['error'] = "Database error. Check logs.";
    }
    header("Location: ../authors.php");
    exit();
} elseif ($action === 'update') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security mismatch.";
        header("Location: ../authors.php");
        exit();
    }

    $id = (int)$_POST['user_id'];
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS);
    $bio = trim(filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_SPECIAL_CHARS));
    $status = isset($_POST['status']) ? 1 : 0;
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];


    $check = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
    $check->execute([':email' => $email, ':id' => $id]);
    if ($check->rowCount() > 0) {
        $_SESSION['error'] = "Email already taken by another user.";
        header("Location: ../authors.php");
        exit();
    }

    try {

        $sql = "UPDATE users SET name=:name, email=:email, role=:role, bio=:bio, status=:status";
        $params = [':name' => $name, ':email' => $email, ':role' => $role, ':bio' => $bio, ':status' => $status, ':id' => $id];


        if (!empty($password)) {
            $pass_error = validatePassword($password, $confirm);
            if ($pass_error !== true) {
                $_SESSION['error'] = $pass_error;
                header("Location: ../authors.php");
                exit();
            }
            $sql .= ", password=:pass";
            $params[':pass'] = password_hash($password, PASSWORD_BCRYPT);
        }


        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $new_avatar = handleAvatarUpload($_FILES['avatar']);
            if ($new_avatar) {
                $sql .= ", avatar=:avt";
                $params[':avt'] = $new_avatar;
            }
        }

        $sql .= " WHERE id=:id";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $_SESSION['success'] = "User profile updated successfully.";
    } catch (PDOException $e) {
        error_log("User Update Error: " . $e->getMessage());
        $_SESSION['error'] = "Update failed due to a database error.";
    }
    header("Location: ../authors.php");
    exit();
} elseif ($action === 'delete') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $current_id = $_SESSION['admin_id'];

    if ($id && $id !== 1 && $id !== $current_id) {
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = "User deleted.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Could not delete user. Possibly assigned posts exist.";
        }
    } else {
        $_SESSION['error'] = "Action prohibited: Cannot delete this account.";
    }
    header("Location: ../authors.php");
    exit();
}
