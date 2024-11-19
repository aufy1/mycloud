<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';
?>

<?php require_once 'head.php'; ?>	
<body class="bg-gray-100">
<?php require_once 'header.php'; ?>	

<main class="py-10">
<section class="sekcja1">	
    <div class="container mx-auto">
            <h2 class="text-center text-2xl mb-5">Edytuj profil</h2>
            
            <?php 
            // Ścieżka do zdjęcia użytkownika
            $username = $_SESSION['username'];
            $profileImagePath = "media/user_images/" . $username . ".jpg";
            
            // Sprawdź, czy plik istnieje, jeśli nie, użyj domyślnego obrazu
            if (!file_exists($profileImagePath)) {
                $profileImagePath = "media/user_images/default.jpg";
            }
            ?>

            <div class="text-center mb-5">
                <img src="<?php echo $profileImagePath; ?>" alt="Zdjęcie profilowe" class="w-24 h-24 rounded-full mx-auto">
            </div>

            <form action="api/settings_form.php" method="POST" enctype="multipart/form-data">
                <div class="space-y-5">
                    <!-- Zmień zdjęcie -->
                    <div>
                        <button type="button" class="w-full bg-indigo-600 text-white p-3 rounded-md text-left" id="changePhotoButton">
                            Zmień zdjęcie
                        </button>
                        <div id="changePhotoForm" class="hidden mt-4">
                            <div class="mb-4">
                                <label for="profilePhoto" class="block text-gray-700 font-medium">Wprowadź zdjęcie</label>
                                <input type="file" class="mt-2 p-2 border border-gray-300 rounded-md w-full" id="profilePhoto" name="profilePhoto">
                            </div>
                        </div>
                    </div>

                    <!-- Zmień hasło -->
                    <div>
                        <button type="button" class="w-full bg-indigo-600 text-white p-3 rounded-md text-left" id="changePasswordButton">
                            Zmień hasło
                        </button>
                        <div id="changePasswordForm" class="hidden mt-4 space-y-4">
                            <div class="mb-4">
                                <label for="currentPassword" class="block text-gray-700 font-medium">Aktualne hasło</label>
                                <input type="password" class="mt-2 p-2 border border-gray-300 rounded-md w-full" id="currentPassword" name="currentPassword">
                            </div>
                            <div class="mb-4">
                                <label for="newPassword" class="block text-gray-700 font-medium">Nowe hasło</label>
                                <input type="password" class="mt-2 p-2 border border-gray-300 rounded-md w-full" id="newPassword" name="newPassword">
                            </div>
                            <div class="mb-4">
                                <label for="confirmPassword" class="block text-gray-700 font-medium">Powtórz hasło</label>
                                <input type="password" class="mt-2 p-2 border border-gray-300 rounded-md w-full" id="confirmPassword" name="confirmPassword">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 text-center">
                    <button type="submit" class="bg-indigo-600 text-white py-2 px-6 rounded-md">Zapisz zmiany</button>
                </div>
            </form>
    </div>
</section>	
</main>

<?php require_once 'footer.php'; ?>			  	

<script>
    // Toggle forms visibility
    document.getElementById('changePhotoButton').addEventListener('click', function() {
        document.getElementById('changePhotoForm').classList.toggle('hidden');
    });
    document.getElementById('changePasswordButton').addEventListener('click', function() {
        document.getElementById('changePasswordForm').classList.toggle('hidden');
    });
</script>

</body>
</html>
