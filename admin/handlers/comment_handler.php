<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/session_check.php';
checkAdminSession();

$allowed_roles = ['admin', 'editor'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    $_SESSION['error'] = 'Unauthorized access.';
    header('Location: ../comments.php');
    exit;
}

$action = $_REQUEST['action'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$redirect = '../comments.php';
if (!empty($_REQUEST['filter'])) {
    $redirect .= '?filter=' . urlencode($_REQUEST['filter']);
}

function redirect_comments($redirect)
{
    header('Location: ' . $redirect);
    exit;
}

if (in_array($action, ['approve', 'spam', 'trash', 'pending', 'delete'], true)) {
    // Prefer CSRF on POST; allow GET for approve shortcuts only with token in query when used from forms
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (
        empty($_SESSION['csrf_token']) ||
        empty($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $_SESSION['error'] = 'Security token mismatch.';
        redirect_comments($redirect);
    }

    if (!$id) {
        $_SESSION['error'] = 'Invalid comment.';
        redirect_comments($redirect);
    }

    try {
        if ($action === 'delete') {
            $stmt = $conn->prepare('DELETE FROM comments WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'Comment permanently deleted.';
        } else {
            $status_map = [
                'approve' => 'approved',
                'spam' => 'spam',
                'trash' => 'trash',
                'pending' => 'pending',
            ];
            $new_status = $status_map[$action];
            $stmt = $conn->prepare('UPDATE comments SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $new_status, ':id' => $id]);
            $_SESSION['success'] = 'Comment updated to "' . $new_status . '".';
        }
    } catch (PDOException $e) {
        error_log('Comment moderation error: ' . $e->getMessage());
        $_SESSION['error'] = 'Database error while updating comment.';
    }

    redirect_comments($redirect);
}

if ($action === 'bulk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $_SESSION['error'] = 'Security token mismatch.';
        redirect_comments($redirect);
    }

    $bulk_action = $_POST['bulk_action'] ?? '';
    $ids = isset($_POST['ids']) && is_array($_POST['ids'])
        ? array_filter(array_map('intval', $_POST['ids']))
        : [];

    if (empty($ids) || !in_array($bulk_action, ['approve', 'spam', 'trash', 'delete'], true)) {
        $_SESSION['error'] = 'Select at least one comment and a valid action.';
        redirect_comments($redirect);
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    try {
        if ($bulk_action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = count($ids) . ' comment(s) deleted.';
        } else {
            $status_map = [
                'approve' => 'approved',
                'spam' => 'spam',
                'trash' => 'trash',
            ];
            $status = $status_map[$bulk_action];
            $stmt = $conn->prepare("UPDATE comments SET status = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$status], $ids));
            $_SESSION['success'] = count($ids) . ' comment(s) marked as ' . $status . '.';
        }
    } catch (PDOException $e) {
        error_log('Comment bulk error: ' . $e->getMessage());
        $_SESSION['error'] = 'Bulk action failed.';
    }

    redirect_comments($redirect);
}

$_SESSION['error'] = 'Unknown action.';
redirect_comments($redirect);
