<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'You are not logged in.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$fileName = $data['fileName'] ?? '';
$disk = $data['disk'] ?? '';
$path = $data['path'] ?? '';

if (empty($fileName) || empty($disk)) {
    echo json_encode(['error' => 'Wrong input data.']);
    exit();
}

require '../../config.php';
require '../../functions.php';

if (!hasAccessToDisk($database, $_SESSION['username'], $disk)) {
    echo json_encode(['error' => 'Permission denied. (No access to disk)']);
    exit();
}

$expiryTime = time() + 300;
$token = generateToken($fileName, $disk, $path, $expiryTime, $_SESSION['username'], $secretKey);

// Zwrócenie tokenu
echo json_encode(['token' => $token]);
exit();
?>
