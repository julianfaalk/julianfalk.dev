<?php
/**
 * Dynamic XML Sitemap Generator
 */
require_once __DIR__ . '/blog.php';

header('Content-Type: application/xml; charset=utf-8');

$base_url = 'https://www.julianfalk.dev';
$blog_posts_by_year = getBlogPostsByYear();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?php echo $base_url; ?>/</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Blog Posts -->
<?php foreach ($blog_posts_by_year as $year => $posts): ?>
<?php foreach ($posts as $post): ?>
    <url>
        <loc><?php echo $base_url; ?>/blog/<?php echo htmlspecialchars($post['slug']); ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($post['created_at'])); ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>
<?php endforeach; ?>
</urlset>
