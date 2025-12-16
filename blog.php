<?php
// Blog helper functions - JSON file based storage with year/month organization

define('POSTS_DIR', __DIR__ . '/posts');

function slugifyTitle($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug ?: 'post';
}

/**
 * Get all blog posts grouped by year
 * Scans /posts/YYYY/MM/slug/post.json structure
 */
function getBlogPostsByYear() {
    $postsDir = POSTS_DIR;

    if (!is_dir($postsDir)) {
        return [];
    }

    $posts = [];

    // Scan year folders
    $years = scandir($postsDir);
    foreach ($years as $year) {
        if ($year === '.' || $year === '..' || !is_numeric($year)) continue;

        $yearPath = "$postsDir/$year";
        if (!is_dir($yearPath)) continue;

        // Scan month folders
        $months = scandir($yearPath);
        foreach ($months as $month) {
            if ($month === '.' || $month === '..') continue;

            $monthPath = "$yearPath/$month";
            if (!is_dir($monthPath)) continue;

            // Scan post folders
            $slugs = scandir($monthPath);
            foreach ($slugs as $slug) {
                if ($slug === '.' || $slug === '..') continue;

                $jsonPath = "$monthPath/$slug/post.json";
                if (!file_exists($jsonPath)) continue;

                $json = file_get_contents($jsonPath);
                $post = json_decode($json, true);

                if (!$post || !isset($post['title']) || !isset($post['created_at'])) continue;

                // Store path info for URL building
                $post['slug'] = $slug;
                $post['year'] = $year;
                $post['month'] = $month;
                $posts[] = $post;
            }
        }
    }

    // Sort by created_at descending
    usort($posts, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Group by year
    $grouped = [];
    foreach ($posts as $post) {
        $year = $post['year'];
        if (!isset($grouped[$year])) {
            $grouped[$year] = [];
        }
        $grouped[$year][] = $post;
    }

    // Sort years descending
    krsort($grouped);

    return $grouped;
}

/**
 * Get a single blog post by its slug
 * Searches through all year/month folders
 */
function getBlogPostBySlug($slug) {
    $postsDir = POSTS_DIR;

    if (!is_dir($postsDir)) {
        return null;
    }

    // Scan year folders
    $years = scandir($postsDir);
    foreach ($years as $year) {
        if ($year === '.' || $year === '..' || !is_numeric($year)) continue;

        $yearPath = "$postsDir/$year";
        if (!is_dir($yearPath)) continue;

        // Scan month folders
        $months = scandir($yearPath);
        foreach ($months as $month) {
            if ($month === '.' || $month === '..') continue;

            $monthPath = "$yearPath/$month";
            $jsonPath = "$monthPath/$slug/post.json";

            if (file_exists($jsonPath)) {
                $json = file_get_contents($jsonPath);
                $post = json_decode($json, true);

                if ($post) {
                    $post['slug'] = $slug;
                    $post['year'] = $year;
                    $post['month'] = $month;
                    return $post;
                }
            }
        }
    }

    return null;
}

/**
 * Get the base path for a post's assets
 */
function getPostBasePath($post) {
    if (empty($post['year']) || empty($post['month']) || empty($post['slug'])) {
        return '/posts/' . ($post['slug'] ?? '');
    }
    return '/posts/' . $post['year'] . '/' . $post['month'] . '/' . $post['slug'];
}

/**
 * Format content HTML and process media markers
 *
 * Markers supported:
 * - {{image:filename.jpg}} or {{image:filename.jpg|alt text}}
 * - {{youtube:VIDEO_ID}}
 */
function formatContentHtml($content, $post = null) {
    $basePath = $post ? getPostBasePath($post) : '';

    // Process image markers: {{image:file.jpg}} or {{image:file.jpg|alt text}}
    $content = preg_replace_callback(
        '/\{\{image:([^}|]+)(?:\|([^}]*))?\}\}/',
        function($matches) use ($basePath) {
            $filename = trim($matches[1]);
            $alt = isset($matches[2]) ? htmlspecialchars(trim($matches[2]), ENT_QUOTES, 'UTF-8') : '';

            $src = $basePath ? "$basePath/$filename" : $filename;
            $src = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');

            return '<img src="' . $src . '" alt="' . $alt . '" class="inline-image">';
        },
        $content
    );

    // Process YouTube markers: {{youtube:VIDEO_ID}}
    $content = preg_replace_callback(
        '/\{\{youtube:([a-zA-Z0-9_-]+)\}\}/',
        function($matches) {
            $videoId = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
            return '<div class="video-embed">'
                . '<iframe src="https://www.youtube.com/embed/' . $videoId . '" '
                . 'frameborder="0" allowfullscreen '
                . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">'
                . '</iframe></div>';
        },
        $content
    );

    return $content;
}

/**
 * Get hero image URL for a post
 */
function getHeroImageUrl($post) {
    if (empty($post['hero_image'])) {
        return null;
    }
    return getPostBasePath($post) . '/' . $post['hero_image'];
}
