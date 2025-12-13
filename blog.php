<?php
// Blog helper functions backed by SQLite

require_once __DIR__ . '/guestbook.php';

function slugifyTitle($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug ?: 'post';
}

function ensureBlogTable($db) {
    // Create blog_posts table if missing
    $db->exec("CREATE TABLE IF NOT EXISTS blog_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

function getBlogPostsByYear() {
    $db = getDB();
    if (!$db) {
        return [];
    }

    try {
        ensureBlogTable($db);
        $stmt = $db->query("SELECT id, title, content, created_at FROM blog_posts ORDER BY created_at DESC");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($posts as $post) {
            $year = date('Y', strtotime($post['created_at']));
            if (!isset($grouped[$year])) {
                $grouped[$year] = [];
            }
            $grouped[$year][] = $post;
        }

        return $grouped;
    } catch (PDOException $e) {
        error_log("Blog fetch error: " . $e->getMessage());
        return [];
    }
}

function getBlogPostBySlug($slug) {
    $db = getDB();
    if (!$db) {
        return null;
    }

    try {
        ensureBlogTable($db);
        $stmt = $db->query("SELECT id, title, content, created_at FROM blog_posts");

        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (slugifyTitle($post['title']) === $slug) {
                return $post;
            }
        }

        return null;
    } catch (PDOException $e) {
        error_log("Blog fetch by slug error: " . $e->getMessage());
        return null;
    }
}

function addBlogPost($title, $content) {
    $db = getDB();
    if (!$db) {
        return false;
    }

    try {
        ensureBlogTable($db);

        $title = trim($title);
        $content = trim($content);

        if (empty($title) || empty($content)) {
            return false;
        }

        if (strlen($title) > 200) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO blog_posts (title, content) VALUES (:title, :content)");
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        error_log("Blog insert error: " . $e->getMessage());
        return false;
    }
}

function isBlogAdmin() {
    // Counter.php starts the session earlier in the request
    $envKey = getenv('BLOG_ADMIN_KEY') ?: '';
    if (!$envKey) {
        return false;
    }

    if (!empty($_SESSION['blog_admin']) && $_SESSION['blog_admin'] === true) {
        return true;
    }

    if (!isset($_POST['admin_key'])) {
        return false;
    }

    $provided = trim($_POST['admin_key']);

    if ($provided !== '' && hash_equals($envKey, $provided)) {
        $_SESSION['blog_admin'] = true;
        return true;
    }

    return false;
}
