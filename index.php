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
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        h1 {
            font-size: 3rem;
            font-weight: 300;
            margin: 0;
        }
        .counter {
            margin-top: 2rem;
            font-size: 1rem;
            opacity: 0.7;
            font-weight: 300;
        }
    </style>
</head>
<body>
    <h1>julianfalk.dev</h1>
    <div class="counter">Visitors: <?php echo number_format($visitor_count); ?></div>
</body>
</html>

