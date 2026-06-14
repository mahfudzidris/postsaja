<?php
/**
 * PostSaja Bot — Cron Poller
 * Runs every 15-30s via shared hosting cron.
 * Hosting → Telegram (outbound) — no firewall issues.
 * 
 * Setup cron (cPanel / crontab):
 *   * * * * * php /home/homesta3/public_html/poller-cron.php
 * Or every 30s via .htaccess / custom cron
 */

define('BOT_TOKEN', '8636909663:AAFa80PQ9himbAQF3FyVI6yB3xA1RPo2_SA');

// ─── State file ───
$stateFile = __DIR__ . '/data/poller-offset.json';
$offset = 0;
if (file_exists($stateFile)) {
    $state = json_decode(file_get_contents($stateFile), true);
    $offset = $state['offset'] ?? 0;
}

// ─── Fetch updates from Telegram ───
$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getUpdates?" . http_build_query([
    'offset' => $offset,
    'timeout' => 10,
    'allowed_updates' => json_encode(['message']),
]);

$response = @file_get_contents($url);
if (!$response) {
    // Telegram API unreachable? Try again next cycle
    exit;
}

$data = json_decode($response, true);
if (!$data['ok'] || empty($data['result'])) {
    exit;
}

// ─── Process each update ───
// DB connection (lazy — only when needed)
$pdo = null;

function getDB() {
    global $pdo;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=homesta3_intro;charset=utf8mb4", 
            'homesta3_intro_database', 'PostSaja@2026', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Exception $e) {
        return null;
    }
    return $pdo;
}

function apiCall($method, $payload) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/$method";
    @file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($payload),
            'timeout' => 10,
        ]
    ]));
}

foreach ($data['result'] as $update) {
    $updateId = $update['update_id'];
    $msg = $update['message'] ?? null;
    
    if (!$msg) {
        $offset = $updateId + 1;
        continue;
    }
    
    $chatId = $msg['chat']['id'] ?? null;
    $text = trim($msg['text'] ?? '');
    $photo = $msg['photo'] ?? null;
    $caption = trim($msg['caption'] ?? '');
    
    if (!$chatId) {
        $offset = $updateId + 1;
        continue;
    }
    
    // ── /start ──
    if (strpos($text, '/start') === 0) {
        apiCall('sendMessage', [
            'chat_id' => $chatId,
            'text' => "👋 *Salam! Saya PostSaja Bot.*\n\n"
                . "Saya AI Marketing Assistant yang akan auto-post gambar bisnes awak ke:\n"
                . "📰 Google Business · 📘 Facebook · 📷 Instagram · 💬 WhatsApp Status\n\n"
                . "📌 *Staff:* Hantar gambar, saya uruskan posting.\n"
                . "📌 *Owner:* Dapat ringkasan harian.\n\n"
                . "🔑 Dah ada akaun? Hantar *Business Code* 6 digit yang owner bagi.\n"
                . "❌ Belum daftar? Minta owner daftar di postsaja.com dulu.",
            'parse_mode' => 'Markdown',
        ]);
        $offset = $updateId + 1;
        continue;
    }
    
    // ── Text (Business Code) ──
    if ($text && !$photo) {
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("SELECT id, business_name FROM postsaja_businesses WHERE business_code = ?");
            $stmt->execute([strtoupper($text)]);
            $business = $stmt->fetch();
            
            if ($business) {
                $stmt = $db->prepare("INSERT INTO postsaja_staff_telegram (business_id, telegram_chat_id, telegram_username) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE telegram_username = VALUES(telegram_username)");
                $stmt->execute([$business['id'], $chatId, $msg['from']['username'] ?? '']);
                
                apiCall('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "✅ *Siap!* Akaun anda dah dipautkan ke *{$business['business_name']}*.\n\n"
                        . "Sekarang hantar gambar bila-bila — AI saya akan:\n"
                        . "1️⃣ Analyze gambar\n"
                        . "2️⃣ Generate caption + hashtags\n"
                        . "3️⃣ Auto-post ke Google Business, Facebook, Instagram, WhatsApp Status\n\n"
                        . "📸 *Cuba hantar gambar sekarang!*",
                    'parse_mode' => 'Markdown',
                ]);
            } else {
                apiCall('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "❌ *Business Code* tak sah. Sila semak semula dengan owner.\n\nAtau daftar dulu di postsaja.com",
                    'parse_mode' => 'Markdown',
                ]);
            }
        }
        $offset = $updateId + 1;
        continue;
    }
    
    // ── Photo ──
    if ($photo) {
        $fileId = end($photo)['file_id'];
        $fileInfo = @json_decode(@file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getFile?file_id=" . $fileId), true);
        $filePath = $fileInfo['result']['file_path'] ?? null;
        $fullUrl = $filePath ? "https://api.telegram.org/file/bot" . BOT_TOKEN . "/" . $filePath : null;
        
        $businessName = 'Business anda';
        $staffId = null;
        
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("SELECT b.business_name, b.id FROM postsaja_staff_telegram s JOIN postsaja_businesses b ON s.business_id = b.id WHERE s.telegram_chat_id = ? AND s.active = 1");
            $stmt->execute([$chatId]);
            $staff = $stmt->fetch();
            if ($staff) {
                $businessName = $staff['business_name'];
                $staffId = $staff['id'];
            }
        }
        
        // Acknowledge
        apiCall('sendMessage', [
            'chat_id' => $chatId,
            'text' => "📸 *Gambar diterima!*\n\nAI sedang menganalisis gambar untuk *$businessName*...",
            'parse_mode' => 'Markdown',
        ]);
        
        sleep(2);
        
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
        
        if ($fullUrl) {
            apiCall('sendPhoto', [
                'chat_id' => $chatId,
                'photo' => $fullUrl,
                'caption' => $mockCaption,
                'parse_mode' => 'Markdown',
            ]);
        } else {
            apiCall('sendMessage', [
                'chat_id' => $chatId,
                'text' => $mockCaption,
                'parse_mode' => 'Markdown',
            ]);
        }
        
        if ($staffId && $db) {
            try {
                $db->prepare("INSERT INTO postsaja_posts (business_id, staff_chat_id, image_url, ai_caption, status) VALUES (?, ?, ?, ?, 'processing')")
                    ->execute([$staffId, $chatId, $fullUrl, $userCaption]);
            } catch (Exception $e) {}
        }
        
        $offset = $updateId + 1;
        continue;
    }
    
    // ── Fallback ──
    apiCall('sendMessage', [
        'chat_id' => $chatId,
        'text' => "❌ Maaf, saya tak faham.\n\n📸 *Hantar gambar* → AI auto-post\n🔑 *Hantar Business Code* → Pautkan akaun\n/start → Lihat panduan",
        'parse_mode' => 'Markdown',
    ]);
    
    $offset = $updateId + 1;
}

// ─── Save offset ───
file_put_contents($stateFile, json_encode(['offset' => $offset]));