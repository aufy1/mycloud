<?php 
session_start();
require_once 'head.php'; 
require_once 'config.php'; 
require_once 'functions.php'; 
?>

<?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
<body class="bg-gray-100">

<?php require_once 'header.php'; ?>

<main class="py-10">
    <section class="sekcja1">    
        <div class="container mx-auto p-4 bg-white rounded-lg shadow-md">
            Strona powitalna. Zanim może się ona pojawić, powinno się zrealizować proces logowania, a na każdej stronie aplikacji powinno się sprawdzać sesję.
        </div>    
    </section>
</main>

<?php require_once 'footer.php'; ?>

<?php else: ?>

<body id="loginpage" class="text-center bg-gray-100">

<main id="formSignin" class="max-w-md mx-auto mt-20 p-8 bg-white rounded-lg shadow-md">
  <form action="verify.php" method="post">
    <h1 class="text-2xl font-semibold text-gray-700 mb-4">Please sign in</h1>

    <input type="hidden" name="screen_resolution" id="screen_resolution">
    <input type="hidden" name="window_resolution" id="window_resolution">
    <input type="hidden" name="color_depth" id="color_depth">
    <input type="hidden" name="cookies_enabled" id="cookies_enabled">
    <input type="hidden" name="browser_language" id="browser_language">
    <input type="hidden" name="browser_name" id="browser_name">

    <div class="mb-4">
      <input type="text" name="user" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Username">
    </div>
    
    <div class="mb-4">
      <input type="password" name="pass" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password">
    </div>

    <?php if(shouldDisplayCaptcha()): ?>
      <div class="mb-4">
        <div class="g-recaptcha" data-sitekey="6LcLe54pAAAAAPpZGs4QcEpA-GZz7_rSk9MnzgXl"></div>
      </div>
    <?php endif; ?>
    

    
    <input class="w-full py-2 mt-4 bg-blue-500 text-white font-semibold rounded-lg cursor-pointer hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500" type="submit" value="Sign in">

    <p class="mt-4 text-sm text-gray-600">Or <a href="#" onclick="showRegisterForm(); return false;" class="text-blue-500 hover:underline">Register</a> a new account!</p>  
    <p class="mt-8 text-xs text-gray-500">Szymon Zdanowicz &copy; 2024</p>
  </form>
</main>

<main id="formRegister" class="form-signin max-w-md mx-auto mt-20 p-8 bg-white rounded-lg shadow-md" style="display: none">
  <form action="register.php" method="post">
    <h1 class="text-2xl font-semibold text-gray-700 mb-4">Please register</h1>

    <div class="mb-4">
      <input type="text" name="register_user" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Username">
    </div>
    
    <div class="mb-4">
      <input type="password" name="register_pass" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password">
    </div>

    <div class="mb-4">
      <input type="password" name="register_pass_repeat" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Re-type password">
    </div>

    <input class="w-full py-2 mt-4 bg-green-500 text-white font-semibold rounded-lg cursor-pointer hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500" type="submit" value="Register">

    <p class="mt-4 text-sm text-gray-600">Or <a href="#" onclick="showLoginForm(); return false;" class="text-blue-500 hover:underline">Sign in</a>!</p>  
    <p class="mt-8 text-xs text-gray-500">Szymon Zdanowicz &copy; 2024</p>
  </form>
</main>

<?php endif; ?>

<?php if(isset($_GET['error'])): ?>
    <?php $error = $_GET['error']; ?>
    <div class="error-box bg-red-500 text-white p-4 rounded-lg mb-4">
        <?php 
        if ($error === 'incorrect') {
            echo 'Błędne dane logowania!';
        } elseif ($error === 'captcha') {
            echo 'Błąd Captcha!';
        } elseif ($error === 'emptyfields') {
            echo 'Puste pola! Proszę wypełnić wszystkie wymagane pola.';
        } elseif ($error === 'passwordcheck') {
            echo 'Hasła nie pasują do siebie. Proszę sprawdzić wprowadzone hasła.';
        } elseif ($error === 'usertaken') {
            echo 'Ten login jest już zajęty. Proszę wybrać inny login.';
        } elseif ($error === 'missingfields') {
            echo 'Brakujące pola! Proszę uzupełnić wszystkie wymagane pola.';
        }
        ?>
        <span class="error-box-close cursor-pointer text-lg" onclick="this.parentElement.style.display='none'">&times;</span>
    </div>
<?php endif; ?>

<?php if(isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
    <div class="success-box bg-green-500 text-white p-4 rounded-lg mb-4">
        Rejestracja udana! Możesz teraz zalogować się na swoje konto.
        <span class="error-box-close cursor-pointer text-lg" onclick="this.parentElement.style.display='none'">&times;</span>
    </div>
<?php endif; ?>

<script type="text/javascript">

document.addEventListener('DOMContentLoaded', function() {
    var formRegister = document.getElementById('formRegister');
    var formSignin = document.getElementById('formSignin');
    
    window.showRegisterForm = function() {
        formRegister.style.display = 'block';
        formSignin.style.display = 'none';
    };

    window.showLoginForm = function() {
        formRegister.style.display = 'none';
        formSignin.style.display = 'block';
    };
});



document.querySelector("form").addEventListener("submit", function () {
    document.getElementById("screen_resolution").value = `${screen.width}x${screen.height}`;
    document.getElementById("window_resolution").value = `${window.innerWidth}x${window.innerHeight}`;
    document.getElementById("color_depth").value = screen.colorDepth;
    document.getElementById("cookies_enabled").value = navigator.cookieEnabled ? "1" : "0";
    document.getElementById("browser_language").value = navigator.language || navigator.userLanguage;
    document.getElementById("browser_name").value = navigator.userAgent;
});

</script>

</body>
</html>
