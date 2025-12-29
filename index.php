<?php
// Include counter and get visitor count
require_once 'env.php';
loadEnvFile(__DIR__ . '/.env');
require_once 'counter.php';
require_once 'guestbook.php';
require_once 'blog.php';
require_once 'newsletter.php';
$visitor_count = getVisitorCount();

// Handle guestbook form submission
$message = '';
$message_type = '';
$subscription_message = '';
$subscription_message_type = '';

if (isset($_GET['confirm_subscription'])) {
    $confirmation = confirmNewsletterSubscription($_GET['confirm_subscription']);
    $subscription_message = $confirmation['message'];
    $subscription_message_type = $confirmation['success'] ? 'welcome' : 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe_newsletter'])) {
    $email = $_POST['subscriber_email'] ?? '';
    $subscription = requestNewsletterSubscription($email);

    // Store message in session and redirect to prevent resubmission on refresh
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['subscription_message'] = $subscription['message'];
    $_SESSION['subscription_message_type'] = $subscription['success'] ? 'success' : 'error';

    header('Location: ' . $_SERVER['PHP_SELF'] . '#newsletter');
    exit;
}

// Check for subscription message from session (after redirect)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['subscription_message'])) {
    $subscription_message = $_SESSION['subscription_message'];
    $subscription_message_type = $_SESSION['subscription_message_type'];
    unset($_SESSION['subscription_message']);
    unset($_SESSION['subscription_message_type']);
}

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

// SEO variables
$base_url = 'https://www.julianfalk.dev';
if ($is_single_post_view && $single_post) {
    $seo_title = $single_post['title'] . ' | Julian Falk';
    $plain_content = strip_tags($single_post['content']);
    $seo_description = substr($plain_content, 0, 155) . '...';
    $seo_canonical = $base_url . '/blog/' . $single_post['slug'];
    $seo_type = 'article';
    $hero_url = getHeroImageUrl($single_post);
    $seo_image = $hero_url ? $base_url . $hero_url : null;
    $seo_published = $single_post['created_at'];
} else {
    $seo_title = 'Julian Falk - Software Engineer & Builder';
    $seo_description = 'Personal blog by Julian Falk - thoughts on software engineering, building products, and life.';
    $seo_canonical = $base_url . '/';
    $seo_type = 'website';
    $seo_image = null;
    $seo_published = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . '/includes/partials/head.php'; ?>

    <!-- JSON-LD Structured Data -->
    <?php if ($is_single_post_view && $single_post): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": "<?php echo htmlspecialchars($single_post['title'], ENT_QUOTES); ?>",
        "description": "<?php echo htmlspecialchars($seo_description, ENT_QUOTES); ?>",
        "datePublished": "<?php echo date('c', strtotime($single_post['created_at'])); ?>",
        "dateModified": "<?php echo date('c', strtotime($single_post['created_at'])); ?>",
        "author": {
            "@type": "Person",
            "name": "Julian Falk",
            "url": "https://www.julianfalk.dev"
        },
        "publisher": {
            "@type": "Person",
            "name": "Julian Falk"
        },
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?php echo htmlspecialchars($seo_canonical, ENT_QUOTES); ?>"
        }
        <?php if ($seo_image): ?>
        ,"image": "<?php echo htmlspecialchars($seo_image, ENT_QUOTES); ?>"
        <?php endif; ?>
    }
    </script>
    <?php else: ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Julian Falk",
        "url": "https://www.julianfalk.dev",
        "author": {
            "@type": "Person",
            "name": "Julian Falk",
            "url": "https://www.julianfalk.dev",
            "sameAs": [
                "https://x.com/julianfaalk"
            ]
        }
    }
    </script>
    <?php endif; ?>

    <?php if ($subscription_message_type === 'welcome'): ?>
    <script>
        $(function() { triggerWelcomeCelebration(); });
    </script>
    <?php endif; ?>
</head>

<body class="<?php echo $is_single_post_view ? 'single-view' : ''; ?>">
    <div class="main-content">
        <h1><a class="site-title-link" href="/#blog">julianfalk.dev</a></h1>

        <?php if (!$is_single_post_view): ?>
            <div class="bio-section">
                <p class="bio-text">
                    Welcome to my little website. Here I'm sharing insights, thoughts, learings and general opinions about software engineering and life in general. Subsribe to my super aweseome newsletter to get the latest updates and insights directly in your inbox.
                </p>
            </div>
        <?php endif; ?>

        <div class="blog-section" id="blog">
            <!-- <div class="section-header">
                <h2>Blog</h2>
                <span class="section-subtitle">Build notes, thoughts, and releases.</span>
            </div> -->

            <?php if ($is_single_post_view): ?>
                <?php if ($single_post): ?>
                    <?php $single_slug = $single_post['slug']; ?>
                    <article class="blog-single">
                        <div class="single-header">
                            <h1 class="single-title">
                                <a class="single-title-link" href="/blog/<?php echo htmlspecialchars($single_slug); ?>">
                                    <?php echo htmlspecialchars($single_post['title']); ?>
                                </a>
                            </h1>
                            <span class="single-date"><?php echo formatDateDateOnly($single_post['created_at']); ?></span>
                        </div>
                        <?php $heroUrl = getHeroImageUrl($single_post); if ($heroUrl): ?>
                            <div class="single-hero">
                                <img src="<?php echo htmlspecialchars($heroUrl); ?>" alt="<?php echo htmlspecialchars($single_post['title']); ?>">
                            </div>
                        <?php endif; ?>
                        <div class="blog-content single-body"><?php echo formatContentHtml($single_post['content'], $single_post); ?></div>
                    </article>
                <?php else: ?>
                    <p class="no-entries">Post not found. <a href="/#blog">Back to blog</a></p>
                <?php endif; ?>
            <?php else: ?>
                <?php if (empty($blog_posts_by_year)): ?>
                    <p class="no-entries">No posts yet.</p>
                <?php else: ?>
                    <?php $exclude_slug = null; include __DIR__ . '/includes/partials/blog-list.php'; ?>
                <?php endif; ?>
            <?php endif; ?>

            <div class="newsletter-card <?php echo $subscription_message_type === 'welcome' ? 'welcome' : ''; ?>" id="newsletter">
                <div class="newsletter-copy">
                    <h3>New posts, straight to your inbox 📧</h3>
                    </br>
                </div>

                <?php if ($subscription_message): ?>
                    <div class="message <?php echo htmlspecialchars($subscription_message_type ?: 'success'); ?> <?php echo $subscription_message_type === 'welcome' ? 'welcome-message' : ''; ?>">
                        <?php echo htmlspecialchars($subscription_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="newsletter-form">
                    <input type="email" name="subscriber_email" class="newsletter-input" placeholder="your@email.com" required>
                    <button type="submit" name="subscribe_newsletter" class="newsletter-submit">Subscribe</button>
                </form>
            </div>

            <?php if ($is_single_post_view && !empty($blog_posts_by_year)): ?>
                <div class="more-posts-section">
                    <hr class="section-divider">
                    <h3 class="more-posts-title">More Posts</h3>
                    <?php $exclude_slug = $single_post['slug']; include __DIR__ . '/includes/partials/blog-list.php'; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$is_single_post_view): ?>
            <div class="guestbook-section">
                <div class="entries-container">
                    <div class="guestbook-header">
                        <h2>Guest Book</h2>
                        <button type="button" class="create-entry-btn" id="toggleGuestbookForm">+ Create Entry</button>
                    </div>

                    <div class="guestbook-form-wrapper" id="guestbookFormWrapper">
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
                    <?php if (empty($entries)): ?>
                        <p class="no-entries">No entries yet. Be the first to sign!</p>
                    <?php else: ?>
                        <?php
                        $visible_limit = 5;
                        $total_entries = count($entries);
                        $has_more = $total_entries > $visible_limit;
                        ?>
                        <div class="entries-list">
                            <?php foreach ($entries as $index => $entry): ?>
                                <div class="entry<?php echo $index >= $visible_limit ? ' entry-hidden' : ''; ?>">
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
                        <?php if ($has_more): ?>
                            <button type="button" class="show-more-entries-btn" id="showMoreEntries">
                                <span class="show-more-text">Show <?php echo $total_entries - $visible_limit; ?> more</span>
                                <svg class="show-more-arrow" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                                </svg>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <?php if (!$is_single_post_view): ?>
        <div class="corner-box x-profile">
            <a href="https://x.com/julianfaalk" target="_blank" rel="noopener noreferrer">
                <span>I'm on</span>
                <svg class="x-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                </svg>
                <span>@julianfaalk</span>
            </a>
        </div>

        <div class="corner-box counter">
             <?php echo number_format($visitor_count); ?> people visited
        </div>
    <?php endif; ?>

    <footer class="site-footer">
        <div class="footer-content">
            <h4 class="footer-title">Projects</h4>
            <ul class="footer-links">
                <li><a href="https://nebenkostenpro.de/" target="_blank" rel="noopener noreferrer">Nebenkostenpro</a></li>
                <!-- <li><a href="https://falksoftware.com/" target="_blank" rel="noopener noreferrer">Falk Software</a></li> -->
            </ul>
        </div>
    </footer>
</body>

</html>