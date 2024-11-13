<?php
session_start();

// Upewniamy się, że użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header('Location: ../../index.php');
    exit();
}

$username = $_SESSION['username'];
$disk = isset($_GET['disk']) ? $_GET['disk'] : '';

// Sprawdzanie, czy dysk istnieje
if (empty($disk)) {
    echo "Brak określonego dysku.";
    exit;
}

// Połączenie z bazą danych
require_once '../../config.php';
require_once '../../functions.php';

if (!hasAccessToDisk($database, $_SESSION['username'], $disk)) {
    header('Location: ../../storage.php'); // Przekierowanie na stronę storage.php
    exit();
}

// Pobieramy zmienną path z adresu URL (GET)
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Sprawdzamy, czy użytkownik chce utworzyć nowy katalog
if (isset($_GET['new_folder']) && !empty($_GET['new_folder'])) {
    $new_folder = trim($_GET['new_folder']);
    $full_path = $disk . '/' . trim($path, '/') . '/' . $new_folder; // Pełna ścieżka folderu

    // Sprawdzamy, czy katalog już istnieje
    $query = "SELECT COUNT(*) FROM files WHERE disk = ? AND path = ?";
    $stmt = $database->prepare($query);
    $stmt->bind_param('ss', $disk, $full_path);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();

    if ($count > 0) {
        echo "Katalog o tej nazwie już istnieje!";
    } else {
        // Dodanie katalogu do bazy danych
        $query = "INSERT INTO files (disk, path, file_name, file_type, owner, last_modified) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $database->prepare($query);
        $file_name = $new_folder; // Nazwa katalogu jako "file_name"
        $file_type = 'folder'; // Typ pliku jako folder
        $owner = $_SESSION['username']; // Użytkownik, który tworzy katalog
        $stmt->bind_param('sssss', $disk, $full_path, $file_name, $file_type, $owner);
        $stmt->execute();

        echo "Katalog został utworzony pomyślnie!";
    }
}

// Pobieramy pliki z wybranej ścieżki
$query = "SELECT * FROM files WHERE disk = ? AND path LIKE ?";
$stmt = $database->prepare($query);

// Formatowanie ścieżki dla SQL
$path_query = $disk . '/' . trim($path, '/') . '%'; // np. "dysk1/folder1/%"
$stmt->bind_param('ss', $disk, $path_query);
$stmt->execute();
$result = $stmt->get_result();

// Tablica na przechowanie wyników
$files = [];
while ($row = $result->fetch_assoc()) {
    $files[] = $row;
}
