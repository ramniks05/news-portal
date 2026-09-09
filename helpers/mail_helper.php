<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/common_functions.php';

function send_email($to, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = get_setting('smtp_host');
        $mail->SMTPAuth   = true;
        $mail->Username   = get_setting('smtp_user');
        $mail->Password   = get_setting('smtp_pass');
        $mail->SMTPSecure = get_setting('smtp_encryption');
        $mail->Port       = get_setting('smtp_port');

        $mail->setFrom(get_setting('smtp_from_email'), get_setting('smtp_from_name'));
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
