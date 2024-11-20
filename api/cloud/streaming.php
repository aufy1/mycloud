<?php
session_start();

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';
if (empty($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'No token set.']);
    exit();
}

require '../../config.php';
require '../../functions.php';

// Funkcja do weryfikacji tokenu
function verifyToken($token, $secretKey) {
    list($payload, $signature) = explode('.', $token);

    // Obliczanie HMAC
    $calculatedHMAC = hash_hmac('sha256', base64_decode($payload), $secretKey);

    // Weryfikacja podpisu
    return $calculatedHMAC === $signature ? base64_decode($payload) : false;
}

// Weryfikacja tokenu
$payload = verifyToken($token, $secretKey);
if (!$payload) {
    http_response_code(403);
    echo json_encode(['error' => 'Wrong token.']);
    exit();
}

$data = json_decode($payload, true);

// Sprawdzanie, czy token nie wygasł
if ($data['expiryTime'] < time()) {
    http_response_code(403);
    echo json_encode(['error' => 'Token expired.']);
    exit();
}

// Sprawdzanie dostępu do pliku
if ($data['username'] !== $_SESSION['username']) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

// Ścieżka do pliku
$filePath = "../../uploads/cloud/{$data['disk']}" . (!empty($data['path']) ? "/{$data['path']}" : '') . "/{$data['fileName']}";
$realFilePath = realpath($filePath);
$baseDir = realpath("../../uploads/cloud/{$data['disk']}");

// Weryfikacja ścieżki pliku
if ($realFilePath === false || strpos($realFilePath, $baseDir) !== 0 || !file_exists($realFilePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found.']);
    exit();
}

// Określenie typu pliku
$fileExtension = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));
if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'bmp', 'svg'])) {
    // Obrazki i SVG
    header('Content-Type: image/' . ($fileExtension === 'svg' ? 'svg+xml' : $fileExtension));
    readfile($realFilePath);
} elseif ($fileExtension === 'mp4') {
    // Wideo
    header('Content-Type: video/mp4');
    readfile($realFilePath);
} elseif ($fileExtension === 'mp3') {
    // Audio
    header('Content-Type: audio/mpeg');
    readfile($realFilePath);
} else {
    // Typ pliku nieobsługiwany
    http_response_code(415); // Unsupported Media Type
    echo json_encode(['error' => 'Unsupported file type.']);
    exit();
}


exit();
?>
