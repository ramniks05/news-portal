<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../ads-manager.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create' || $action === 'update') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security token mismatch. Please try again.";
        header("Location: ../ads-manager.php");
        exit();
    }

    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name']);
    $location = $_POST['location'];
    $type = $_POST['type'];
    $url = trim($_POST['destination_url']);
    $code = $_POST['code'];
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($name)) {
        $_SESSION['error'] = "Ad Name is required.";
        header("Location: ../ads-manager.php");
        exit();
    }

    try {
        $image_path = null;
        $sql_img_update = "";

        if ($type === 'image' && isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {

            if ($_FILES['ad_image']['size'] > 102400) {
                $_SESSION['error'] = "File too large! Max limit is 100KB.";
                header("Location: ../ads-manager.php");
                exit();
            }

            $upload_dir = "../../uploads/ads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_name = $_FILES['ad_image']['name'];
            $file_tmp = $_FILES['ad_image']['tmp_name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed_exts)) {

                $new_name = "ad_" . time() . "_" . uniqid() . "." . $ext;
                $target_file = $upload_dir . $new_name;

                if (move_uploaded_file($file_tmp, $target_file)) {

                    $image_path = "uploads/ads/" . $new_name;
                    $sql_img_update = ", image_path = :img";

                    if ($action === 'update' && $id) {
                        $old_stmt = $conn->prepare("SELECT image_path FROM ads WHERE id = :id");
                        $old_stmt->execute([':id' => $id]);
                        $old_row = $old_stmt->fetch();
                        if ($old_row && !empty($old_row['image_path'])) {
                            $old_file = "../../" . $old_row['image_path'];
                            if (file_exists($old_file)) unlink($old_file);
                        }
                    }
                }
            } else {
                $_SESSION['error'] = "Invalid file type. Only JPG, PNG, GIF, WEBP allowed.";
                header("Location: ../ads-manager.php");
                exit();
            }
        }

        if ($action === 'create') {
            $sql = "INSERT INTO ads (name, location, type, image_path, destination_url, code, status) 
                    VALUES (:name, :loc, :type, :img, :url, :code, :status)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':loc' => $location,
                ':type' => $type,
                ':img' => $image_path,
                ':url' => $url,
                ':code' => $code,
                ':status' => $status
            ]);
            $_SESSION['success'] = "Advertisement created successfully.";
        } elseif ($action === 'update' && $id) {
            $sql = "UPDATE ads SET name=:name, location=:loc, type=:type, destination_url=:url, code=:code, status=:status $sql_img_update WHERE id=:id";

            $params = [
                ':name' => $name,
                ':loc' => $location,
                ':type' => $type,
                ':url' => $url,
                ':code' => $code,
                ':status' => $status,
                ':id' => $id
            ];

            if ($image_path) {
                $params[':img'] = $image_path;
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $_SESSION['success'] = "Advertisement updated successfully.";
        }
    } catch (PDOException $e) {
        error_log("Ad Handler Error: " . $e->getMessage());
        $_SESSION['error'] = "Database Error. Please check inputs.";
    }
} elseif ($action === 'toggle_status') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security Token Mismatch.";
        header("Location: ../ads-manager.php");
        exit();
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $current = $_POST['current_status'];

    if ($id) {
        $new_status = ($current == 1) ? 0 : 1;
        $stmt = $conn->prepare("UPDATE ads SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $id]);

        $_SESSION['success'] = "Ad status updated.";
    }
} elseif ($action === 'delete') {

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $conn->prepare("SELECT image_path FROM ads WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row && !empty($row['image_path'])) {
            $path = "../../" . $row['image_path'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $del_stmt = $conn->prepare("DELETE FROM ads WHERE id = :id");
        $del_stmt->execute([':id' => $id]);

        $_SESSION['success'] = "Advertisement deleted permanently.";
    }
}

header("Location: ../ads-manager.php");
exit();
