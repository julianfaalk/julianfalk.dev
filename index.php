<?php
// Include counter and get visitor count
require_once 'counter.php';
require_once 'guestbook.php';
$visitor_count = getVisitorCount();

// Handle guestbook form submission
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_entry'])) {
    $name = $_POST['name'] ?? '';
    $entry_message = $_POST['message'] ?? '';
    
    if (addGuestbookEntry($name, $entry_message)) {
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
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="main-content">
        <h1>julianfalk.dev</h1>

        <div class="guestbook-section">
            <h2>Guest Book</h2>
            
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
                <button type="submit" name="submit_entry" class="submit-btn">Sign Guestbook</button>
            </form>

            <div class="entries-container">
                <h3>Recent Entries</h3>
                <?php if (empty($entries)): ?>
                    <p class="no-entries">No entries yet. Be the first to sign!</p>
                <?php else: ?>
                    <div class="entries-list">
                        <?php foreach ($entries as $entry): ?>
                            <div class="entry">
                                <div class="entry-header">
                                    <span class="entry-name"><?php echo htmlspecialchars($entry['name']); ?></span>
                                    <span class="entry-date"><?php echo formatDate($entry['created_at']); ?></span>
                                </div>
                                <div class="entry-message"><?php echo nl2br(htmlspecialchars($entry['message'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
</body>

</html>