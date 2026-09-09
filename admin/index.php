<?php
session_start();

if (isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
