<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'editor')) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../breaking-news.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create' || $action === 'update') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security token mismatch.";
        header("Location: ../breaking-news.php");
        exit();
    }

    $title = trim($_POST['title']);
    $link = trim($_POST['link']);
    $status = isset($_POST['status']) ? 1 : 0;
    $id = $_POST['id'] ?? null;

    if (empty($title)) {
        $_SESSION['error'] = "Headline text is required.";
        header("Location: ../breaking-news.php");
        exit();
    }

    try {
        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO breaking_news (title, link, status) VALUES (:title, :link, :status)");
            $stmt->execute([':title' => $title, ':link' => $link, ':status' => $status]);
            $_SESSION['success'] = "Ticker added successfully.";
        } elseif ($action === 'update' && $id) {
            $stmt = $conn->prepare("UPDATE breaking_news SET title = :title, link = :link, status = :status WHERE id = :id");
            $stmt->execute([':title' => $title, ':link' => $link, ':status' => $status, ':id' => $id]);
            $_SESSION['success'] = "Ticker updated successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error.";
    }
}

if ($action === 'toggle_status') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $current = $_POST['current_status'];

    if ($id) {
        $new_status = ($current == 1) ? 0 : 1;
        $stmt = $conn->prepare("UPDATE breaking_news SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $id]);
        $_SESSION['success'] = "Status updated.";
    }
}

if ($action === 'delete') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM breaking_news WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['success'] = "Item deleted.";
    }
}

header("Location: ../breaking-news.php");
exit();
