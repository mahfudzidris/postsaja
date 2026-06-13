/**
 * PostSaja Bot — Telegram Poller
 * Runs on Mac, checks Telegram for new messages every 10s.
 * Alternative to webhook (hosting firewall blocks Telegram).
 * 
 * Run: node telegram-poller.js
 */

const TELEGRAM_TOKEN = '8636909663:AAFa80PQ9himbAQF3FyVI6yB3xA1RPo2_SA';
const API_BASE = `https://api.telegram.org/bot${TELEGRAM_TOKEN}`;

let lastUpdateId = 0;

const mysql = require('mysql2/promise');

// ─── DB Pool ───
const db = mysql.createPool({
  host: 'localhost',
  user: 'root',
  password: 'Pathfinder@2024!',
  database: 'postsaja_marketing',
  waitForConnections: true,
  connectionLimit: 2,
});

// ─── Helpers ───
async function call(method, payload = {}) {
  const resp = await fetch(`${API_BASE}/${method}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  return resp.json();
}

async function sendMsg(chatId, text) {
  return call('sendMessage', { chat_id: chatId, text, parse_mode: 'Markdown' });
}

async function sendPhoto(chatId, photoUrl, caption) {
  return call('sendPhoto', { chat_id: chatId, photo: photoUrl, caption, parse_mode: 'Markdown' });
}

// ─── Message Handlers ───
async function handleStart(chatId) {
  const msg = `👋 *Salam! Saya PostSaja Bot.*

Saya AI Marketing Assistant yang akan auto-post gambar bisnes awak ke:
📰 Google Business · 📘 Facebook · 📷 Instagram · 💬 WhatsApp Status

📌 *Staff:* Hantar gambar, saya uruskan posting.
📌 *Owner:* Dapat ringkasan harian.

🔑 Dah ada akaun? Hantar *Business Code* 6 digit yang owner bagi.
❌ Belum daftar? Minta owner daftar di postsaja.com dulu.`;
  await sendMsg(chatId, msg);
  console.log(`  → Sent welcome to ${chatId}`);
}

async function handleCode(chatId, code, username) {
  const [rows] = await db.query(
    'SELECT id, business_name FROM postsaja_businesses WHERE business_code = ?',
    [code.toUpperCase()]
  );
  
  if (rows.length === 0) {
    await sendMsg(chatId, `❌ *Business Code* tak sah. Sila semak semula dengan owner.\n\nAtau daftar dulu di postsaja.com`);
    console.log(`  → Invalid code: ${code}`);
    return;
  }
  
  const biz = rows[0];
  await db.query(
    `INSERT INTO postsaja_staff_telegram (business_id, telegram_chat_id, telegram_username)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE telegram_username = VALUES(telegram_username)`,
    [biz.id, chatId, username || '']
  );
  
  await sendMsg(chatId, `✅ *Siap!* Akaun anda dah dipautkan ke *${biz.business_name}*.\n\nSekarang hantar gambar bila-bila — AI saya akan:\n1️⃣ Analyze gambar\n2️⃣ Generate caption + hashtags\n3️⃣ Auto-post ke Google Business, Facebook, Instagram, WhatsApp Status\n\n📸 *Cuba hantar gambar sekarang!*`);
  console.log(`  → Registered ${chatId} to ${biz.business_name}`);
}

async function handlePhoto(chatId, photo, caption) {
  // Get largest photo file_id
  const fileId = photo[photo.length - 1].file_id;
  const fileInfo = await call('getFile', { file_id: fileId });
  const filePath = fileInfo?.result?.file_path;
  const fullUrl = filePath ? `https://api.telegram.org/file/bot${TELEGRAM_TOKEN}/${filePath}` : null;

  // Get business name
  const [staff] = await db.query(
    `SELECT b.business_name, b.id FROM postsaja_staff_telegram s 
     JOIN postsaja_businesses b ON s.business_id = b.id 
     WHERE s.telegram_chat_id = ? AND s.active = 1`,
    [chatId]
  );
  const bizName = staff.length ? staff[0].business_name : 'Business anda';
  
  // Step 1: Acknowledge
  await sendMsg(chatId, `📸 *Gambar diterima!*\n\nAI sedang menganalisis gambar untuk *${bizName}*...`);
  console.log(`  → Processing photo for ${bizName}`);
  
  // Step 2: Simulate AI processing
  await new Promise(r => setTimeout(r, 2000));
  
  // Step 3: Generate mock result
  const userCaption = caption || 'Servis kenderaan';
  const result = `✅ *${userCaption.charAt(0).toUpperCase() + userCaption.slice(1)}* — Siap!

✨ *AI Caption:*
"Servis berkualiti dari ${bizName}. Kepuasan pelanggan keutamaan kami."

#servis #berkualiti #${bizName.replace(/\s+/g, '')} #SME #Malaysia #postSaja

📤 *Posting ke:*
✅ Google Business
✅ Facebook
✅ Instagram
✅ WhatsApp Status

📊 *Anggaran capaian:*
👁️ 89 views · 👍 15 likes · 💬 2 respon

🚀 Post akan naik dalam masa 5 minit!`;

  if (fullUrl) {
    await sendPhoto(chatId, fullUrl, result);
  } else {
    await sendMsg(chatId, result);
  }
  
  // Step 4: Log
  if (staff.length) {
    await db.query(
      `INSERT INTO postsaja_posts (business_id, staff_chat_id, image_url, ai_caption, status)
       VALUES (?, ?, ?, ?, 'processing')`,
      [staff[0].id, chatId, fullUrl || '', userCaption]
    );
  }
  console.log(`  → Posted result for ${bizName}`);
}

// ─── Main Poll Loop ───
async function poll() {
  const data = await call('getUpdates', {
    offset: lastUpdateId + 1,
    timeout: 30,
    allowed_updates: ['message'],
  });

  if (!data?.ok || !data?.result?.length) return;

  for (const update of data.result) {
    const msg = update.message;
    if (!msg) continue;

    lastUpdateId = update.update_id;
    const chatId = msg.chat.id;
    const text = (msg.text || '').trim();
    const photo = msg.photo;
    const caption = (msg.caption || '').trim();
    const username = msg.from?.username || '';

    console.log(`[${new Date().toLocaleTimeString()}] Got msg from ${chatId}: ${text?.substring(0, 50) || '(photo)'}`);

    try {
      if (text.startsWith('/start')) {
        await handleStart(chatId);
      } else if (text && !photo) {
        await handleCode(chatId, text, username);
      } else if (photo) {
        await handlePhoto(chatId, photo, caption);
      } else {
        await sendMsg(chatId, `❌ Maaf, saya tak faham.\n\n📸 *Hantar gambar* → AI auto-post\n🔑 *Hantar Business Code* → Pautkan akaun\n/start → Lihat panduan`);
      }
    } catch (err) {
      console.error(`  ✗ Error handling update ${update.update_id}:`, err.message);
    }
  }
}

// ─── Start ───
console.log('🤖 PostSaja Bot — Telegram Poller');
console.log('   Polling every 10s...\n');

setInterval(poll, 10000);
poll(); // First run immediately
