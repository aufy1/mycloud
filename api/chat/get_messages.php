<?php
session_start();

// Ustawienie strefy czasowej
date_default_timezone_set('Europe/Warsaw');

if (!isset($_SESSION['username'])) {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

// Ustawienie aktualnego użytkownika
$user = $_SESSION['username'];

// Pobranie użytkownika do czatu
$selected_user = isset($_GET['user']) ? $_GET['user'] : '';

// Walidacja użytkownika (np. tylko alfanumeryczne znaki)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $selected_user)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid user']));
}

// Przygotowanie zapytania
$message_query = "SELECT * FROM messages WHERE (user=? AND recipient=?) OR (user=? AND recipient=?) ORDER BY datetime DESC";
$stmt = $database->prepare($message_query);
$stmt->bind_param("ssss", $user, $selected_user, $selected_user, $user);
$stmt->execute();
$message_result = $stmt->get_result();

$messages = [];
while ($row = $message_result->fetch_assoc()) {
    $row['datetime'] = date('Y-m-d H:i:s', strtotime($row['datetime']));

    // Jeśli wiadomość zawiera ścieżkę do pliku z katalogu chat_images, traktujemy ją jako obraz
    $row['is_image'] = preg_match('/^media\/chat_images\/.+\.jpg$/', $row['message']) ? true : false;

    $messages[] = $row;
}

$stmt->close();
echo json_encode(['success' => true, 'messages' => $messages]);
?>
