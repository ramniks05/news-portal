<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/common_functions.php';

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../manage-posts.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security token mismatch. Please try again.";
        header("Location: ../add-post.php");
        exit();
    }

    require_once '../../helpers/common_functions.php';

    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = normalize_post_html($_POST['content'] ?? '');
    $summary = trim($_POST['summary']);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $image_credit = trim($_POST['image_credit'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $meta_title = trim($_POST['meta_title']);
    $meta_desc = trim($_POST['meta_description']);
    $tags_input = trim($_POST['tags']);

    if (empty($title) || empty($slug) || empty($category_id)) {
        $_SESSION['error'] = "Title, Permalink (Slug), and Category are required.";
        header("Location: ../add-post.php");
        exit();
    }

    try {
        $conn->beginTransaction();

        $featured_image_path = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $featured_image_path = handleImageUpload($_FILES['featured_image']);
        } else {
            $featured_image_path = normalizeFeaturedImageUrl($_POST['featured_image_url'] ?? '');
        }

        if ($featured_image_path === false) {
            $conn->rollBack();
            $_SESSION['error'] = "Invalid image URL. Use a full http:// or https:// link.";
            header("Location: ../add-post.php");
            exit();
        }

        $sql = "INSERT INTO posts (title, slug, category_id, content, summary, featured_image, image_credit, status, meta_title, meta_description, author_id, published_at) 
                VALUES (:title, :slug, :cat, :content, :summary, :img, :img_credit, :status, :m_title, :m_desc, :author, :pub_date)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':cat' => $category_id,
            ':content' => $content,
            ':summary' => $summary,
            ':img' => $featured_image_path,
            ':img_credit' => $image_credit,
            ':status' => $status,
            ':m_title' => $meta_title,
            ':m_desc' => $meta_desc,
            ':author' => $_SESSION['admin_id'],
            ':pub_date' => ($status === 'published') ? date('Y-m-d H:i:s') : null
        ]);

        $post_id = $conn->lastInsertId();

        processTags($conn, $post_id, $tags_input);

        $conn->commit();
        $_SESSION['success'] = "Article created successfully!";
        header("Location: ../manage-posts.php");
    } catch (PDOException $e) {
        $conn->rollBack();
        handleDbErrors($e, "../add-post.php");
    }
    exit();
} elseif ($action === 'update') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security token mismatch.";
        header("Location: ../manage-posts.php");
        exit();
    }

    $id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = normalize_post_html($_POST['content'] ?? '');
    $summary = trim($_POST['summary']);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $image_credit = trim($_POST['image_credit'] ?? '');
    $status = $_POST['status'];
    $meta_title = trim($_POST['meta_title']);
    $meta_desc = trim($_POST['meta_description']);
    $tags_input = trim($_POST['tags']);

    if (!$id || empty($title) || empty($slug)) {
        $_SESSION['error'] = "Invalid data provided.";
        header("Location: ../edit-post.php?id=$id");
        exit();
    }

    try {
        $conn->beginTransaction();

        $img_sql_part = "";
        $params = [
            ':title' => $title,
            ':slug' => $slug,
            ':cat' => $category_id,
            ':content' => $content,
            ':summary' => $summary,
            ':img_credit' => $image_credit,
            ':status' => $status,
            ':m_title' => $meta_title,
            ':m_desc' => $meta_desc,
            ':id' => $id
        ];

        $remove_image = isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] === '1';

        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $new_path = handleImageUpload($_FILES['featured_image']);
            if ($new_path) {
                $img_sql_part = ", featured_image = :img";
                $params[':img'] = $new_path;
            }
        } elseif ($remove_image) {
            $img_sql_part = ", featured_image = :img";
            $params[':img'] = null;
        } elseif (isset($_POST['featured_image_url'])) {
            $url_path = normalizeFeaturedImageUrl($_POST['featured_image_url']);
            if ($url_path === false) {
                $conn->rollBack();
                $_SESSION['error'] = "Invalid image URL. Use a full http:// or https:// link.";
                header("Location: ../edit-post.php?id=$id");
                exit();
            }
            // Empty URL with no upload = keep existing (unless remove flag)
            if ($url_path !== null) {
                $img_sql_part = ", featured_image = :img";
                $params[':img'] = $url_path;
            }
        }

        $pub_sql_part = "";
        if ($status === 'published') {
            // Set publish time if missing; keep existing published_at if already set
            $pub_sql_part = ", published_at = COALESCE(published_at, NOW())";
        }

        $sql = "UPDATE posts SET 
                title = :title, slug = :slug, category_id = :cat, 
                content = :content, summary = :summary, image_credit = :img_credit, status = :status, 
                meta_title = :m_title, meta_description = :m_desc 
                $img_sql_part
                $pub_sql_part
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $conn->prepare("DELETE FROM post_tags WHERE post_id = :pid")->execute([':pid' => $id]);
        processTags($conn, $id, $tags_input);

        $conn->commit();
        $_SESSION['success'] = "Article updated successfully.";
        header("Location: ../manage-posts.php");
    } catch (PDOException $e) {
        $conn->rollBack();
        handleDbErrors($e, "../edit-post.php?id=$id");
    }
    exit();
} elseif ($action === 'delete') {

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        try {
            $stmt = $conn->prepare("SELECT featured_image FROM posts WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $post = $stmt->fetch();

            if ($post && !empty($post['featured_image']) && !preg_match('#^https?://#i', $post['featured_image'])) {
                $file_path = "../../" . $post['featured_image'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $del_stmt = $conn->prepare("DELETE FROM posts WHERE id = :id");
            $del_stmt->execute([':id' => $id]);

            $_SESSION['success'] = "Post deleted successfully.";
        } catch (PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete post.";
        }
    }
    header("Location: ../manage-posts.php");
    exit();
}

function normalizeFeaturedImageUrl($url)
{
    $url = trim((string)$url);
    if ($url === '') {
        return null;
    }

    // Allow already-stored local relative paths from the form (edit safety)
    if (strpos($url, 'uploads/') === 0) {
        return $url;
    }

    if (!preg_match('#^https?://#i', $url)) {
        return false;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Block dangerous schemes already covered by http/https; limit length
    if (strlen($url) > 500) {
        return false;
    }

    return $url;
}

function handleImageUpload($file)
{
    $year = date('Y');
    $month = date('m');
    $upload_dir = "../../uploads/$year/$month/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (in_array($file_ext, $allowed)) {
        $new_name = time() . "_feat." . $file_ext;
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
            return "uploads/$year/$month/$new_name";
        }
    }
    return null;
}

function processTags($conn, $post_id, $tags_input)
{
    if (empty($tags_input)) return;

    $tags_array = array_map('trim', explode(',', $tags_input));

    foreach ($tags_array as $tag_name) {
        if (empty($tag_name)) continue;

        $tag_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $tag_name));
        $tag_slug = trim($tag_slug, '-');

        $tag_stmt = $conn->prepare("SELECT id FROM tags WHERE slug = :slug");
        $tag_stmt->execute([':slug' => $tag_slug]);
        $tag_row = $tag_stmt->fetch();

        if ($tag_row) {
            $tag_id = $tag_row['id'];
        } else {
            $ins_tag = $conn->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
            $ins_tag->execute([':name' => $tag_name, ':slug' => $tag_slug]);
            $tag_id = $conn->lastInsertId();
        }

        $pivot = $conn->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (:pid, :tid)");
        $pivot->execute([':pid' => $post_id, ':tid' => $tag_id]);
    }
}

function handleDbErrors($e, $redirect_url)
{
    error_log("Post DB Error: " . $e->getMessage());
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = "The URL Slug is already being used. Please create a unique slug.";
    } else {
        $_SESSION['error'] = "A system error occurred. Please try again.";
    }
    header("Location: $redirect_url");
    exit();
}
