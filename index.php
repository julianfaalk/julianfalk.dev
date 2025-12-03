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
    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            position: relative;
        }
        h1 {
            font-size: 3rem;
            font-weight: 300;
            margin: 0;
        }
        .corner-box {
            position: fixed;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 300;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .corner-box:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }
        .counter {
            bottom: 1.5rem;
            right: 1.5rem;
        }
        .x-profile {
            bottom: 1.5rem;
            left: 1.5rem;
        }
        .x-profile a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .x-profile a:hover {
            opacity: 0.8;
        }
        .x-icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }
    </style>
</head>
<body>
    <h1>julianfalk.dev</h1>
    
    <div class="corner-box x-profile">
        <a href="https://x.com/julianfaalk" target="_blank" rel="noopener noreferrer">
            <svg class="x-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
            <span>@julianfaalk</span>
        </a>
    </div>
    
    <div class="corner-box counter">
        Visitors: <?php echo number_format($visitor_count); ?>
    </div>
</body>
</html>

