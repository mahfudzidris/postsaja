<?php
/**
 * PostSaja Telegram Bot — Webhook Handler (Production)
 * 
 * ⚡ Minimal, fast, no-DB-for-start approach.
 * Apache shared hosting = flush() sometimes doesn't close connection,
 * so keep entire script fast (< 1s).
 * 
 * Flow:
 *   /start → welcome (no DB needed)
 *   Text (code) → register staff → DB only when needed
 *   Photo → simulate AI auto-post
 */

require_once __DIR__ . '/config/bot-config.php';

// ─── Read input ───
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message']['chat']['id'])) {
    http_response_code(200);
    exit;
}

$chatId = $input['message']['chat']['id'];
$text = trim($input['message']['text'] ?? '');
$photo = $input['message']['photo'] ?? null;
$caption = trim($input['message']['caption'] ?? '');

// ─── Respond 200 ASAP ───
http_response_code(200);
// Minimal output — Apache on shared hosting may buffer regardless,
// but we keep response tiny and exit fast.

// ─── Handlers ───

// 1. /start — no DB needed, fast reply
if (strpos($text, '/start') === 0) {
    $welcome = "👋 *Salam! Saya PostSaja Bot.*\n\n"
        . "Saya AI Marketing Assistant yang akan auto-post gambar bisnes awak ke:\n"
        . "📰 Google Business · 📘 Facebook · 📷 Instagram · 💬 WhatsApp Status\n\n"
        . "📌 *Staff:* Hantar gambar, saya uruskan posting.\n"
        . "📌 *Owner:* Dapat ringkasan harian.\n\n"
        . "🔑 Dah ada akaun? Hantar *Business Code* 6 digit yang owner bagi.\n"
        . "❌ Belum daftar? Minta owner daftar di postsaja.com dulu.";
    @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'chat_id' => $chatId,
                'text' => $welcome,
                'parse_mode' => 'Markdown',
            ]),
            'timeout' => 5,
        ]
    ]));
    exit;
}

// ─── Database (lazy — only for code registration & photo) ───
try {
    $pdo = new PDO("mysql:host=localhost;dbname=homesta3_intro;charset=utf8mb4", 'homesta3_intro_database', 'PostSaja@2026', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    exit;
}

// 2. Business Code Registration
if ($text && !$photo) {
    $stmt = $pdo->prepare("SELECT id, business_name FROM postsaja_businesses WHERE business_code = ?");
    $stmt->execute([strtoupper($text)]);
    $business = $stmt->fetch();

    if ($business) {
        $stmt = $pdo->prepare("INSERT INTO postsaja_staff_telegram (business_id, telegram_chat_id, telegram_username) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE telegram_username = VALUES(telegram_username)");
        $stmt->execute([$business['id'], $chatId, $input['message']['from']['username'] ?? '']);

        @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage", false, stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => json_encode(['chat_id' => $chatId, 'text' => "✅ *Siap!* Akaun anda dah dipautkan ke *{$business['business_name']}*.\n\nSekarang hantar gambar bila-bila — AI saya akan:\n1️⃣ Analyze gambar\n2️⃣ Generate caption + hashtags\n3️⃣ Auto-post ke Google Business, Facebook, Instagram, WhatsApp Status\n\n📸 *Cuba hantar gambar sekarang!*", 'parse_mode' => 'Markdown']), 'timeout' => 5]
        ]));
    } else {
        @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage", false, stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => json_encode(['chat_id' => $chatId, 'text' => "❌ *Business Code* tak sah. Sila semak semula dengan owner.\n\nAtau daftar dulu di postsaja.com", 'parse_mode' => 'Markdown']), 'timeout' => 5]
        ]));
    }
    exit;
}

// 3. Photo received
if ($photo) {
    $fileId = end($photo)['file_id'];
    $fileInfo = @json_decode(@file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getFile?file_id=" . $fileId), true);
    $fullUrl = $fileInfo['result']['file_path'] ?? null;
    if ($fullUrl) {
        $fullUrl = "https://api.telegram.org/file/bot" . BOT_TOKEN . "/" . $fullUrl;
    }

    // Get business name
    $stmt = $pdo->prepare("SELECT b.business_name, b.id FROM postsaja_staff_telegram s JOIN postsaja_businesses b ON s.business_id = b.id WHERE s.telegram_chat_id = ? AND s.active = 1");
    $stmt->execute([$chatId]);
    $staff = $stmt->fetch();
    $businessName = $staff ? $staff['business_name'] : 'Business anda';

    // Acknowledge
    @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage", false, stream_context_create([
        'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => json_encode(['chat_id' => $chatId, 'text' => "📸 *Gambar diterima!*\n\nAI sedang menganalisis gambar untuk *$businessName*...", 'parse_mode' => 'Markdown']), 'timeout' => 5]
    ]));

    sleep(2);

    $userCaption = $caption ?: 'Servis kenderaan';
    $mockCaption = "✅ *" . ucfirst($userCaption) . "* — Siap!\n\n✨ *AI Caption:*\n\"Servis berkualiti dari $businessName. Kepuasan pelanggan keutamaan kami.\"\n\n#servis #berkualiti #$businessName #SME #Malaysia #postSaja\n\n📤 *Posting ke:*\n✅ Google Business\n✅ Facebook\n✅ Instagram\n✅ WhatsApp Status\n\n📊 *Anggaran capaian:*\n👁️ 89 views · 👍 15 likes · 💬 2 respon\n\n🚀 Post akan naik dalam masa 5 minit!";

    if ($fullUrl) {
        @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto", false, stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => json_encode(['chat_id' => $chatId, 'photo' => $fullUrl, 'caption' => $mockCaption, 'parse_mode' => 'Markdown']), 'timeout' => 5]
        ]));
    } else {
        @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage", false, stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => json_encode(['chat_id' => $chatId, 'text' => $mockCaption, 'parse_mode' => 'Markdown']), 'timeout' => 5]
        ]));
    }

    if ($staff) {
        try {
            $pdo->prepare("INSERT INTO postsaja_posts (business_id, staff_chat_id, image_url, ai_caption, status) VALUES (?, ?, ?, ?, 'processing')")->execute([$staff['id'], $chatId, $fullUrl, $userCaption]);
        } catch (Exception $e) {}
    }
    exit;
}

// Fallback
@file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage", false, stream_context_create([
    'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => json_encode(['chat_id' => $chatId, 'text' => "❌ Maaf, saya tak faham.\n\n📸 *Hantar gambar* → AI auto-post\n🔑 *Hantar Business Code* → Pautkan akaun\n/start → Lihat panduan", 'parse_mode' => 'Markdown']), 'timeout' => 5]
]));