<?php
// subscribe.php — Handle PostSaja email signups (MySQL)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database config
$dbHost = 'localhost';
$dbUser = 'homesta3_intro_database';
$dbPass = 'PostSaja@2026';
$dbName = 'homesta3_intro';

// Connect to MySQL
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ralat server. Cuba lagi nanti.']);
    exit;
}

// Create subscribers table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS postsaja_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(100) DEFAULT 'postsaja.com',
    ip VARCHAR(45) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sila masukkan email.']);
    $conn->close();
    exit;
}

$email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email tak sah. Cuba lagi.']);
    $conn->close();
    exit;
}

// Check duplicate
$stmt = $conn->prepare("SELECT id FROM postsaja_subscribers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Email dah didaftarkan! Kami akan maklumkan bila pelancaran tiba.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Insert new subscriber
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$stmt = $conn->prepare("INSERT INTO postsaja_subscribers (email, source, ip) VALUES (?, 'postsaja.com', ?)");
$stmt->bind_param("ss", $email, $ip);

if ($stmt->execute()) {
    // Optional: send notification
    @mail('aiagent@postsaja.com', '📩 Signup baru: ' . $email,
        "Email baru: $email\nTarikh: " . date('Y-m-d H:i:s') . "\n\nSource: postsaja.com",
        "From: PostSaja <noreply@postsaja.com>\r\n");

    echo json_encode([
        'success' => true,
        'message' => 'Tahniah! 🎉 Anda dalam senarai. Kami akan email bila PostSaja rasmi dilancarkan. Sementara tu, terus urus kerja — biar AI sambung marketing.'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ralat simpan data. Cuba lagi nanti.']);
}

$stmt->close();
$conn->close();
