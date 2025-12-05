<?php
// Database functions using SQLite

function getDB() {
    $db_file = __DIR__ . '/julianfalk.dev.db';
    
    try {
        $db = new PDO('sqlite:' . $db_file);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create guestbook table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create visitor_count table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS visitor_count (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            count INTEGER NOT NULL DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        return $db;
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        return null;
    }
}

// Alias for backward compatibility
function getGuestbookDB() {
    return getDB();
}

function addGuestbookEntry($name, $message) {
    $db = getGuestbookDB();
    if (!$db) {
        return false;
    }
    
    try {
        // Sanitize input
        $name = trim($name);
        $message = trim($message);
        
        // Basic validation
        if (empty($name) || empty($message)) {
            return false;
        }
        
        // Limit length
        if (strlen($name) > 100 || strlen($message) > 1000) {
            return false;
        }
        
        $stmt = $db->prepare("INSERT INTO entries (name, message) VALUES (:name, :message)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->execute();
        
        return true;
    } catch (PDOException $e) {
        error_log("Guestbook Add Error: " . $e->getMessage());
        return false;
    }
}

function getGuestbookEntries($limit = 50) {
    $db = getGuestbookDB();
    if (!$db) {
        return [];
    }
    
    try {
        $stmt = $db->prepare("SELECT id, name, message, created_at FROM entries ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Guestbook Get Error: " . $e->getMessage());
        return [];
    }
}

function formatDate($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}
?>

