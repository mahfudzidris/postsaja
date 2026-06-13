<?php
/**
 * PostSaja Telegram Bot — Webhook Handler (Production)
 * 
 * Flow:
 *   /start → welcome + ask business code
 *   Text (code) → register staff to business
 *   Photo → simulate AI processing → mock auto-post
 */

require_once __DIR__ . '/config/bot-config.php';

// ─── Database (Production) ───
$DB_HOST = 'localhost';
$DB_USER = 'homesta3_intro_database';
$DB_PASS = 'PostSaja@2026';
$DB_NAME = 'homesta3_intro';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    die('DB connection failed');
}

// ─── Incoming update from Telegram ───
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(200);
    exit;
}

// ─── Helpers ───
function sendMessage($chatId, $text, $parseMode = 'Markdown') {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => $parseMode,
    ];
    file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data),
        ]
    ]));
}

function sendPhoto($chatId, $photoUrl, $caption) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";
    $data = [
        'chat_id' => $chatId,
        'photo' => $photoUrl,
        'caption' => $caption,
        'parse_mode' => 'Markdown',
    ];
    file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data),
        ]
    ]));
}

// ─── Route incoming message ───
$chatId = $input['message']['chat']['id'] ?? null;
$text = trim($input['message']['text'] ?? '');
$photo = $input['message']['photo'] ?? null;
$caption = trim($input['message']['caption'] ?? '');

if (!$chatId) {
    http_response_code(200);
    exit;
}

// Always respond with 200 ASAP
http_response_code(200);
flush();

// ─── Handle Commands ───

// 1. /start command
if (strpos($text, '/start') === 0) {
    $welcome = "👋 *Salam! Saya PostSaja Bot.*\n\n"
        . "Saya AI Marketing Assistant yang akan auto-post gambar bisnes awak ke:\n"
        . "📰 Google Business · 📘 Facebook · 📷 Instagram · 💬 WhatsApp Status\n\n"
        . "📌 *Staff:* Hantar gambar, saya uruskan posting.\n"
        . "📌 *Owner:* Dapat ringkasan harian.\n\n"
        . "🔑 Dah ada akaun? Hantar *Business Code* 6 digit yang owner bagi.\n"
        . "❌ Belum daftar? Minta owner daftar di postsaja.com dulu.";
    sendMessage($chatId, $welcome);
    exit;
}

// 2. Text — Business Code registration
if ($text && !$photo) {
    // Check if it looks like a business code
    $stmt = $pdo->prepare("SELECT id, business_name FROM postsaja_businesses WHERE business_code = ?");
    $stmt->execute([strtoupper($text)]);
    $business = $stmt->fetch();

    if ($business) {
        // Store staff in DB
        $stmt = $pdo->prepare("
            INSERT INTO postsaja_staff_telegram (business_id, telegram_chat_id, telegram_username)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE telegram_username = VALUES(telegram_username)
        ");
        $username = $input['message']['from']['username'] ?? '';
        $stmt->execute([$business['id'], $chatId, $username]);

        sendMessage($chatId, "✅ *Siap!* Akaun anda dah dipautkan ke *{$business['business_name']}*.\n\n"
            . "Sekarang hantar gambar bila-bila — AI saya akan:\n"
            . "1️⃣ Analyze gambar\n"
            . "2️⃣ Generate caption + hashtags\n"
            . "3️⃣ Auto-post ke Google Business, Facebook, Instagram, WhatsApp Status\n\n"
            . "📸 *Cuba hantar gambar sekarang!*");
    } else {
        sendMessage($chatId, "❌ *Business Code* tak sah. Sila semak semula dengan owner.\n\n"
            . "Atau daftar dulu di postsaja.com");
    }
    exit;
}

// 3. Photo received — THE CORE FEATURE
if ($photo) {
    // Get the largest photo
    $fileId = end($photo)['file_id'];
    
    // Get file path from Telegram
    $fileUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/getFile?file_id=" . $fileId;
    $fileInfo = json_decode(file_get_contents($fileUrl), true);
    $filePath = $fileInfo['result']['file_path'] ?? null;
    $fullUrl = $filePath ? "https://api.telegram.org/file/bot" . BOT_TOKEN . "/" . $filePath : null;
    
    // Get staff business info
    $stmt = $pdo->prepare("SELECT b.business_name, b.id FROM postsaja_staff_telegram s 
                           JOIN postsaja_businesses b ON s.business_id = b.id 
                           WHERE s.telegram_chat_id = ? AND s.active = 1");
    $stmt->execute([$chatId]);
    $staff = $stmt->fetch();
    
    $businessName = $staff ? $staff['business_name'] : 'Business anda';
    
    // ─── STEP 1: Acknowledge ───
    sendMessage($chatId, "📸 *Gambar diterima!*\n\n"
        . "AI sedang menganalisis gambar untuk *$businessName*...");
    
    // ─── STEP 2: Simulate AI processing ───
    sleep(2);
    
    // Generate mock AI caption
    $userCaption = $caption ?: 'Servis kenderaan';
    $mockCaption = "✅ *" . ucfirst($userCaption) . "* — Siap!\n\n"
        . "✨ *AI Caption:*\n"
        . "\"Servis berkualiti dari $businessName. Kepuasan pelanggan keutamaan kami.\"\n\n"
        . "#servis #berkualiti #$businessName #SME #Malaysia #postSaja\n\n"
        . "📤 *Posting ke:*\n"
        . "✅ Google Business\n"
        . "✅ Facebook\n"
        . "✅ Instagram\n"
        . "✅ WhatsApp Status\n\n"
        . "📊 *Anggaran capaian:*\n"
        . "👁️ 89 views · 👍 15 likes · 💬 2 respon\n\n"
        . "🚀 Post akan naik dalam masa 5 minit!";
    
    // ─── STEP 3: Send result with the photo ───
    if ($fullUrl) {
        sendPhoto($chatId, $fullUrl, $mockCaption);
    } else {
        sendMessage($chatId, $mockCaption);
    }
    
    // ─── STEP 4: Log to database ───
    if ($staff) {
        try {
            $pdo->prepare("
                INSERT INTO postsaja_posts (business_id, staff_chat_id, image_url, ai_caption, status)
                VALUES (?, ?, ?, ?, 'processing')
            ")->execute([$staff['id'], $chatId, $fullUrl, $userCaption]);
        } catch (Exception $e) {
            // Silently log
        }
    }
    
    exit;
}

// Fallback
sendMessage($chatId, "❌ Maaf, saya tak faham.\n\n"
    . "📸 *Hantar gambar* → AI auto-post\n"
    . "🔑 *Hantar Business Code* → Pautkan akaun\n"
    . "/start → Lihat panduan");
