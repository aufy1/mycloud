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

// Weryfikacja dostępu do dysku
if (!hasAccessToDisk($database, $owner, $disk)) {
    echo json_encode(['success' => false, 'error' => 'Brak dostępu do dysku']);
    exit();
}


// Usuwanie plików z tabeli files
$query = "DELETE FROM files WHERE disk = ?";
$stmt = mysqli_prepare($database, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Błąd przygotowania zapytania do usuwania plików: ' . mysqli_error($database)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $disk);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_error($stmt)) {
    echo json_encode(['success' => false, 'error' => 'Błąd podczas usuwania plików z tabeli: ' . mysqli_stmt_error($stmt)]);
    exit();
}

// Usunięcie wpisu dysku z tabeli disks
$query = "DELETE FROM disks WHERE disk_name = ? AND owner = ?";
$stmt = mysqli_prepare($database, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Błąd przygotowania zapytania do usuwania dysku: ' . mysqli_error($database)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "ss", $disk, $owner);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_error($stmt)) {
    echo json_encode(['success' => false, 'error' => 'Błąd podczas usuwania dysku z tabeli: ' . mysqli_stmt_error($stmt)]);
    exit();
}

// Sprawdzenie, czy dysk został usunięty
if (mysqli_stmt_affected_rows($stmt) > 0) {
    // Ścieżka do folderu na dysku
    $folder_path = '../../uploads/cloud/' . $disk;

    // Usuwanie folderu
    if (file_exists($folder_path) && is_dir($folder_path)) {
        deleteFolder($folder_path);
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Nie udało się usunąć dysku z bazy danych lub brak dostępu']);
}

mysqli_stmt_close($stmt);
mysqli_close($database);
?>
