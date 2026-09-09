<?php
session_start();
require_once '../../config/database.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../settings.php");
    exit();
}


if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Security token mismatch. Please refresh and try again.";
    header("Location: ../settings.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../settings.php");
    exit();
}


try {
    $conn->beginTransaction();


    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) 
            ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($sql);

    $exclude_keys = [
        'csrf_token',
        'site_logo',
        'site_favicon',
        'fakeusernameremembered',
        'fakepasswordremembered'
    ];

    foreach ($_POST as $key => $value) {
        if (in_array($key, $exclude_keys)) continue;


        $clean_value = trim($value);

        $stmt->execute([
            ':key' => $key,
            ':value' => $clean_value
        ]);
    }



    $upload_dir = '../../uploads/static/';


    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }


    $file_fields = [
        'site_logo' => 'logo_path',
        'site_favicon' => 'favicon_path'
    ];

    foreach ($file_fields as $input_name => $db_key) {


        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {

            $file_tmp = $_FILES[$input_name]['tmp_name'];
            $file_name = $_FILES[$input_name]['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));


            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'ico'];

            if (in_array($file_ext, $allowed)) {

                $prefix = ($input_name === 'site_logo') ? 'logo' : 'favicon';
                $new_name = $prefix . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_name;


                if (move_uploaded_file($file_tmp, $target_path)) {


                    $old_stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
                    $old_stmt->execute([':key' => $db_key]);
                    $old_path = $old_stmt->fetchColumn();

                    if ($old_path && file_exists("../../" . $old_path)) {
                        unlink("../../" . $old_path);
                    }


                    $relative_path = 'uploads/static/' . $new_name;

                    $stmt->execute([
                        ':key' => $db_key,
                        ':value' => $relative_path
                    ]);
                }
            } else {

                $_SESSION['warning'] = "Some files were not uploaded due to invalid format.";
            }
        }
    }

    $conn->commit();
    $_SESSION['success'] = "System settings updated successfully.";
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Settings Update Error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to save settings. Database error.";
}


header("Location: ../settings.php");
exit();
