<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../categories.php");
    exit();
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'create' || $action === 'update') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security token mismatch.";
        header("Location: ../categories.php");
        exit();
    }

    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $color = $_POST['color'] ?? '#6366f1';
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $show_on_menu = isset($_POST['show_on_menu']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

    if (empty($name) || empty($slug)) {
        $_SESSION['error'] = "Name and Slug are required.";
        header("Location: ../categories.php");
        exit();
    }

    // Only allow top-level categories as parents (1-level nesting)
    if ($parent_id > 0) {
        $pcheck = $conn->prepare("SELECT id, parent_id FROM categories WHERE id = :id AND status = 1 LIMIT 1");
        $pcheck->execute([':id' => $parent_id]);
        $parent = $pcheck->fetch(PDO::FETCH_ASSOC);

        if (!$parent) {
            $_SESSION['error'] = "Selected parent category was not found.";
            header("Location: ../categories.php");
            exit();
        }

        if ((int)$parent['parent_id'] !== 0) {
            $_SESSION['error'] = "Only top-level categories can be parents (one level of subcategories).";
            header("Location: ../categories.php");
            exit();
        }

        if ($id && $parent_id === $id) {
            $_SESSION['error'] = "A category cannot be its own parent.";
            header("Location: ../categories.php");
            exit();
        }
    }

    try {
        if ($action === 'create') {
            $check = $conn->prepare("SELECT id FROM categories WHERE slug = :slug");
            $check->execute([':slug' => $slug]);
            if ($check->rowCount() > 0) {
                $_SESSION['error'] = "Slug already exists. Please choose a unique one.";
                header("Location: ../categories.php");
                exit();
            }

            $sql = "INSERT INTO categories (name, slug, description, color, parent_id, show_on_menu, status) 
                    VALUES (:name, :slug, :desc, :color, :parent, :menu, :status)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':desc' => $description,
                ':color' => $color,
                ':parent' => $parent_id,
                ':menu' => $show_on_menu,
                ':status' => $status
            ]);

            $_SESSION['success'] = "Category added successfully.";
        } elseif ($action === 'update' && $id) {

            $check = $conn->prepare("SELECT id FROM categories WHERE slug = :slug AND id != :id");
            $check->execute([':slug' => $slug, ':id' => $id]);
            if ($check->rowCount() > 0) {
                $_SESSION['error'] = "Slug already exists on another category.";
                header("Location: ../categories.php");
                exit();
            }

            // If this category has children, it must stay top-level
            if ($parent_id > 0) {
                $child_check = $conn->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = :id");
                $child_check->execute([':id' => $id]);
                if ((int)$child_check->fetchColumn() > 0) {
                    $_SESSION['error'] = "This category has subcategories. Remove or reassign them before nesting it under another category.";
                    header("Location: ../categories.php");
                    exit();
                }
            }

            $sql = "UPDATE categories SET name=:name, slug=:slug, description=:desc, 
                    color=:color, parent_id=:parent, show_on_menu=:menu, status=:status WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':desc' => $description,
                ':color' => $color,
                ':parent' => $parent_id,
                ':menu' => $show_on_menu,
                ':status' => $status,
                ':id' => $id
            ]);

            $_SESSION['success'] = "Category updated successfully.";
        }
    } catch (PDOException $e) {
        error_log("Category Error: " . $e->getMessage());
        $_SESSION['error'] = "Database error occurred.";
    }
}

if ($action === 'delete') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        // Promote children to top-level instead of orphaning them
        $reparent = $conn->prepare("UPDATE categories SET parent_id = 0 WHERE parent_id = :id");
        $reparent->execute([':id' => $id]);

        $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['success'] = "Category deleted. Any subcategories were moved to top-level.";
    }
}

header("Location: ../categories.php");
exit();
