<?php
/**
 * Blog post list partial
 *
 * @param array $blog_posts_by_year - Posts grouped by year
 * @param string|null $exclude_slug - Optional slug to exclude from list
 */

if (!isset($blog_posts_by_year) || empty($blog_posts_by_year)) {
    return;
}

$exclude_slug = $exclude_slug ?? null;
?>
<div class="blog-timeline">
    <?php foreach ($blog_posts_by_year as $year => $posts): ?>
        <div class="blog-row">
            <div class="year-label"><?php echo htmlspecialchars($year); ?></div>
            <div class="year-posts blog-list">
                <?php foreach ($posts as $post): ?>
                    <?php if ($exclude_slug === null || $post['slug'] !== $exclude_slug): ?>
                        <div class="blog-list-item">
                            <span class="blog-date-inline"><?php echo htmlspecialchars(formatDateShort($post['created_at'])); ?></span>
                            <a class="blog-title-link" href="/blog/<?php echo htmlspecialchars($post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
