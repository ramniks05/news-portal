<?php
session_start();
require_once '../../config/database.php';


if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['admin_id'];
$action = $_POST['action'] ?? '';


if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Security token mismatch.";
    header("Location: ../profile.php");
    exit();
}


if ($action === 'update_info') {
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);

    if (empty($name)) {
        $_SESSION['error'] = "Name cannot be empty.";
        header("Location: ../profile.php");
        exit();
    }

    try {
        $conn->beginTransaction();

        $avatar_sql = "";
        $params = [':name' => $name, ':bio' => $bio, ':id' => $user_id];


        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "../../uploads/authors/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $new_name = "user_" . $user_id . "_" . time() . "." . $ext;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_name)) {
                    $avatar_sql = ", avatar = :avatar";
                    $params[':avatar'] = "uploads/authors/" . $new_name;


                    $_SESSION['admin_name'] = $name;
                }
            } else {
                $_SESSION['error'] = "Invalid image format.";
                header("Location: ../profile.php");
                exit();
            }
        }

        $sql = "UPDATE users SET name = :name, bio = :bio $avatar_sql WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $conn->commit();
        $_SESSION['success'] = "Profile updated successfully.";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Database error.";
    }

    header("Location: ../profile.php");
    exit();
}


if ($action === 'change_password') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($new) < 8) {
        $_SESSION['error'] = "New password must be at least 8 characters.";
        header("Location: ../profile.php");
        exit();
    }

    if ($new !== $confirm) {
        $_SESSION['error'] = "New passwords do not match.";
        header("Location: ../profile.php");
        exit();
    }

    try {

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current, $user['password'])) {

            $new_hash = password_hash($new, PASSWORD_BCRYPT);

            $update = $conn->prepare("UPDATE users SET password = :pass WHERE id = :id");
            $update->execute([':pass' => $new_hash, ':id' => $user_id]);


            session_unset();
            session_destroy();

            session_start();
            $_SESSION['success'] = "Password changed. Please login again.";
            header("Location: ../login.php");
            exit();
        } else {
            $_SESSION['error'] = "Incorrect current password.";
            header("Location: ../profile.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error.";
        header("Location: ../profile.php");
        exit();
    }
}
