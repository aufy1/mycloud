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

$filePath = "../../uploads/cloud/{$data['disk']}" . (!empty($data['path']) ? "/{$data['path']}" : '') . "/{$data['fileName']}";
$realFilePath = realpath($filePath);
$baseDir = realpath("../../uploads/cloud/{$data['disk']}");

// Weryfikacja ścieżki pliku
if ($realFilePath === false || strpos($realFilePath, $baseDir) !== 0 || !file_exists($realFilePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found.']);
    exit();
}

// Przesyłanie pliku
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($realFilePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($realFilePath));

readfile($realFilePath);
exit();
?>
