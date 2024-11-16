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
$path = $_POST['path']; // Ścieżka pliku (jeśli jest)

$owner = $_SESSION['username'];

if (empty($disk) || empty($fileName)) {
    echo json_encode(['success' => false, 'error' => 'Brak nazwy dysku lub nazwy pliku']);
    exit();
}

// Sprawdzenie dostępu do dysku
if (!hasAccessToDisk($database, $owner, $disk)) {
    echo json_encode(['success' => false, 'error' => 'Brak dostępu do dysku']);
    exit();
}

// Sprawdzamy, czy to folder
$isFolder = isset($_POST['is_folder']) && $_POST['is_folder'] === 'true';

// Logowanie parametrów do debugowania
error_log('is_folder: ' . $isFolder);
error_log('fileName: ' . $fileName);
error_log('path: ' . $path);
error_log('disk: ' . $disk);

// Jeśli to folder
if ($isFolder) {
    // Sprawdzamy ścieżkę folderu
    $filePath = '../../uploads/cloud/' . $disk . '/' . ($path ? $path . '/' : '') . $fileName;
    
    // Logowanie ścieżki
    error_log('Folder path: ' . $filePath);

    if (file_exists($filePath) && is_dir($filePath)) {
        deleteFolder($filePath);  // Usuwamy folder (Twoja funkcja deleteFolder)
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Folder nie istnieje: ' . $filePath]);
    }
} else {
    // Jeśli to plik
    $filePath = '../../uploads/cloud/' . $disk . '/' . ($path ? $path . '/' : '') . $fileName;
    
    // Logowanie ścieżki
    error_log('File path: ' . $filePath);

    if (file_exists($filePath)) {
        unlink($filePath);  // Usuwamy plik
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Plik nie istnieje: ' . $filePath]);
    }
}

mysqli_close($database);


?>
