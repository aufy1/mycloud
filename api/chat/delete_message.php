<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Brak dostępu']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($message_id > 0) {
        $delete_query = "DELETE FROM messages WHERE id = ?";
        $stmt = $database->prepare($delete_query);
        $stmt->bind_param('i', $message_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Wiadomość usunięta']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Błąd przy usuwaniu wiadomości']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID wiadomości']);
    }
}
?>
