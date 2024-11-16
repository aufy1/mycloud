<?php
session_start();

header('Content-Type: application/json');

require_once '../../config.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Użytkownik niezalogowany']);
    exit();
}

require_once '../../functions.php';

$disk = $_POST['disk_name'];
$fileName = $_POST['file_name'];
$path = $_POST['path'] ?? ''; // Ścieżka pliku (opcjonalnie)
$isFolder = isset($_POST['is_folder']) && $_POST['is_folder'] === 'true';

$owner = $_SESSION['username'];

if (empty($disk) || empty($fileName)) {
    echo json_encode(['success' => false, 'error' => 'Brak nazwy dysku lub nazwy pliku/folderu']);
    exit();
}

// Sprawdzenie dostępu do dysku
if (!hasAccessToDisk($database, $owner, $disk)) {
    echo json_encode(['success' => false, 'error' => 'Brak dostępu do dysku']);
    exit();
}

// Ścieżka do pliku lub folderu
$filePath = '../../uploads/cloud/' . $disk . '/' . ($path ? $path . '/' : '') . $fileName;

if (file_exists($filePath)) {
    if (is_dir($filePath)) {
        deleteFolder($filePath); // Twoja funkcja do usuwania folderów

        // Sprawdzamy, czy path zawiera "/". Jeśli tak, traktujemy go jako folder.
        if  (substr_count($path, '/') > 1) {
            $dbPath = $disk . '/';  // Jeśli path zawiera "/", traktujemy go jako folder
        } else {
            $dbPath = $disk . '/' . $path;  // Jeśli path nie zawiera "/", traktujemy to jako pojedynczy folder
        }
        
        
        // Usuwamy plik, uwzględniając także nazwę pliku w zapytaniu
        $query = "DELETE FROM files WHERE disk = ? AND path LIKE CONCAT(?, '%') AND file_name = ?";
        $stmt = mysqli_prepare($database, $query);
        if ($stmt) {
            // Używamy pełnej ścieżki folderu (dbPath) oraz samego fileName w zapytaniu
            mysqli_stmt_bind_param($stmt, "sss", $disk, $dbPath, $fileName);
            mysqli_stmt_execute($stmt);
        }

        if(!$path)
        {
            $dbPath = $disk . '/' . $fileName;
        }

        // Logowanie zapytania
        error_log('Wykonane zapytanie: ' . $query . ' z wartościami disk = ' . $disk . ', dbPath = ' . $dbPath . ', file_name = ' . $fileName);
        
        // Usuwamy wszystkie pliki i foldery, które mają ścieżki zaczynające się od dbPath/fileName
        $query2 = "DELETE FROM files WHERE disk = ? AND path LIKE CONCAT(?, '%')";
        $stmt2 = mysqli_prepare($database, $query2);
        if ($stmt2) {
            // Używamy pełnej ścieżki folderu (dbPath) i fileName, aby usunąć wszystkie pliki i foldery zawierające fileName
            mysqli_stmt_bind_param($stmt2, "ss", $disk, $dbPath);
            mysqli_stmt_execute($stmt2);
        }
        
        // Logowanie zapytania
        error_log('Wykonane zapytanie do usuwania wszystkich plików i folderów: ' . $query2 . ' z wartościami disk = ' . $disk . ', dbPath = ' . $dbPath);
        
        // Odpowiedź
        echo json_encode([
            'success' => true,
            'message' => 'Plik lub folder został usunięty: ' . $filePath,
            'query' => $query,  // Dodajemy zapytanie do odpowiedzi JSON
            'query_values' => ['disk' => $disk, 'dbPath' => $dbPath, 'file_name' => $fileName],  // Dodajemy wartości parametrów

        ]);
        

    } else {
        unlink($filePath); // Usuwamy plik

        // Tworzymy dbPath na podstawie pełnej ścieżki do pliku
        $dbPath = ($path ? $disk . '/' . $path : $disk . '/'); // Ścieżka pliku w bazie danych (bez samego fileName)
        
        // Usuwamy odpowiedni rekord w bazie danych
        $query = "DELETE FROM files WHERE disk = ? AND path = ? AND file_name = ?";
        $stmt = mysqli_prepare($database, $query);
        if ($stmt) {
            // Używamy pełnej ścieżki do folderu w dbPath oraz samego fileName
            mysqli_stmt_bind_param($stmt, "sss", $disk, $dbPath, $fileName);
            mysqli_stmt_execute($stmt);
        }
        
        // Logowanie zapytania
        error_log('Wykonane zapytanie: ' . $query . ' z wartościami disk = ' . $disk . ', dbPath = ' . $dbPath . ', file_name = ' . $fileName);
        
        echo json_encode([
            'success' => true,
            'message' => 'Plik został usunięty: ' . $filePath,
            'query' => $query,  // Dodajemy zapytanie do odpowiedzi JSON
            'query_values' => ['disk' => $disk, 'dbPath' => $dbPath, 'file_name' => $fileName]  // Dodajemy wartości parametrów
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Plik lub folder nie istnieje: ' . $filePath]);
}





mysqli_close($database);
?>
