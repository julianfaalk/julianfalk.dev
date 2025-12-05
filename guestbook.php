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
            website TEXT,
            social_media_platform TEXT,
            social_media_handle TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add new columns to existing table if they don't exist (for migration)
        try {
            $db->exec("ALTER TABLE entries ADD COLUMN website TEXT");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
        try {
            $db->exec("ALTER TABLE entries ADD COLUMN social_media_platform TEXT");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
        try {
            $db->exec("ALTER TABLE entries ADD COLUMN social_media_handle TEXT");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
        
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

function addGuestbookEntry($name, $message, $website = null, $social_media_platform = null, $social_media_handle = null) {
    $db = getGuestbookDB();
    if (!$db) {
        return false;
    }
    
    try {
        // Sanitize input
        $name = trim($name);
        $message = trim($message);
        $website = $website ? trim($website) : null;
        $social_media_platform = $social_media_platform ? trim($social_media_platform) : null;
        $social_media_handle = $social_media_handle ? trim($social_media_handle) : null;
        
        // Basic validation
        if (empty($name) || empty($message)) {
            return false;
        }
        
        // Limit length
        if (strlen($name) > 100 || strlen($message) > 1000) {
            return false;
        }
        
        // Validate and normalize website URL
        if ($website) {
            // Add http:// if no protocol is specified
            if (!preg_match('/^https?:\/\//i', $website)) {
                $website = 'https://' . $website;
            }
            // Basic URL validation
            if (!filter_var($website, FILTER_VALIDATE_URL)) {
                return false;
            }
            // Limit website length
            if (strlen($website) > 255) {
                return false;
            }
        }
        
        // Validate social media handle if platform is provided
        if ($social_media_platform && empty($social_media_handle)) {
            return false; // If platform is selected, handle is required
        }
        
        // Limit handle length
        if ($social_media_handle && strlen($social_media_handle) > 100) {
            return false;
        }
        
        $stmt = $db->prepare("INSERT INTO entries (name, message, website, social_media_platform, social_media_handle) VALUES (:name, :message, :website, :platform, :handle)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':website', $website, PDO::PARAM_STR);
        $stmt->bindParam(':platform', $social_media_platform, PDO::PARAM_STR);
        $stmt->bindParam(':handle', $social_media_handle, PDO::PARAM_STR);
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
        $stmt = $db->prepare("SELECT id, name, message, website, social_media_platform, social_media_handle, created_at FROM entries ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Guestbook Get Error: " . $e->getMessage());
        return [];
    }
}

function getSocialMediaUrl($platform, $handle) {
    if (empty($platform) || empty($handle)) {
        return null;
    }
    
    // Remove @ if user included it
    $handle = ltrim($handle, '@');
    
    $urls = [
        'x' => 'https://x.com/' . $handle,
        'instagram' => 'https://instagram.com/' . $handle,
        'github' => 'https://github.com/' . $handle,
        'linkedin' => 'https://linkedin.com/in/' . $handle,
        'youtube' => 'https://youtube.com/@' . $handle,
        'tiktok' => 'https://tiktok.com/@' . $handle,
        'facebook' => 'https://facebook.com/' . $handle,
        'reddit' => 'https://reddit.com/user/' . $handle,
        'discord' => 'https://discord.com/users/' . $handle,
    ];
    
    return isset($urls[strtolower($platform)]) ? $urls[strtolower($platform)] : null;
}

function formatDate($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

function getSocialMediaIcon($platform) {
    $platform = strtolower($platform);
    $icons = [
        // X (formerly Twitter) - Official X logo
        'x' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        
        // Instagram - Official camera icon
        'instagram' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
        
        // GitHub - Official octocat logo
        'github' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>',
        
        // LinkedIn - Official "in" logo
        'linkedin' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        
        // YouTube - Official play button logo
        'youtube' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        
        // TikTok - Official music note logo
        'tiktok' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.51 6.51 0 0 0-1-.08A6.49 6.49 0 0 0 5 15.91a6.49 6.49 0 0 0 10.86 4.81 6.46 6.46 0 0 0 3.8-5.81V7.77a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.2z"/></svg>',
        
        // Facebook - Official "f" logo
        'facebook' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        
        // Reddit - Official alien/robot logo
        'reddit' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>',
        
        // Discord - Official game controller logo
        'discord' => '<svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C2.451 6.018 1.73 7.713 1.43 9.432a.082.082 0 0 0 .031.084 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.084c-.3-1.72-1.023-3.415-2.216-5.036a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>',
    ];
    
    return isset($icons[$platform]) ? $icons[$platform] : '';
}
?>

