<?php
session_start();
date_default_timezone_set('Europe/Warsaw');

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']);
    exit();
}

require_once '../../config.php';

$user = $_SESSION['username'];
$recipient = trim($_POST['recipient']);
$message = trim($_POST['message']);

if (!preg_match('/^[a-zA-Z0-9_ -]+$/', $recipient)) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy odbiorca.']);
    exit();
}


// Sprawdzenie, czy odbiorca istnieje w bazie danych
$check_user_query = "SELECT username FROM users WHERE username = ?";
$stmt = $database->prepare($check_user_query);
$stmt->bind_param("s", $recipient);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Odbiorca nie istnieje.']);
    exit();
}
$stmt->close();

// Obsługa przesyłania pliku
$image_path = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = uniqid() . '.jpg';
    $uploadPath = "../../media/chat_images/" . $fileName;

    if (move_uploaded_file($fileTmpPath, $uploadPath)) {
        $image_path = "media/chat_images/" . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Nie udało się przesłać obrazu.']);
        exit();
    }
}

// Przygotowanie zapytania SQL w zależności od rodzaju wiadomości
if (!empty($image_path)) {
    $insert_query = "INSERT INTO messages (user, recipient, message, datetime) VALUES (?, ?, ?, NOW())";
    $message = $image_path; // Przechowywanie ścieżki obrazu w bazie danych jako wiadomość
} else {
    $insert_query = "INSERT INTO messages (user, recipient, message, datetime) VALUES (?, ?, ?, NOW())";
}

$stmt = $database->prepare($insert_query);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Błąd przy przygotowywaniu zapytania.']);
    exit();
}

$stmt->bind_param("sss", $user, $recipient, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => $message, 'image' => $image_path ? $image_path : null]);
} else {
    echo json_encode(['success' => false, 'message' => 'Błąd przy dodawaniu wiadomości.']);
}

$stmt->close();

?>
