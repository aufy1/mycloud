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
$owner = $_SESSION['username'];

if (empty($disk)) {
    echo json_encode(['success' => false, 'error' => 'Brak nazwy dysku']);
    exit();
}

if (!hasAccessToDisk($database, $owner, $disk)) {
    echo json_encode(['success' => false, 'error' => 'Brak dostępu do dysku']);
    exit();
}


// Włącz wyświetlanie błędów dla diagnostyki
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Usuwanie dysku z bazy danych
$query = "DELETE FROM disks WHERE disk_name = ? AND owner = ?";
$stmt = mysqli_prepare($database, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Błąd przygotowania zapytania: ' . mysqli_error($database)]);
    exit();
}
mysqli_stmt_bind_param($stmt, "ss", $disk, $owner);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    $folder_path = '../../uploads/cloud/' . $disk;

    function deleteFolder($folder_path) {
        if (is_dir($folder_path)) {
            $files = scandir($folder_path);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $filePath = $folder_path . '/' . $file;
                    is_dir($filePath) ? deleteFolder($filePath) : unlink($filePath);
                }
            }
            rmdir($folder_path);
        }
    }

    deleteFolder($folder_path);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Nie udało się usunąć dysku z bazy danych lub brak dostępu']);
}

mysqli_stmt_close($stmt);
mysqli_close($database);
?>
