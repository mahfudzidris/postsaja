<?php
// total.php — Count PostSaja prospects (email signups)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dbHost = 'localhost';
$dbUser = 'homesta3_intro_database';
$dbPass = 'PostSaja@2026';
$dbName = 'homesta3_intro';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ralat server.']);
    exit;
}

$result = $conn->query("SELECT COUNT(*) as total FROM postsaja_subscribers");
$row = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'total' => (int) $row['total']
]);

$conn->close();
