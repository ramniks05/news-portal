<?php
require_once 'config/database.php';
require_once 'config/constants.php';

$action = $_GET['action'] ?? '';
$token = $_GET['token'] ?? '';
$message = '';
$sub_message = '';
$icon = '';
$bg_color = '';
$text_color = '';
$type = 'error';

if ($token) {
    if ($action === 'verify') {
        $stmt = $conn->prepare("UPDATE newsletter_subscribers SET is_verified = 1, verification_token = NULL WHERE verification_token = :token");
        $stmt->execute([':token' => $token]);

        if ($stmt->rowCount() > 0) {
            $type = 'success';
            $icon = 'fa-circle-check';
            $message = 'Email Verified Successfully!';
            $sub_message = 'Thank you for subscribing. You will now receive our latest updates directly in your inbox.';
            $bg_color = 'bg-green-50';
            $text_color = 'text-green-600';
        } else {
            $message = 'Link Expired or Invalid';
            $sub_message = 'This verification link may have already been used or is broken.';
            $icon = 'fa-circle-xmark';
            $bg_color = 'bg-red-50';
            $text_color = 'text-red-600';
        }
    } elseif ($action === 'unsubscribe') {
        $stmt = $conn->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed' WHERE unsubscribe_token = :token AND status = 'active'");
        $stmt->execute([':token' => $token]);

        if ($stmt->rowCount() > 0) {
            $type = 'success';
            $icon = 'fa-bell-slash';
            $message = 'Unsubscribed Successfully';
            $sub_message = 'We are sorry to see you go. You have been removed from our mailing list.';
            $bg_color = 'bg-slate-50';
            $text_color = 'text-slate-600';
        } else {
            $message = 'Invalid Request';
            $sub_message = 'We could not find your subscription or you are already unsubscribed.';
            $icon = 'fa-circle-exclamation';
            $bg_color = 'bg-amber-50';
            $text_color = 'text-amber-600';
        }
    }
} else {
    $message = 'Missing Token';
    $sub_message = 'The link you followed is incomplete.';
    $icon = 'fa-link-slash';
    $bg_color = 'bg-red-50';
    $text_color = 'text-red-600';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $message ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="./assets/src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
        <div class="h-2 w-full <?= ($type == 'success' && $action != 'unsubscribe') ? 'bg-green-500' : 'bg-indigo-600' ?>"></div>

        <div class="p-8 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full mb-6 <?= $bg_color ?>">
                <i class="fa-solid <?= $icon ?> text-4xl <?= $text_color ?>"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2"><?= $message ?></h1>
            <p class="text-gray-500 text-sm leading-relaxed mb-8">
                <?= $sub_message ?>
            </p>
            <a href="<?= BASE_URL ?>"
                class="inline-flex items-center justify-center w-full px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm">
                <i class="fa-solid fa-house mr-2"></i> Return to Homepage
            </a>

            <?php if ($action === 'unsubscribe' && $type === 'success'): ?>
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400">Did this by mistake?</p>
                    <a href="#" onclick="alert('Please re-subscribe from the homepage footer.'); return false;" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">Re-subscribe</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.<br>
                Developed by <a href="https://digitalcreatorss.com" class="text-indigo-500 hover:underline">Digital Creatorss</a>
            </p>
        </div>

    </div>

</body>

</html>