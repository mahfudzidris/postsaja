<?php
/**
 * PostSaja Bot — Production Database Setup
 * Visit once: https://postsaja.com/setup-bot-db.php
 */

$DB_HOST = 'localhost';
$DB_USER = 'homesta3_intro_database';
$DB_PASS = 'PostSaja@2026';
$DB_NAME = 'homesta3_intro';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Business accounts
    $pdo->exec("CREATE TABLE IF NOT EXISTS postsaja_businesses (
      id INT AUTO_INCREMENT PRIMARY KEY,
      business_name VARCHAR(255) NOT NULL,
      owner_name VARCHAR(255) DEFAULT NULL,
      owner_wa VARCHAR(50) DEFAULT NULL,
      business_code VARCHAR(6) NOT NULL UNIQUE,
      telegram_bot_enabled TINYINT(1) DEFAULT 1,
      google_business_token TEXT DEFAULT NULL,
      fb_token TEXT DEFAULT NULL,
      ig_token TEXT DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Staff
    $pdo->exec("CREATE TABLE IF NOT EXISTS postsaja_staff_telegram (
      id INT AUTO_INCREMENT PRIMARY KEY,
      business_id INT NOT NULL,
      telegram_chat_id BIGINT NOT NULL UNIQUE,
      telegram_username VARCHAR(255) DEFAULT NULL,
      display_name VARCHAR(255) DEFAULT NULL,
      role ENUM('staff','supervisor','owner') DEFAULT 'staff',
      active TINYINT(1) DEFAULT 1,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (business_id) REFERENCES postsaja_businesses(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Posts
    $pdo->exec("CREATE TABLE IF NOT EXISTS postsaja_posts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      business_id INT DEFAULT NULL,
      staff_chat_id BIGINT DEFAULT NULL,
      image_url TEXT DEFAULT NULL,
      ai_caption TEXT DEFAULT NULL,
      platforms_posted JSON DEFAULT NULL,
      status ENUM('processing','posted','failed') DEFAULT 'processing',
      analytics JSON DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (business_id) REFERENCES postsaja_businesses(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Demo businesses
    $pdo->exec("INSERT IGNORE INTO postsaja_businesses (id, business_name, owner_name, business_code)
                VALUES (1, 'Bengkel Demo Khamis', 'Tokey Bengkel', 'BENGKEL'),
                       (2, 'Kedai Makan Demo', 'Tokey Makan', 'MAKAN')");
    
    echo "<h2>✅ PostSaja Bot — Database Setup Complete!</h2>";
    echo "<p>Tables: postsaja_businesses, postsaja_staff_telegram, postsaja_posts</p>";
    echo "<p>Demo businesses: BENGKEL, MAKAN</p>";
    echo "<p><a href='https://t.me/PostSajaBot'>➡️ Try the bot</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2><p>" . $e->getMessage() . "</p>";
}
