<?php
session_start();
// Ustawiamy nagłówek odpowiedzi na JSON
header('Content-Type: application/json');

require_once('../../config.php');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Użytkownik niezalogowany']);
    exit();
}

// Sprawdzamy, czy połączenie się powiodło
if (mysqli_connect_errno()) {
    echo json_encode(['success' => false, 'error' => 'Błąd połączenia z bazą danych: ' . mysqli_connect_error()]);
    exit;
}

// Ścieżka do folderu, w którym mają być tworzone nowe dyski
$cloudDir = '../../uploads/cloud/';

// Funkcja generująca unikalny identyfikator (12 znaków)
function generateUniqueId($length = 12) {
    return bin2hex(random_bytes($length / 2)); // Generuje losowy ciąg o długości 12 znaków
}

// Funkcja do zapisania informacji o dysku w bazie danych
function saveDiskToDatabase($owner, $disk_name, $shared_with, $database) {
    $created_at = date('Y-m-d H:i:s'); // Aktualna data i czas
    $query = "INSERT INTO disks (owner, disk_name, shared_with, created_at) 
              VALUES (?, ?, ?, ?)";
    
    // Przygotowanie zapytania do bazy danych
    $stmt = mysqli_prepare($database, $query);
    
    // Wiązanie parametrów z zapytaniem
    mysqli_stmt_bind_param($stmt, "ssss", $owner, $disk_name, $shared_with, $created_at);
    
    // Wykonanie zapytania
    mysqli_stmt_execute($stmt);
    
    // Sprawdzanie, czy zapytanie się powiodło
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        return true;
    } else {
        return false;
    }
}

// Generowanie unikalnego identyfikatora dla folderu
$newFolderName = generateUniqueId();

// Ścieżka do nowego folderu
$newFolderPath = $cloudDir . $newFolderName;

// Sprawdzamy, czy katalog docelowy jest dostępny
if (!is_writable($cloudDir)) {
    echo json_encode(['success' => false, 'error' => 'Brak uprawnień do zapisu w katalogu ' . $cloudDir]);
    exit;
}

// Tworzenie folderu, jeśli jeszcze nie istnieje
if (!file_exists($newFolderPath)) {
    if (mkdir($newFolderPath, 0777, true)) {
        // Zapis do bazy danych
        $owner = $_SESSION['username']; // Pobieramy nazwę użytkownika z sesji
        $disk_name = $newFolderName; // Nazwa folderu to identyfikator
        $shared_with = ''; // Na początku folder nie jest udostępniany
        
        if (saveDiskToDatabase($owner, $disk_name, $shared_with, $database)) {
            echo json_encode(['success' => true, 'folder' => $newFolderName]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Nie udało się zapisać informacji o dysku w bazie danych']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Nie udało się utworzyć folderu']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Folder już istnieje']);
}

// Zamykamy połączenie z bazą danych
mysqli_close($database);
?>
