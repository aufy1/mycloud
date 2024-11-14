<?php
// Upewniamy się, że użytkownik jest zalogowany
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Nie jesteś zalogowany.']);
    exit();
}

header('Content-Type: application/json');

// Ustawienia i połączenie z bazą danych
require_once '../../config.php';
require_once '../../functions.php';

$username = $_SESSION['username'];
$disk = isset($_POST['disk']) ? $_POST['disk'] : '';
$path = isset($_POST['path']) ? $_POST['path'] : '';

// Sprawdzamy, czy dysk i ścieżka są przekazane
if (empty($disk)) {
    echo json_encode(['error' => 'Brak określonego dysku.']);
    exit();
}

// Ustawienie folderu na pliki, uwzględniając nazwę dysku i ścieżkę
$upload_dir = '../../uploads/cloud/' . $disk . '/'; // Folder dla dysku

if (!empty($path)) {
    $upload_dir .= $path . '/';  // Dodajemy 'path' do ścieżki
}

// Sprawdzamy, czy katalog istnieje, jeśli nie, tworzymy go
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Tworzenie katalogu, jeśli nie istnieje
}

// Obsługa pliku
if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
    $file_name = $_FILES['file_upload']['name'];
    $file_tmp = $_FILES['file_upload']['tmp_name'];
    $file_size = $_FILES['file_upload']['size'];
    $file_type = pathinfo($file_name, PATHINFO_EXTENSION);
    $owner = $_SESSION['username'];
    $shared_with = ''; // Puste pole shared_with

    // Pełna ścieżka do pliku
    $upload_path = $upload_dir . basename($file_name);

    // Sprawdzamy, czy plik już istnieje
    if (file_exists($upload_path)) {
        echo json_encode(["error" => "Plik o tej nazwie już istnieje w systemie."]);
        exit();
    }

    // Przesyłanie pliku na serwer
    if (move_uploaded_file($file_tmp, $upload_path)) {
        // Tworzymy pełną ścieżkę do zapisu w bazie danych
        $db_path = $disk . '/' . (!empty($path) ? $path : '');

        // Zapisanie informacji o pliku w bazie danych
        $query = "INSERT INTO files (file_name, disk, owner, file_type, path, shared_with, last_modified) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $database->prepare($query);
        if ($stmt === false) {
            echo json_encode(['error' => 'Błąd przygotowania zapytania: ' . $database->error]);
            exit();
        }

        // Bindowanie parametrów
        $stmt->bind_param('ssssss', $file_name, $disk, $owner, $file_type, $db_path, $shared_with);

        // Wykonanie zapytania
        if ($stmt->execute()) {
            echo json_encode(["success" => "Plik został przesłany pomyślnie."]);
        } else {
            echo json_encode(["error" => "Błąd zapytania: " . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(["error" => "Błąd podczas przesyłania pliku."]);
    }
} else {
    echo json_encode(["error" => "Nie wybrano pliku do przesłania."]);
}
?>
