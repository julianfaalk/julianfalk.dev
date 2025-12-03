<?php
// Simple visitor counter function
function getVisitorCount() {
    // Use absolute path to ensure file is in the same directory as this script
    $counter_file = __DIR__ . '/visitor_count.txt';
    $dir = __DIR__;
    
    // Check if directory is writable
    if (!is_writable($dir) && !is_writable(dirname($counter_file))) {
        // If directory not writable, try using /tmp as fallback
        $counter_file = sys_get_temp_dir() . '/julianfalk_dev_visitor_count.txt';
    }
    
    // Read current count
    $count = 0;
    if (file_exists($counter_file)) {
        $content = @file_get_contents($counter_file);
        if ($content !== false) {
            $count = (int)trim($content);
        }
    }
    
    // Increment count
    $count++;
    
    // Write new count back to file
    // Use LOCK_EX to prevent concurrent writes
    $result = @file_put_contents($counter_file, $count, LOCK_EX);
    
    // If write failed, log error (but don't break the page)
    if ($result === false) {
        error_log("Visitor counter: Failed to write to $counter_file. Check file permissions.");
    } else {
        // Ensure file has correct permissions
        @chmod($counter_file, 0666);
    }
    
    // Return the count
    return $count;
}
?>

