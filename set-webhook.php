<?php
/**
 * PostSaja Bot — Set Webhook
 * Run once to tell Telegram where to send updates.
 */

require_once __DIR__ . '/config/bot-config.php';

$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook";
$data = [
    'url' => BOT_WEBHOOK_URL,
    'drop_pending_updates' => true,
    'allowed_updates' => json_encode(['message']),
];

$result = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($data),
    ]
]));

echo "Response:\n";
echo print_r(json_decode($result, true), true);
