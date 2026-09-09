<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../helpers/mail_helper.php';
require_once '../../helpers/email_template.php';


$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'subscribe') {
    header('Content-Type: application/json');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit();
    }

    try {
        $token = bin2hex(random_bytes(32));
        $unsub_token = bin2hex(random_bytes(32));

        $stmt = $conn->prepare("
            INSERT INTO newsletter_subscribers (email, ip_address, verification_token, unsubscribe_token, status, is_verified) 
            VALUES (:email, :ip, :token, :unsub, 'active', 0)
            ON DUPLICATE KEY UPDATE verification_token = VALUES(verification_token), unsubscribe_token = IFNULL(unsubscribe_token, VALUES(unsubscribe_token)), is_verified = 0, status = 'active'
        ");
        $stmt->execute([':email' => $email, ':ip' => $_SERVER['REMOTE_ADDR'], ':token' => $token, ':unsub' => $unsub_token]);


        $verifyLink = BASE_URL . "/newsletter-action.php?action=verify&token=$token";
        $subject = "Confirm your subscription";
        $body = get_email_template(
            "Welcome to " . SITE_NAME,
            "<p>Please click the button below to verify your email and start receiving news updates.</p>",
            "Verify My Email",
            $verifyLink
        );

        send_email($email, $subject, $body);

        echo json_encode(['status' => 'success', 'message' => 'Confirmation email sent. Please check your inbox.']);
    } catch (PDOException $e) {
        error_log("Subscribe Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'System error. Could not subscribe.']);
    }
    exit();
}


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If AJAX, send JSON error. If not, redirect.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    } else {
        $_SESSION['error'] = 'Unauthorized access.';
        header('Location: ../login.php');
    }
    exit();
}

if ($action === 'send_batch') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Security token mismatch.']);
        exit();
    }

    $offset = (int)$_POST['offset'];
    $limit = (int)$_POST['batch_size'];
    $subject = $_POST['subject'];
    $content = $_POST['content'];


    $stmt = $conn->prepare("SELECT email, unsubscribe_token FROM newsletter_subscribers WHERE status='active' AND is_verified=1 LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sent_count = 0;
    foreach ($subscribers as $sub) {
        $token = $sub['unsubscribe_token'] ?: '';
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $conn->prepare("UPDATE newsletter_subscribers SET unsubscribe_token = :t WHERE email = :e")
                ->execute([':t' => $token, ':e' => $sub['email']]);
        }
        $unsubLink = BASE_URL . "/newsletter-action.php?action=unsubscribe&token=" . $token;
        $full_body = get_email_template($subject, $content, "Read More", BASE_URL, $unsubLink);

        if (send_email($sub['email'], $subject, $full_body)) {
            $sent_count++;
        }
    }

    echo json_encode([
        'status' => 'success',
        'sent_in_batch' => $sent_count,
        'offset_processed' => $offset
    ]);
    exit();
}

if ($action === 'export') {
    $filename = "subscribers_export_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Email', 'Status', 'Is Verified', 'Subscribed Date']);

    $stmt = $conn->query("SELECT email, status, is_verified, created_at FROM newsletter_subscribers ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['is_verified'] = $row['is_verified'] ? 'Yes' : 'No';
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

if ($action === 'delete') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        try {
            $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = "Subscriber removed permanently.";
        } catch (PDOException $e) {
            error_log("Delete Subscriber Error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete subscriber.";
        }
    } else {
        $_SESSION['error'] = "Invalid subscriber ID.";
    }


    $referrer = $_SERVER['HTTP_REFERER'] ?? '../subscribers.php';
    header("Location: $referrer");
    exit();
}
