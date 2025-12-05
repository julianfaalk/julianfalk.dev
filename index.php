<?php
// Include counter and get visitor count
require_once 'counter.php';
$visitor_count = getVisitorCount();
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
    <h1>julianfalk.dev</h1>

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