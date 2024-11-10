<?php
session_start();
require_once '../config.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit();
}

// Pobierz dane użytkownika z sesji
$username = $_SESSION['username'];

// Sprawdź, czy formularz został przesłany
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Połączenie z bazą danych
    global $database;

    // Sprawdź połączenie z bazą danych
    if (!$database) {
        die("Błąd połączenia z bazą danych: " . mysqli_connect_error());
    }

    // Aktualizacja zdjęcia profilowego
    if (!empty($_FILES['profilePhoto']['name'])) {
        $target_dir = "../media/user_images/";
        $target_file = $target_dir . $username . ".jpg"; // Nadpisanie pliku .jpg z nazwą użytkownika

        // Sprawdzenie, czy plik jest obrazem
        $check = getimagesize($_FILES["profilePhoto"]["tmp_name"]);
        if ($check === false) {
            $errors[] = "Plik nie jest obrazem.";
        }

        // Przekonwertuj plik na .jpg i zapisz
        if (empty($errors)) {
            $imageFileType = strtolower(pathinfo($_FILES["profilePhoto"]["name"], PATHINFO_EXTENSION));
            
            switch ($imageFileType) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($_FILES["profilePhoto"]["tmp_name"]);
                    break;
                case 'png':
                    $image = imagecreatefrompng($_FILES["profilePhoto"]["tmp_name"]);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($_FILES["profilePhoto"]["tmp_name"]);
                    break;
                default:
                    $errors[] = "Dozwolone formaty plików to JPG, JPEG, PNG, GIF.";
                    break;
            }

            // Jeśli nie ma błędów i obraz został poprawnie załadowany, zapisujemy go w formacie JPG
            if (empty($errors) && $image !== false) {
                if (imagejpeg($image, $target_file, 90)) {
                    imagedestroy($image);
                } else {
                    $errors[] = "Błąd podczas zapisywania zdjęcia.";
                }
            }
        }
    }

    // Zmiana hasła
    if (!empty($_POST['currentPassword']) && !empty($_POST['newPassword']) && !empty($_POST['confirmPassword'])) {
        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        $confirm_password = $_POST['confirmPassword'];

        // Sprawdź, czy nowe hasło i potwierdzenie hasła są takie same
        if ($new_password !== $confirm_password) {
            $errors[] = "Nowe hasło i potwierdzenie hasła nie są takie same.";
        } else {
            // Pobierz aktualne hasło z bazy danych
            $sql = "SELECT password FROM users WHERE username = '$username'";
            $result = mysqli_query($database, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);

                // Sprawdź, czy aktualne hasło jest poprawne
                if (password_verify($current_password, $row['password'])) {
                    // Szyfruj nowe hasło
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Zaktualizuj hasło w bazie danych
                    $sql = "UPDATE users SET password = '$hashed_password' WHERE username = '$username'";
                    if (!mysqli_query($database, $sql)) {
                        $errors[] = "Błąd podczas aktualizacji hasła.";
                    }
                } else {
                    $errors[] = "Aktualne hasło jest niepoprawne.";
                }
            }
        }
    }

    // Wyświetl komunikaty o sukcesie lub błędach
    if (empty($errors)) {
        header("Location: ../settings.php?=success");
        exit();
    } else {
        header("Location: ../settings.php?=error");
        exit();
    }
}
?>
