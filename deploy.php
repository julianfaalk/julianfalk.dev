<?php
// Simple deploy webhook
$secret = getenv('DEPLOY_SECRET');
$token = $_GET['token'] ?? '';

if (!$secret || $token !== $secret) {
    http_response_code(403);
    die('Forbidden');
}

chdir(__DIR__);
echo shell_exec('git pull 2>&1');
