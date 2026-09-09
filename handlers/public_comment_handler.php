<?php
/**
 * Public comment submission endpoint.
 * Comments are stored as pending and require admin approval.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/common_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'submit_comment') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    exit;
}

// CSRF
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Security check failed. Refresh the page and try again.']);
    exit;
}

// Honeypot — bots fill this; humans leave it empty
if (!empty($_POST['website'])) {
    echo json_encode(['status' => 'success', 'message' => 'Thank you! Your comment is awaiting moderation.']);
    exit;
}

$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$content = trim($_POST['content'] ?? '');

if (!$post_id || $name === '' || $email === '' || $content === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    echo json_encode(['status' => 'error', 'message' => 'Name must be between 2 and 100 characters.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit;
}

if (mb_strlen($content) < 5 || mb_strlen($content) > 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Comment must be between 5 and 2000 characters.']);
    exit;
}

// Strip tags — comments are plain text only
$name = strip_tags($name);
$content = strip_tags($content);
$email = strtolower($email);

try {
    // Ensure post exists and is published
    $post_stmt = $conn->prepare("SELECT id FROM posts WHERE id = :id AND status = 'published' LIMIT 1");
    $post_stmt->execute([':id' => $post_id]);
    if (!$post_stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Article not found.']);
        exit;
    }

    $ip = get_client_ip();
    // Keep only a reasonable IP string (avoid spoofed long headers)
    if (is_string($ip) && strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    $ip = substr((string)$ip, 0, 45);

    // Rate limit: max 1 comment per post per IP every 2 minutes
    $rate = $conn->prepare("
        SELECT COUNT(*) FROM comments 
        WHERE post_id = :pid AND ip_address = :ip AND created_at > (NOW() - INTERVAL 2 MINUTE)
    ");
    $rate->execute([':pid' => $post_id, ':ip' => $ip]);
    if ((int)$rate->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please wait a moment before posting another comment.']);
        exit;
    }

    // Rate limit: max 5 comments per IP per hour
    $hourly = $conn->prepare("
        SELECT COUNT(*) FROM comments 
        WHERE ip_address = :ip AND created_at > (NOW() - INTERVAL 1 HOUR)
    ");
    $hourly->execute([':ip' => $ip]);
    if ((int)$hourly->fetchColumn() >= 5) {
        echo json_encode(['status' => 'error', 'message' => 'Comment limit reached. Please try again later.']);
        exit;
    }

    $insert = $conn->prepare("
        INSERT INTO comments (post_id, parent_id, name, email, content, status, ip_address)
        VALUES (:post_id, 0, :name, :email, :content, 'pending', :ip)
    ");
    $insert->execute([
        ':post_id' => $post_id,
        ':name' => $name,
        ':email' => $email,
        ':content' => $content,
        ':ip' => $ip,
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you! Your comment was submitted and is awaiting moderation.'
    ]);
} catch (PDOException $e) {
    error_log('Comment submit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not save your comment. Please try again.']);
}
