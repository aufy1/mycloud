<?php
session_start();

require_once 'config.php';
require_once 'functions.php';

$response = $_POST['g-recaptcha-response'];
$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = array(
    'secret' => $recaptcha_secret,
    'response' => $response
);
$recaptcha_options = array(
    'http' => array(
        'method' => 'POST',
        'content' => http_build_query($recaptcha_data)
    )
);
$recaptcha_context = stream_context_create($recaptcha_options);
$recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);
$recaptcha_response = json_decode($recaptcha_result);

$user = htmlentities($_POST['user'], ENT_QUOTES, "UTF-8");
$pass = $_POST['pass'];
$ip_address = $_SERVER['REMOTE_ADDR'];
$login_datetime = date('Y-m-d H:i:s');
$successful_login = 0;

if (!$database) {
    echo "Błąd połączenia: " . mysqli_connect_error();
    exit;
}

mysqli_set_charset($database, 'utf8');




// Sprawdzenie liczby nieprawidłowych prób logowania w ciągu ostatnich 3 minut
$previous_login_datetime = date('Y-m-d H:i:s', strtotime('-3 minutes'));
$query = "SELECT COUNT(*) AS attempts FROM login_attempts WHERE ip_address = ? AND login_datetime > ? AND successful_login = 0";
$stmt = mysqli_prepare($database, $query);
mysqli_stmt_bind_param($stmt, "ss", $ip_address, $previous_login_datetime);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$login_attempts = $row['attempts'];
mysqli_stmt_close($stmt);

// Jeśli użytkownik miał ponad 2 nieprawidłowe próby logowania, sprawdź Captchę
if ($login_attempts > 2 || (isset($_GET['error']) && $_GET['error'] == 'captcha')) {
    if (!$recaptcha_response->success) {
        header("Location: index.php?error=captcha");
        exit();
    }
}

// Sprawdzenie poprawności danych logowania
$query = "SELECT password FROM users WHERE username=?";
$stmt = mysqli_prepare($database, $query);
mysqli_stmt_bind_param($stmt, "s", $user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $hashed_password_from_db = $row['password'];
    if (password_verify($pass, $hashed_password_from_db)) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user;
        $successful_login = 1;
    }
}
mysqli_stmt_close($stmt);

$screen_resolution = $_POST['screen_resolution'];
$window_resolution = $_POST['window_resolution'];
$color_depth = $_POST['color_depth'];
$cookies_enabled = $_POST['cookies_enabled'];
$browser_language = $_POST['browser_language'];
$browser_name = $_POST['browser_name'];

// Pobranie danych geolokalizacyjnych
$details = ip_location($ip_address);
$location = "{$details->country}, {$details->region}, {$details->city}";


$query = "INSERT INTO login_attempts (ip_address, login_datetime, successful_login, username, screen_resolution, window_resolution, color_depth, cookies_enabled, browser_name, browser_language, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($database, $query);
mysqli_stmt_bind_param($stmt, "ssissssisss", $ip_address, $login_datetime, $successful_login, $user, $screen_resolution, $window_resolution, $color_depth, $cookies_enabled, $browser_name, $browser_language, $location);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

mysqli_close($database);

// Przekierowanie w zależności od wyniku logowania
if ($successful_login) {
    header("Location: index.php");
    exit();
} else {
    header("Location: index.php?error=incorrect");
    exit();
}
?>