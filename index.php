<?php
// Include counter and get visitor count
require_once 'counter.php';
require_once 'guestbook.php';
require_once 'blog.php';
$visitor_count = getVisitorCount();

// Handle guestbook form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_entry'])) {
    $name = $_POST['name'] ?? '';
    $entry_message = $_POST['message'] ?? '';
    $website = $_POST['website'] ?? null;
    $social_media_platform = $_POST['social_media_platform'] ?? null;
    $social_media_handle = $_POST['social_media_handle'] ?? null;

    if (addGuestbookEntry($name, $entry_message, $website, $social_media_platform, $social_media_handle)) {
        $message = 'Thank you for signing the guestbook!';
        $message_type = 'success';
        // Redirect to prevent resubmission on refresh
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = 'Sorry, there was an error adding your entry. Please try again.';
        $message_type = 'error';
    }
}

// Get guestbook entries
$entries = getGuestbookEntries();
$requested_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$is_single_post_view = false;
$single_post = null;

if (preg_match('#^/blog/([a-z0-9-]+)$#', $requested_path, $matches)) {
    $is_single_post_view = true;
    $single_post = getBlogPostBySlug($matches[1]);
}

$blog_posts_by_year = getBlogPostsByYear();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>www.julianfalk.dev</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style><?php echo file_get_contents(__DIR__ . '/styles.css'); ?></style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-7VpQ3zPpKXb88GJEqn3m0w6bR5Yh5D5yE6q3Y9KXhDQ=" crossorigin="anonymous"></script>
    <script>
        $(function () {
            var $platformSelect = $('#social_media_platform');
            var $handleInput = $('#social_media_handle');

            function updateHandleInput() {
                if ($platformSelect.val()) {
                    $handleInput.prop('disabled', false).attr('placeholder', '@username');
                } else {
                    $handleInput.prop('disabled', true).val('').attr('placeholder', '@username');
                }
            }

            if ($platformSelect.length && $handleInput.length) {
                $platformSelect.on('change', updateHandleInput);
                updateHandleInput(); // Initialize on page load
            }
        });
    </script>
</head>

<body class="<?php echo $is_single_post_view ? 'single-view' : ''; ?>">
    <div class="main-content">
        <h1>julianfalk.dev</h1>

        <div class="blog-section" id="blog">
            <!-- <div class="section-header">
                <h2>Blog</h2>
                <span class="section-subtitle">Build notes, thoughts, and releases.</span>
            </div> -->

            <?php if ($is_single_post_view): ?>
                <?php if ($single_post): ?>
                    <article class="blog-single">
                        <div class="single-meta">
                            <span class="single-site">julianfalk.dev</span>
                            <span class="single-date"><?php echo formatDateDateOnly($single_post['created_at']); ?></span>
                        </div>
                        <h1 class="single-title"><?php echo htmlspecialchars($single_post['title']); ?></h1>
                        <?php if (!empty($single_post['image_url'])): ?>
                            <div class="single-hero">
                                <img src="<?php echo htmlspecialchars($single_post['image_url']); ?>" alt="<?php echo htmlspecialchars($single_post['title']); ?>">
                            </div>
                        <?php endif; ?>
                        <div class="blog-content single-body"><?php echo formatContentHtml($single_post['content']); ?></div>
                        <div class="blog-back-link">
                            <a href="/#blog">← Back to all posts</a>
                        </div>
                    </article>
                <?php else: ?>
                    <p class="no-entries">Post not found. <a href="/#blog">Back to blog</a></p>
                <?php endif; ?>
            <?php else: ?>
                <?php if (empty($blog_posts_by_year)): ?>
                    <p class="no-entries">No posts yet.</p>
                <?php else: ?>
                    <div class="blog-timeline">
                        <?php foreach ($blog_posts_by_year as $year => $posts): ?>
                            <div class="blog-row">
                                <div class="year-label"><?php echo htmlspecialchars($year); ?></div>
                                <div class="year-posts blog-list">
                                    <?php foreach ($posts as $post): ?>
                                        <div class="blog-list-item">
                                            <span class="blog-date-inline"><?php echo htmlspecialchars(formatDateShort($post['created_at'])); ?></span>
                                            <a class="blog-title-link" href="/blog/<?php echo htmlspecialchars(slugifyTitle($post['title'])); ?>">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!$is_single_post_view): ?>
            <div class="guestbook-section">
                <div class="entries-container">
                    <h2>Guest Book</h2>
                    <?php if (empty($entries)): ?>
                        <p class="no-entries">No entries yet. Be the first to sign!</p>
                    <?php else: ?>
                        <div class="entries-list">
                            <?php foreach ($entries as $entry): ?>
                                <div class="entry">
                                    <div class="entry-header">
                                        <div class="entry-name-section">
                                            <?php if (!empty($entry['website'])): ?>
                                                <a href="<?php echo htmlspecialchars($entry['website']); ?>" target="_blank" rel="noopener noreferrer" class="entry-name-link">
                                                    <span class="entry-name"><?php echo htmlspecialchars($entry['name']); ?></span>
                                                    <svg class="website-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M18 19H6c-1.1 0-2-.9-2-2V7c0-1.1.9-2 2-2h5c.55 0 1 .45 1 1s-.45 1-1 1H6v10h12v-5c0-.55.45-1 1-1s1 .45 1 1v5c0 1.1-.9 2-2 2zM14 4c0 .55.45 1 1 1h2.59l-9.13 9.13c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0L19 6.41V9c0 .55.45 1 1 1s1-.45 1-1V4c0-.55-.45-1-1-1h-5c-.55 0-1 .45-1 1z" />
                                                    </svg>
                                                </a>
                                            <?php else: ?>
                                                <span class="entry-name"><?php echo htmlspecialchars($entry['name']); ?></span>
                                            <?php endif; ?>
                                            <?php
                                            $social_url = getSocialMediaUrl($entry['social_media_platform'] ?? null, $entry['social_media_handle'] ?? null);
                                            if ($social_url && !empty($entry['social_media_platform'])):
                                                $platform = strtolower($entry['social_media_platform']);
                                                ?>
                                                <a href="<?php echo htmlspecialchars($social_url); ?>" target="_blank" rel="noopener noreferrer" class="social-icon-link" title="<?php echo htmlspecialchars(ucfirst($platform)); ?>">
                                                    <?php echo getSocialMediaIcon($platform); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <span class="entry-date"><?php echo formatDate($entry['created_at']); ?></span>
                                    </div>
                                    <div class="entry-message"><?php echo nl2br(htmlspecialchars($entry['message'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <h3>Create an entry right below!</h3>

                <?php if ($message): ?>
                    <div class="message <?php echo htmlspecialchars($message_type); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="guestbook-form">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" required maxlength="100" placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message" required maxlength="1000" rows="4" placeholder="Leave a message..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="website">Website (optional):</label>
                        <input type="url" id="website" name="website" maxlength="255" placeholder="https://example.com">
                    </div>
                    <div class="form-group social-media-group">
                        <label for="social_media_platform">Social Media (optional):</label>
                        <div class="social-media-inputs">
                            <select id="social_media_platform" name="social_media_platform" class="social-platform-select">
                                <option value="">None</option>
                                <option value="x">X</option>
                                <option value="instagram">Instagram</option>
                                <option value="github">GitHub</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="youtube">YouTube</option>
                                <option value="tiktok">TikTok</option>
                                <option value="facebook">Facebook</option>
                                <option value="reddit">Reddit</option>
                                <option value="discord">Discord</option>
                            </select>
                            <input type="text" id="social_media_handle" name="social_media_handle" maxlength="100" placeholder="@username" class="social-handle-input">
                        </div>
                    </div>
                    <button type="submit" name="submit_entry" class="submit-btn">Sign Guestbook</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$is_single_post_view): ?>
        <div class="corner-box x-profile">
            <a href="https://x.com/julianfaalk" target="_blank" rel="noopener noreferrer">
                <span>Follow me</span>
                <svg class="x-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                </svg>
                <span>@julianfaalk</span>
            </a>
        </div>

        <div class="corner-box counter">
            Visitors: <?php echo number_format($visitor_count); ?>
        </div>
    <?php endif; ?>
</body>

</html>
