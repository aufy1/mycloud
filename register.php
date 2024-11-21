<?php
// Sprawdź, czy formularz rejestracji został przesłany
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sprawdź, czy wszystkie wymagane pola są ustawione
    if (isset($_POST["register_user"]) && isset($_POST["register_pass"]) && isset($_POST["register_pass_repeat"])) {
        // Pobierz dane z formularza
        $username = $_POST["register_user"];
        $password = $_POST["register_pass"];
        $password_repeat = $_POST["register_pass_repeat"];

        require_once 'config.php';

        // Walidacja pól formularza (możesz dodać własne warunki walidacji)
        if (empty($username) || empty($password) || empty($password_repeat)) {
            // Przekieruj użytkownika z powrotem do formularza rejestracji z komunikatem o błędzie
            header("Location: index.php?error=emptyfields");
            exit();
        } elseif ($password !== $password_repeat) {
            // Przekieruj użytkownika z powrotem do formularza rejestracji z komunikatem o niezgodności hasła
            header("Location: index.php?error=passwordcheck");
            exit();
        } else {
            // Połącz się z bazą danych (zmień dane dostępowe do bazy danych na odpowiednie)
            global $database;

            // Sprawdź połączenie z bazą danych
            if (!$database) {
                die("Błąd połączenia z bazą danych: " . mysqli_connect_error());
            }

            // Zabezpiecz dane przed wstrzykiwaniem SQL
            $username = mysqli_real_escape_string($database, $username);

            // Sprawdź, czy użytkownik o podanej nazwie już istnieje
            $sql = "SELECT * FROM users WHERE username='$username'";
            $result = mysqli_query($database, $sql);
            if (mysqli_num_rows($result) > 0) {
                // Przekieruj użytkownika z powrotem do formularza rejestracji z komunikatem o zajętej nazwie użytkownika
                header("Location: index.php?error=usertaken");
                exit();
            } else {
                // Zaszyfruj hasło przed zapisaniem do bazy danych (możesz użyć bardziej zaawansowanej metody szyfrowania, np. bcrypt)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Wstaw dane nowego użytkownika do bazy danych
                $sql = "INSERT INTO users (username, password) VALUES ('$username', '$hashed_password')";
                if (mysqli_query($database, $sql)) {
                    // Jeśli rejestracja zakończyła się sukcesem, przekieruj użytkownika do strony logowania
                    header("Location: index.php?success=registered");
                    exit();
                } else {
                    // Jeśli wystąpił błąd podczas dodawania użytkownika do bazy danych
                    echo "Błąd podczas rejestracji: " . mysqli_error($database);
                }
            }

            // Zamknij połączenie z bazą danych
            mysqli_close($database);
        }
    } else {
        // Jeśli nie wszystkie wymagane pola są ustawione
        header("Location: index.php?error=missingfields");
        exit();
    }
} else {
    // Jeśli użytkownik próbuje uzyskać dostęp do strony rejestracji bez przesłania formularza
    header("Location: index.php");
    exit();
}
?>
