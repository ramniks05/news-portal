<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Token mismatch']);
    exit();
}

try {
    $conn->beginTransaction();


    $conn->exec("DELETE FROM site_sections WHERE section_key IN ('hero_featured', 'home_slider')");

    $stmt = $conn->prepare("INSERT INTO site_sections (section_key, post_id, sort_order) VALUES (?, ?, ?)");


    $hero = json_decode($_POST['hero'], true);
    foreach ($hero as $index => $post_id) {
        if ($post_id) $stmt->execute(['hero_featured', $post_id, $index]);
    }


    $slider = json_decode($_POST['slider'], true);
    foreach ($slider as $index => $post_id) {
        if ($post_id) $stmt->execute(['home_slider', $post_id, $index]);
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Homepage layout updated successfully!']);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
