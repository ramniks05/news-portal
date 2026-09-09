<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAdminSession()
{

    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php?error=unauthorized");
        exit();
    }


    $allowed_roles = ['admin', 'editor', 'author'];

    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {

        session_unset();
        session_destroy();
        header("Location: login.php?error=unauthorized");
        exit();
    }


    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=timeout");
        exit();
    }


    $_SESSION['last_activity'] = time();
}
