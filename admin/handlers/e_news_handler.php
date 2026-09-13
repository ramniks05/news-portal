<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/session_check.php';
checkAdminSession();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'editor'], true)) {
    $_SESSION['error'] = 'Unauthorized.';
    header('Location: ../e-news.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['error'] = 'Security token mismatch.';
    header('Location: ../e-news.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $edition_date = trim($_POST['edition_date'] ?? '');
        $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

        if ($title === '' || $edition_date === '') {
            throw new Exception('Title and date are required.');
        }
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please upload a PDF file.');
        }

        $pdf = $_FILES['pdf_file'];
        $ext = strtolower(pathinfo($pdf['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $pdf['tmp_name']);
        finfo_close($finfo);
        if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            throw new Exception('Only PDF files are allowed.');
        }
        if ($pdf['size'] > 25 * 1024 * 1024) {
            throw new Exception('PDF too large (max 25MB).');
        }

        $dir = '../../uploads/e-news/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new Exception('Could not create upload folder.');
        }
        $pdf_name = 'enews_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $pdf_rel = 'uploads/e-news/' . date('Y/m') . '/' . $pdf_name;
        if (!move_uploaded_file($pdf['tmp_name'], '../../' . $pdf_rel)) {
            throw new Exception('Failed to save PDF.');
        }

        $cover_rel = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $img = $_FILES['cover_image'];
            $iext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
            if (in_array($iext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $cover_name = 'cover_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $iext;
                $cover_rel = 'uploads/e-news/' . date('Y/m') . '/' . $cover_name;
                move_uploaded_file($img['tmp_name'], '../../' . $cover_rel);
            }
        }

        $stmt = $conn->prepare("INSERT INTO e_news_editions (title, edition_date, pdf_path, cover_image, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $edition_date, $pdf_rel, $cover_rel, $status]);
        $_SESSION['success'] = 'E-News edition uploaded.';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT pdf_path, cover_image FROM e_news_editions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            foreach (['pdf_path', 'cover_image'] as $k) {
                if (!empty($row[$k]) && is_file('../../' . $row[$k])) {
                    @unlink('../../' . $row[$k]);
                }
            }
            $conn->prepare('DELETE FROM e_news_editions WHERE id = ?')->execute([$id]);
            $_SESSION['success'] = 'Edition deleted.';
        }
    }
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../e-news.php');
exit;
