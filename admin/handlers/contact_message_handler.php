<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/session_check.php';
checkAdminSession();

$allowed_roles = ['admin', 'editor'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    $_SESSION['error'] = 'Unauthorized access.';
    header('Location: ../contact-messages.php');
    exit;
}

$action = $_POST['action'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$filter = $_POST['filter'] ?? 'new';
$redirect = '../contact-messages.php?filter=' . urlencode($filter);

$token = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    empty($token) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    $_SESSION['error'] = 'Security token mismatch.';
    header('Location: ' . $redirect);
    exit;
}

if (!$id || !in_array($action, ['read', 'archive', 'delete', 'new'], true)) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: ' . $redirect);
    exit;
}

try {
    if ($action === 'delete') {
        $conn->prepare('DELETE FROM contact_messages WHERE id = :id')->execute([':id' => $id]);
        $_SESSION['success'] = 'Message deleted.';
    } else {
        $map = ['read' => 'read', 'archive' => 'archived', 'new' => 'new'];
        $conn->prepare('UPDATE contact_messages SET status = :s WHERE id = :id')
            ->execute([':s' => $map[$action], ':id' => $id]);
        $_SESSION['success'] = 'Message updated.';
    }
} catch (PDOException $e) {
    error_log('Contact message error: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error.';
}

header('Location: ' . $redirect);
exit;
