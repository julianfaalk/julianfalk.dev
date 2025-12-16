    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($seo_title ?? 'Julian Falk'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_description ?? 'Personal blog by Julian Falk - thoughts on software engineering, building products, and life.'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($seo_canonical ?? 'https://www.julianfalk.dev/'); ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="<?php echo $seo_type ?? 'website'; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_title ?? 'Julian Falk'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_description ?? 'Personal blog by Julian Falk - thoughts on software engineering, building products, and life.'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($seo_canonical ?? 'https://www.julianfalk.dev/'); ?>">
    <meta property="og:site_name" content="Julian Falk">
    <?php if (!empty($seo_image)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($seo_image); ?>">
    <?php else: ?>
    <meta property="og:image" content="https://www.julianfalk.dev/assets/images/favicon.jpg">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@julianfaalk">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seo_title ?? 'Julian Falk'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_description ?? 'Personal blog by Julian Falk - thoughts on software engineering, building products, and life.'); ?>">
    <?php if (!empty($seo_image)): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($seo_image); ?>">
    <?php else: ?>
    <meta name="twitter:image" content="https://www.julianfalk.dev/assets/images/favicon.jpg">
    <?php endif; ?>

    <link rel="icon" type="image/jpeg" href="/assets/images/favicon.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/styles.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/js/confetti.js"></script>
    <script src="/js/app.js"></script>
