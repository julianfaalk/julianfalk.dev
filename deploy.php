<?php
// Simple deploy webhook - GitHub calls this URL on push
// Add a secret token for security

$secret = getenv('DEPLOY_SECRET') ?: 'CHANGE_THIS_SECRET';
$token = $_GET['token'] ?? '';

if ($token !== $secret) {
    http_response_code(403);
    die('Forbidden');
}

// Run git pull
chdir(__DIR__);
$output = shell_exec('git pull 2>&1');

echo "Deployed!\n\n";
echo $output;
