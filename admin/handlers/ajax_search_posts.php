<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    exit(json_encode([]));
}

$q = $_GET['q'] ?? '';
$cat_id = $_GET['cat_id'] ?? '';

$sql = "SELECT id, title, featured_image, DATE_FORMAT(published_at, '%b %d, %Y') as pub_date 
        FROM posts WHERE status = 'published'";
$params = [];

if (!empty($cat_id)) {
    $sql .= " AND category_id = :cat_id";
    $params[':cat_id'] = $cat_id;
}

if (!empty($q)) {
    $sql .= " AND title LIKE :q";
    $params[':q'] = "%$q%";
}

$sql .= " ORDER BY published_at DESC LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as &$res) {
    $img = $res['featured_image'] ?? '';
    if (!empty($img) && preg_match('#^https?://#i', $img)) {
        // keep external CDN URL as-is
        continue;
    }
    if (!$img || !file_exists('../../' . $img)) {
        $res['featured_image'] = 'assets/images/static/placeholder.jpg';
    }
}

echo json_encode($results);
