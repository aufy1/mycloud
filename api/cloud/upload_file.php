<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Użytkownik niezalogowany']);
    exit();
}

$disk = isset($_POST['disk']) ? $_POST['disk'] : ''; // Odbieramy zmienną disk z formularza

require_once '../../functions.php';

if (!hasAccessToDisk($database, $_SESSION['username'], $disk)) {
    echo json_encode(['success' => false, 'error' => 'Brak dostępu do dysku']);
    exit();
}

// Sprawdzamy, czy plik został przesłany
if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
    $file_name = $_FILES['file_upload']['name'];
    $file_tmp = $_FILES['file_upload']['tmp_name'];
    $file_size = $_FILES['file_upload']['size'];
    $file_type = pathinfo($file_name, PATHINFO_EXTENSION);
    $owner = $_SESSION['username']; // Przechowujemy nazwę użytkownika

    // Jeśli nie określono dysku, wyświetl błąd
    if (empty($disk)) {
        echo json_encode(["success" => false, "error" => "Brak określonego dysku."]);
        exit;
    }

    // Ustawienie folderu na pliki, uwzględniając nazwę dysku
    $upload_dir = '../../uploads/cloud/' . $disk . '/'; // Dynamiczna ścieżka do folderu dla dysku
    if (!file_exists($upload_dir)) {
        // Tworzymy folder, jeśli nie istnieje
        mkdir($upload_dir, 0777, true);
    }

    $upload_path = $upload_dir . basename($file_name);

    // Sprawdzamy, czy plik już istnieje
    if (file_exists($upload_path)) {
        echo json_encode(["success" => false, "error" => "Plik o tej nazwie już istnieje."]);
        exit;
    }

    // Przesyłanie pliku na serwer
    if (move_uploaded_file($file_tmp, $upload_path)) {
        // Tworzenie ścieżki do zapisania w bazie danych
        $path = $disk . '/';

        // Zapisanie informacji o pliku w bazie danych
        $query = "INSERT INTO files (file_name, disk, owner, file_type, path, last_modified) VALUES (?, ?, ?, ?, ?, NOW())";
        
        // Zmiana połączenia z $conn na $database
        $stmt = $database->prepare($query);  // Używamy $database do przygotowania zapytania
        $stmt->bind_param('sssss', $file_name, $disk, $owner, $file_type, $path); // Bindowanie parametrów
        $stmt->execute(); // Wykonanie zapytania

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Błąd podczas przesyłania pliku."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Nie wybrano pliku do przesłania."]);
}
?>
