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
$path = $_POST['path'] ?? '';
$isFolder = isset($_POST['is_folder']) && $_POST['is_folder'] === 'true';

$owner = $_SESSION['username'];

if (empty($disk) || empty($fileName)) {
    echo json_encode(['success' => false, 'error' => 'Brak nazwy dysku lub nazwy pliku/folderu']);
    exit();
}

if (!hasAccessToDisk($database, $owner, $disk)) {
    echo json_encode(['success' => false, 'error' => 'Brak dostępu do dysku']);
    exit();
}

$filePath = '../../uploads/cloud/' . $disk . '/' . ($path ? $path . '/' : '') . $fileName;

if (file_exists($filePath)) {
    if (is_dir($filePath)) {
        deleteFolder($filePath);

            $dbPath = $disk . '/' . $path; 

  //      if($path && substr_count($path, '/') == 0) 
  //      {
   //         $dbPath = $disk . '/';
   //     }
        
        
        $query = "DELETE FROM files WHERE disk = ? AND path = ? AND file_name = ?";
        $stmt = mysqli_prepare($database, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $disk, $dbPath, $fileName);
            mysqli_stmt_execute($stmt);
        }

        error_log('Wykonane zapytanie: ' . $query . ' z wartościami disk = ' . $disk . ', dbPath = ' . $dbPath . ', file_name = ' . $fileName);
        
        $dbPath = $disk . '/' . $path . '/' . $fileName;

        if(((substr_count($path, '/') == 0) && $path) || !$path)
        {
            $dbPath = $disk . '/' . $fileName;
        }

        $query2 = "DELETE FROM files WHERE disk = ? AND path LIKE CONCAT(?, '%')";
        $stmt2 = mysqli_prepare($database, $query2);
        if ($stmt2) {
            mysqli_stmt_bind_param($stmt2, "ss", $disk, $dbPath);
            mysqli_stmt_execute($stmt2);
        }

        error_log('Wykonane zapytanie do usuwania wszystkich plików i folderów: ' . $query2 . ' z wartościami disk = ' . $disk . ', dbPath = ' . $dbPath);
        
        echo json_encode(['success' => true,'message' => 'Plik lub folder został usunięty: ' . $filePath]);
        

    } else {
        unlink($filePath);

        $dbPath = ($path ? $disk . '/' . $path : $disk . '/');
        
        $query = "DELETE FROM files WHERE disk = ? AND path = ? AND file_name = ?";
        $stmt = mysqli_prepare($database, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $disk, $dbPath, $fileName);
            mysqli_stmt_execute($stmt);
        }
        
        error_log('Wykonane zapytanie: ' . $query . ' z wartościami disk = ' . $disk . ', dbPath = ' . $dbPath . ', file_name = ' . $fileName);
        
        echo json_encode(['success' => true, 'message' => 'Plik został usunięty: ' . $filePath,]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Plik lub folder nie istnieje: ' . $filePath]);
}


mysqli_close($database);
?>
