<?php
// Visitor counter function using SQLite database

function getVisitorCount() {
    // Include database functions
    require_once __DIR__ . '/guestbook.php';
    
    $db = getDB();
    if (!$db) {
        // Fallback: try to read from old text file if database fails
        return getVisitorCountFromFile();
    }
    
    try {
        // Initialize counter if it doesn't exist
        $stmt = $db->query("SELECT COUNT(*) FROM visitor_count");
        if ($stmt->fetchColumn() == 0) {
            // Migrate from text file if it exists
            $old_count = getVisitorCountFromFile();
            $db->exec("INSERT INTO visitor_count (id, count) VALUES (1, $old_count)");
        }
        
        // Get current count and increment
        $db->beginTransaction();
        
        $stmt = $db->prepare("SELECT count FROM visitor_count WHERE id = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $count = (int)$result['count'] + 1;
        } else {
            $count = 1;
        }
        
        // Update count atomically
        $stmt = $db->prepare("INSERT OR REPLACE INTO visitor_count (id, count, updated_at) VALUES (1, :count, CURRENT_TIMESTAMP)");
        $stmt->bindValue(':count', $count, PDO::PARAM_INT);
        $stmt->execute();
        
        $db->commit();
        
        return $count;
    } catch (PDOException $e) {
        error_log("Visitor counter DB Error: " . $e->getMessage());
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        // Fallback to file-based counter
        return getVisitorCountFromFile();
    }
}

// Fallback function to read from text file (for migration/fallback)
function getVisitorCountFromFile() {
    $counter_file = __DIR__ . '/visitor_count.txt';
    $count = 0;
    
    if (file_exists($counter_file)) {
        $content = @file_get_contents($counter_file);
        if ($content !== false) {
            $count = (int)trim($content);
        }
    }
    
    $count++;
    
    // Try to update file (may fail if permissions are wrong, but that's ok)
    @file_put_contents($counter_file, $count, LOCK_EX);
    
    return $count;
}
?>

