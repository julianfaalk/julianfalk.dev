<?php
// Newsletter subscription helpers

require_once __DIR__ . '/guestbook.php';

function ensureNewsletterTable($db)
{
    $db->exec("CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        token TEXT,
        status TEXT NOT NULL DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        confirmed_at DATETIME,
        token_expires_at DATETIME,
        last_requested_at DATETIME,
        user_agent TEXT,
        ip_address TEXT,
        referer TEXT
    )");

    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_newsletter_email ON newsletter_subscriptions (email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_newsletter_token ON newsletter_subscriptions (token)");
}

function getAppBaseUrl()
{
    $envUrl = getenv('APP_URL') ?: '';
    if (!empty($envUrl)) {
        return rtrim($envUrl, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host;
}

function buildSubscriptionConfirmUrl($token)
{
    return getAppBaseUrl() . '/?confirm_subscription=' . urlencode($token);
}

function sendSubscriptionEmail($email, $token, $expiresAt)
{
    $apiKey = getenv('MAILGUN_API_KEY') ?: '';
    $domain = getenv('MAILGUN_DOMAIN') ?: '';
    $from = getenv('MAILGUN_FROM') ?: 'Julian Falk <newsletter@julianfalk.dev>';
    $apiBase = getenv('MAILGUN_API_BASE') ?: 'https://api.mailgun.net/v3';

    if (empty($apiKey) || empty($domain)) {
        error_log("Mailgun config missing: ensure MAILGUN_API_KEY and MAILGUN_DOMAIN are set.");
        return false;
    }

    $confirmUrl = buildSubscriptionConfirmUrl($token);
    $subject = 'Confirm your julianfalk.dev newsletter subscription';
    $expiryHuman = date('M j, Y g:i A', strtotime($expiresAt));

    $html = '
        <div style="background:#0d0d0d;padding:32px;font-family:Arial,sans-serif;color:#f7f7f7;">
            <div style="max-width:560px;margin:0 auto;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:28px;background:rgba(255,255,255,0.03);">
                <h1 style="margin-top:0;margin-bottom:12px;font-family:\'Share Tech Mono\',monospace;font-weight:700;letter-spacing:-0.5px;color:#fff;">
                    julianfalk.dev
                </h1>
                <p style="margin:0 0 14px 0;color:#e6e6e6;font-size:16px;line-height:1.6;">
                    Thanks for wanting updates. Confirm your subscription to start receiving notes and launches.
                </p>
                <a href="' . htmlspecialchars($confirmUrl, ENT_QUOTES, "UTF-8") . '" style="display:inline-block;margin:18px 0;padding:14px 18px;background:#0b82f0;color:#fff;text-decoration:none;border-radius:8px;font-weight:700;">
                    Confirm subscription
                </a>
                <p style="margin:16px 0 8px 0;color:#b3b3b3;font-size:14px;">
                    The link stays valid for 1 hour (until ' . htmlspecialchars($expiryHuman, ENT_QUOTES, "UTF-8") . '). If you didn\'t request this, feel free to ignore it.
                </p>
                <p style="margin:8px 0 0 0;font-size:13px;color:#7f7f7f;">See you soon,<br>Julian</p>
            </div>
        </div>
    ';

    $text = "Confirm your julianfalk.dev newsletter subscription\n\n";
    $text .= "Click the link to confirm (valid for 1 hour):\n$confirmUrl\n\n";
    $text .= "If you didn't request this, you can ignore the message.";

    $postData = [
        'from' => $from,
        'to' => $email,
        'subject' => $subject,
        'text' => $text,
        'html' => $html,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($apiBase, '/') . '/' . $domain . '/messages');
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        error_log('Mailgun send error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);
    return $httpCode >= 200 && $httpCode < 300;
}

function requestNewsletterSubscription($email)
{
    $email = strtolower(trim($email));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Please enter a valid email address.',
        ];
    }

    $db = getDB();
    if (!$db) {
        return [
            'success' => false,
            'message' => 'Something went wrong saving your request. Please try again.',
        ];
    }

    try {
        ensureNewsletterTable($db);

        $stmt = $db->prepare("SELECT id, status, confirmed_at FROM newsletter_subscriptions WHERE email = :email LIMIT 1");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $now = date('Y-m-d H:i:s');

        if ($existing && $existing['status'] === 'confirmed' && !empty($existing['confirmed_at'])) {
            $update = $db->prepare("UPDATE newsletter_subscriptions SET last_requested_at = :last_requested_at, user_agent = :user_agent, ip_address = :ip_address, referer = :referer WHERE id = :id");
            $update->bindValue(':last_requested_at', $now, PDO::PARAM_STR);
            $update->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
            $update->bindValue(':ip_address', $ip, PDO::PARAM_STR);
            $update->bindValue(':referer', $referer, PDO::PARAM_STR);
            $update->bindValue(':id', $existing['id'], PDO::PARAM_INT);
            $update->execute();

            return [
                'success' => true,
                'message' => 'You are already subscribed. Updates will keep coming your way.',
            ];
        }

        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        if ($existing) {
            $update = $db->prepare("UPDATE newsletter_subscriptions
                SET token = :token,
                    status = 'pending',
                    token_expires_at = :expires_at,
                    last_requested_at = :last_requested_at,
                    user_agent = :user_agent,
                    ip_address = :ip_address,
                    referer = :referer
                WHERE id = :id");
            $update->bindValue(':token', $token, PDO::PARAM_STR);
            $update->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
            $update->bindValue(':last_requested_at', $now, PDO::PARAM_STR);
            $update->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
            $update->bindValue(':ip_address', $ip, PDO::PARAM_STR);
            $update->bindValue(':referer', $referer, PDO::PARAM_STR);
            $update->bindValue(':id', $existing['id'], PDO::PARAM_INT);
            $update->execute();
        } else {
            $insert = $db->prepare("INSERT INTO newsletter_subscriptions (email, token, status, token_expires_at, last_requested_at, user_agent, ip_address, referer)
                VALUES (:email, :token, 'pending', :expires_at, :last_requested_at, :user_agent, :ip_address, :referer)");
            $insert->bindValue(':email', $email, PDO::PARAM_STR);
            $insert->bindValue(':token', $token, PDO::PARAM_STR);
            $insert->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
            $insert->bindValue(':last_requested_at', $now, PDO::PARAM_STR);
            $insert->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
            $insert->bindValue(':ip_address', $ip, PDO::PARAM_STR);
            $insert->bindValue(':referer', $referer, PDO::PARAM_STR);
            $insert->execute();
        }

        $sent = sendSubscriptionEmail($email, $token, $expiresAt);
        if (!$sent) {
            return [
                'success' => false,
                'message' => 'Could not send the confirmation email. Please try again shortly.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Check your inbox for a confirmation link to finish subscribing.',
        ];
    } catch (Exception $e) {
        error_log('Newsletter request error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Could not start your subscription right now. Please try again.',
        ];
    }
}

function confirmNewsletterSubscription($token)
{
    $token = trim($token);
    if ($token === '') {
        return [
            'success' => false,
            'message' => 'Invalid confirmation link.',
        ];
    }

    $db = getDB();
    if (!$db) {
        return [
            'success' => false,
            'message' => 'Could not validate your confirmation. Please try again.',
        ];
    }

    try {
        ensureNewsletterTable($db);

        $stmt = $db->prepare("SELECT id, token_expires_at FROM newsletter_subscriptions WHERE token = :token LIMIT 1");
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'success' => false,
                'message' => 'That confirmation link is not valid anymore.',
            ];
        }

        if (empty($row['token_expires_at'])) {
            return [
                'success' => false,
                'message' => 'That confirmation link is not valid anymore.',
            ];
        }

        $now = new DateTime('now');
        $expires = new DateTime($row['token_expires_at']);

        if ($now > $expires) {
            $update = $db->prepare("UPDATE newsletter_subscriptions SET status = 'expired', token = NULL, token_expires_at = NULL WHERE id = :id");
            $update->bindValue(':id', $row['id'], PDO::PARAM_INT);
            $update->execute();

            return [
                'success' => false,
                'message' => 'The confirmation link expired. Please subscribe again.',
            ];
        }

        $update = $db->prepare("UPDATE newsletter_subscriptions
            SET status = 'confirmed',
                confirmed_at = :confirmed_at,
                token = NULL,
                token_expires_at = NULL
            WHERE id = :id");
        $update->bindValue(':confirmed_at', $now->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $update->bindValue(':id', $row['id'], PDO::PARAM_INT);
        $update->execute();

        return [
            'success' => true,
            'message' => 'Welcome aboard! You will get updates when something interesting ships.',
        ];
    } catch (Exception $e) {
        error_log('Newsletter confirm error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Could not confirm the subscription. Please try again.',
        ];
    }
}
