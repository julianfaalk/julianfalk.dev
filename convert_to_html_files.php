<?php
/**
 * Convert content string to array of paragraphs
 */

$postsDir = __DIR__ . '/posts';

function scanPosts($dir) {
    $posts = [];
    $years = scandir($dir);

    foreach ($years as $year) {
        if ($year === '.' || $year === '..' || !is_numeric($year)) continue;

        $yearPath = "$dir/$year";
        $months = scandir($yearPath);

        foreach ($months as $month) {
            if ($month === '.' || $month === '..') continue;

            $monthPath = "$yearPath/$month";
            $slugs = scandir($monthPath);

            foreach ($slugs as $slug) {
                if ($slug === '.' || $slug === '..') continue;

                $postPath = "$monthPath/$slug";
                if (is_dir($postPath)) {
                    $posts[] = $postPath;
                }
            }
        }
    }

    return $posts;
}

$postPaths = scanPosts($postsDir);

foreach ($postPaths as $postPath) {
    $jsonPath = "$postPath/post.json";

    if (!file_exists($jsonPath)) continue;

    $data = json_decode(file_get_contents($jsonPath), true);

    if (!isset($data['content']) || is_array($data['content'])) {
        echo "Skipped: $postPath (no content or already array)\n";
        continue;
    }

    // Split content into array by double newlines (paragraph breaks)
    $content = $data['content'];

    // Split by </p> tags and rebuild as array
    $parts = preg_split('/(<\/p>)\s*/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

    $paragraphs = [];
    $current = '';

    foreach ($parts as $part) {
        if ($part === '</p>') {
            $current .= $part;
            $paragraphs[] = trim($current);
            $paragraphs[] = ''; // Empty line between paragraphs
            $current = '';
        } else {
            $current .= $part;
        }
    }

    // Add any remaining content
    if (trim($current)) {
        $paragraphs[] = trim($current);
    }

    // Remove trailing empty strings
    while (!empty($paragraphs) && $paragraphs[count($paragraphs) - 1] === '') {
        array_pop($paragraphs);
    }

    $data['content'] = $paragraphs;

    file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "Converted: $postPath\n";
}

echo "\nDone!\n";
