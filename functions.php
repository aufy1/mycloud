<?php
function shouldDisplayCaptcha() {
    global $database;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $previous_login_datetime = date('Y-m-d H:i:s', strtotime('-3 minutes'));
    
    if(!$database) {
        echo "Błąd: ". mysqli_connect_errno() . " " . mysqli_connect_error();
        exit();
    }
    
    mysqli_query($database, "SET NAMES 'utf8'");
    
    // Przygotowanie zapytania do bazy danych
    $stmt = mysqli_prepare($database, "SELECT COUNT(*) AS attempts FROM login_attempts WHERE ip_address = ? AND login_datetime > ? AND successful_login = 0");
    
    // Sprawdzenie, czy zapytanie zostało poprawnie przygotowane
    if ($stmt === false) {
        echo "Błąd przygotowania zapytania: " . mysqli_error($database);
        exit();
    }
    
    // Wiązanie parametrów i wykonanie zapytania
    mysqli_stmt_bind_param($stmt, "ss", $ip_address, $previous_login_datetime);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $login_attempts = $row['attempts'];
    mysqli_stmt_close($stmt);
    
    mysqli_close($database);
    
    return $login_attempts > 2;
}

function ip_location($ip) {
    $json = file_get_contents("http://ipinfo.io/{$ip}/geo");
    $details = json_decode($json);
    return $details;
}

function hasAccessToDisk($database, $username, $disk) {
    // Zapytanie do bazy danych, aby pobrać nazwy dysków użytkownika lub te, które są z nim udostępnione
    $query = "SELECT disk_name FROM disks WHERE (owner = ? OR FIND_IN_SET(?, shared_with))";
    $stmt = mysqli_prepare($database, $query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Błąd zapytania SQL: ' . mysqli_error($database)]);
        exit();
    }

    // Przypisanie parametrów
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);

    // Wykonanie zapytania
    mysqli_stmt_execute($stmt);

    // Pobranie wyników
    mysqli_stmt_bind_result($stmt, $disk_name);
    $available_disks = [];

    while (mysqli_stmt_fetch($stmt)) {
        $available_disks[] = $disk_name;
    }

    // Zamykamy zapytanie
    mysqli_stmt_close($stmt);

    // Sprawdzenie, czy żądany dysk znajduje się na liście dostępnych dysków
    return in_array($disk, $available_disks);
}


        // Funkcja do usuwania folderu i jego zawartości
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


/*
        function checkFileExists($database, $disk, $fileName, $path) {
            // Przygotowanie zapytania SQL, które sprawdza, czy folder istnieje w danym path
            $query = "SELECT COUNT(*) FROM files WHERE file_name = ? AND disk = ? AND file_type = 'folder' AND path = ?";
            $stmt = $database->prepare($query);
            $count=0;

            if (!$stmt) {
                echo "Błąd zapytania: " . mysqli_error($database);
                exit();
            }
        
            if($path)
            {
                $dbPath = $disk . '/' . $path;  
            }
            else
            {
                $dbPath = $disk . '/';
            }

        //    if (substr_count($path, '/') > 1) 
        //    {
        //        $dbPath = $disk . '/' . $path . '/' . $fileName;
        //   }

            echo $dbPath;

            $stmt->bind_param('sss', $fileName, $disk, $dbPath);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
        
            // Jeśli wynik jest większy niż 0, to folder istnieje
            return $count > 0;
        }
*/



        // Funkcja do tworzenia tokenu
function generateToken($fileName, $disk, $path, $expiryTime, $username, $secretKey) {
    $payload = json_encode([
        'fileName' => $fileName,
        'disk' => $disk,
        'path' => $path ?? '',
        'expiryTime' => $expiryTime,
        'username' => $username
    ]);
    
    // Generowanie tokenu
    $base64Payload = base64_encode($payload);
    $hmac = hash_hmac('sha256', $payload, $secretKey);

    return $base64Payload . '.' . $hmac;
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}


?>