<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Token mismatch.";
        header("Location: ../dashboard.php");
        exit();
    }

    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $slug = $_POST['slug'];

    try {
        $stmt = $conn->prepare("UPDATE pages SET title = :title, content = :content WHERE id = :id");
        $stmt->execute([':title' => $title, ':content' => $content, ':id' => $id]);

        $_SESSION['success'] = "Page updated successfully.";
        header("Location: ../edit-page.php?slug=" . $slug);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error.";
        header("Location: ../edit-page.php?slug=" . $slug);
    }
}
