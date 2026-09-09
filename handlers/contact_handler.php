<?php
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/common_functions.php';
require_once __DIR__ . '/../helpers/mail_helper.php';
require_once __DIR__ . '/../helpers/email_template.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Security check failed. Refresh and try again.']);
    exit;
}

// Honeypot
if (!empty($_POST['website'])) {
    echo json_encode(['status' => 'success', 'message' => 'Thanks! Your message has been sent.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in name, email, and message.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit;
}

if (mb_strlen($name) > 100 || mb_strlen($subject) > 200 || mb_strlen($message) > 5000) {
    echo json_encode(['status' => 'error', 'message' => 'One or more fields are too long.']);
    exit;
}

$name = strip_tags($name);
$subject = strip_tags($subject);
$message = strip_tags($message);
if ($subject === '') {
    $subject = 'Website contact message';
}

$ip = get_client_ip();
if (is_string($ip) && strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}
$ip = substr((string)$ip, 0, 45);

try {
    // Rate limit: max 3 messages / hour / IP
    $rate = $conn->prepare("SELECT COUNT(*) FROM contact_messages WHERE ip_address = :ip AND created_at > (NOW() - INTERVAL 1 HOUR)");
    $rate->execute([':ip' => $ip]);
    if ((int)$rate->fetchColumn() >= 3) {
        echo json_encode(['status' => 'error', 'message' => 'Too many messages. Please try again later.']);
        exit;
    }

    $ins = $conn->prepare("
        INSERT INTO contact_messages (name, email, subject, message, ip_address, status)
        VALUES (:name, :email, :subject, :message, :ip, 'new')
    ");
    $ins->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip' => $ip,
    ]);

    $to = get_setting('contact_email');
    if (empty($to)) {
        $to = get_setting('smtp_from_email');
    }

    if (!empty($to) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $body = get_email_template(
            'New contact message',
            '<p><strong>From:</strong> ' . htmlspecialchars($name) . ' (' . htmlspecialchars($email) . ')</p>' .
            '<p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>' .
            '<p>' . nl2br(htmlspecialchars($message)) . '</p>',
            'Open Admin',
            BASE_URL . '/admin/'
        );
        @send_email($to, '[Contact] ' . $subject, $body);
    }

    echo json_encode(['status' => 'success', 'message' => 'Thanks! Your message has been sent. We will get back to you soon.']);
} catch (PDOException $e) {
    error_log('Contact form error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not send your message. Please try again.']);
}
