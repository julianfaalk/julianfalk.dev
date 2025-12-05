<?php
// Debug script to check database connection and permissions
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Debug Info</h2>";

$db_file = __DIR__ . '/julianfalk.dev.db';

echo "<p><strong>Database file path:</strong> " . $db_file . "</p>";
echo "<p><strong>File exists:</strong> " . (file_exists($db_file) ? 'Yes' : 'No') . "</p>";

if (file_exists($db_file)) {
    echo "<p><strong>File permissions:</strong> " . substr(sprintf('%o', fileperms($db_file)), -4) . "</p>";
    echo "<p><strong>File readable:</strong> " . (is_readable($db_file) ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>File writable:</strong> " . (is_writable($db_file) ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>File owner:</strong> " . posix_getpwuid(fileowner($db_file))['name'] . "</p>";
    echo "<p><strong>File group:</strong> " . posix_getgrgid(filegroup($db_file))['name'] . "</p>";
}

echo "<p><strong>Directory writable:</strong> " . (is_writable(__DIR__) ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Current user:</strong> " . get_current_user() . "</p>";

try {
    require_once 'guestbook.php';
    $db = getDB();
    
    if ($db) {
        echo "<p style='color: green;'><strong>✓ Database connection: SUCCESS</strong></p>";
        
        $stmt = $db->query("SELECT * FROM visitor_count WHERE id = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<p><strong>Current count in DB:</strong> " . $result['count'] . "</p>";
            echo "<p><strong>Last updated:</strong> " . $result['updated_at'] . "</p>";
        } else {
            echo "<p style='color: red;'><strong>✗ No visitor_count row found!</strong></p>";
        }
    } else {
        echo "<p style='color: red;'><strong>✗ Database connection: FAILED</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Test getVisitorCount()</h3>";
require_once 'counter.php';
$count = getVisitorCount();
echo "<p><strong>getVisitorCount() returned:</strong> " . $count . "</p>";
?>

