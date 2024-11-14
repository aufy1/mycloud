<?php
session_start();

header('Content-Type: application/json');

// Upewniamy się, że użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Nie jesteś zalogowany.']);
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config.php';
require_once '../../functions.php';

$username = $_SESSION['username'];
$disk = isset($_POST['disk']) ? $_POST['disk'] : '';
$path = isset($_POST['path']) ? $_POST['path'] : '';

// Sprawdzanie, czy dysk i ścieżka są przekazane
if (empty($disk)) {
    echo json_encode(['error' => 'Brak określonego dysku.']);
    exit();
}

// Sprawdzamy, czy użytkownik ma dostęp do dysku
if (!hasAccessToDisk($database, $_SESSION['username'], $disk)) {
    echo json_encode(['error' => 'Brak dostępu do tego dysku.']);
    exit();
}

// Sprawdzamy, czy użytkownik chce utworzyć nowy katalog
if (isset($_POST['new_folder']) && !empty($_POST['new_folder'])) {
    $new_folder = trim($_POST['new_folder']);
    
    // Budowanie pełnej ścieżki katalogu
    $base_directory = '../../uploads/cloud/' . $disk . '/';
    $full_path = rtrim($base_directory . trim($path, '/') . '/' . $new_folder, '/');

    // Logowanie ścieżki dla debugowania
    error_log("Ścieżka katalogu: " . $full_path);

    // Sprawdzamy, czy katalog już istnieje w systemie plików
    if (is_dir($full_path)) {
        echo json_encode(['error' => 'Katalog o tej nazwie już istnieje w systemie.']);
        exit();
    }

    // Tworzenie katalogu w systemie plików
    if (!mkdir($full_path, 0777, true)) {
        echo json_encode(['error' => 'Nie udało się stworzyć katalogu.']);
        exit();
    }

    // Dodanie katalogu do bazy danych
    $query = "SELECT COUNT(*) FROM files WHERE disk = ? AND path = ?";
    $stmt = $database->prepare($query);
    if ($stmt === false) {
        echo json_encode(['error' => 'Błąd przygotowania zapytania: ' . $database->error]);
        exit();
    }

    // Sprawdzenie, czy folder już istnieje w bazie danych
    $check_path = rtrim($path, '/') . '/' . $new_folder;
    $stmt->bind_param('ss', $disk, $check_path);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();

    // Zamykamy wynik zapytania
    $stmt->close();

    if ($count > 0) {
        echo json_encode(['error' => 'Katalog o tej nazwie już istnieje w bazie danych.']);
        exit();
    } else {

        // Ustawienie zmiennych dla zapytania
        $file_name = $new_folder; // Nazwa katalogu
        $file_type = 'folder'; // Typ pliku jako folder
        $owner = $_SESSION['username']; // Użytkownik tworzący katalog
        $shared_with = ''; // Puste pole shared_with

        // Przygotowanie zapytania SQL do dodania katalogu
        $query = "INSERT INTO files (disk, path, file_name, file_type, owner, shared_with, last_modified) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $database->prepare($query);
        if ($stmt === false) {
            echo json_encode(['error' => 'Błąd przygotowania zapytania: ' . $database->error]);
            exit();
        }

 
        // Tworzenie ścieżki do katalogu w bazie danych, upewniając się, że ukośnik jest po nazwie dysku
$db_path = $disk . '/' . ltrim(trim($path, '/'), '/'); // Upewniamy się, że `$disk` ma ukośnik po, a `$path` jest dobrze sformatowane


        $stmt->bind_param('ssssss', $disk, $db_path, $file_name, $file_type, $owner, $shared_with);

        // Debugowanie wartości przed wykonaniem zapytania
        echo json_encode([
            'debug' => [
                'disk' => $disk,
                'path' => $db_path,
                'file_name' => $file_name,
                'file_type' => $file_type,
                'owner' => $owner,
                'shared_with' => $shared_with
            ]
        ]);

        // Wykonanie zapytania
        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Błąd zapytania: ' . $stmt->error]);
            exit();
        }

        // Zamykamy zapytanie
        $stmt->close();
        echo json_encode(['success' => 'Katalog został utworzony pomyślnie!']);
        
        exit();
    }
} else {
    echo json_encode(['error' => 'Brak nazwy katalogu.']);
}
