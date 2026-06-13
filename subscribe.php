<?php
// subscribe.php — Handle PostSaja email signups
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/subscribers.json';

// Create data directory if needed
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sila masukkan email.']);
    exit;
}

$email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email tak sah. Cuba lagi.']);
    exit;
}

// Load existing subscribers
$subscribers = [];
if (file_exists($dataFile)) {
    $subscribers = json_decode(file_get_contents($dataFile), true) ?? [];
}

// Check duplicate
foreach ($subscribers as $s) {
    if ($s['email'] === $email) {
        echo json_encode([
            'success' => true,
            'message' => 'Email dah didaftarkan! Kami akan maklumkan bila pelancaran tiba.'
        ]);
        exit;
    }
}

// Add new subscriber
$subscribers[] = [
    'email' => $email,
    'subscribed_at' => date('Y-m-d H:i:s'),
    'source' => 'postsaja.com',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
];

// Save
file_put_contents($dataFile, json_encode($subscribers, JSON_PRETTY_PRINT));

// Optional: send notification email to Mahfudz
$to = 'aiagent@postsaja.com';
$subject = '📩 Signup baru: ' . $email;
$headers = "From: PostSaja <noreply@postsaja.com>\r\n";
$body = "Email baru: $email\nTarikh: " . date('Y-m-d H:i:s') . "\n\nSource: postsaja.com";
@mail($to, $subject, $body, $headers);

// Respond
echo json_encode([
    'success' => true,
    'message' => 'Tahniah! 🎉 Anda dalam senarai. Kami akan email bila PostSaja rasmi dilancarkan. Sementara tu, terus urus kerja — biar AI sambung marketing.'
]);
