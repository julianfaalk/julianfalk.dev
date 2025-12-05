<?php
// Visitor counter function using SQLite database with session tracking

function getVisitorCount() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Include database functions
    require_once __DIR__ . '/guestbook.php';
    
    $db = getDB();
    if (!$db) {
        // Database connection failed - return 0 or log error
        error_log("Visitor counter: Database connection failed");
        return 0;
    }
    
    try {
        // Initialize counter if it doesn't exist
        $stmt = $db->query("SELECT COUNT(*) FROM visitor_count");
        if ($stmt->fetchColumn() == 0) {
            // Initialize with count 0 if table is empty
            $db->exec("INSERT INTO visitor_count (id, count) VALUES (1, 0)");
        }
        
        // Check if this visitor has already been counted in this session
        if (!isset($_SESSION['visitor_counted'])) {
            // Get current count and increment only if not counted yet
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
            
            // Mark this visitor as counted in the session
            $_SESSION['visitor_counted'] = true;
        } else {
            // Visitor already counted, just return current count without incrementing
            $stmt = $db->prepare("SELECT count FROM visitor_count WHERE id = 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result ? (int)$result['count'] : 0;
        }
        
        return $count;
    } catch (PDOException $e) {
        error_log("Visitor counter DB Error: " . $e->getMessage());
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        // Return 0 if database operation fails
        return 0;
    }
}
?>

