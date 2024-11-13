<?php
session_start();

// Ustawienie strefy czasowej
date_default_timezone_set('Europe/Warsaw');

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

// Pobieranie użytkowników
$user_query = "SELECT username FROM users";
$user_result = mysqli_query($database, $user_query);
$users = mysqli_fetch_all($user_result, MYSQLI_ASSOC);

// Domyślnie wybrany użytkownik
$selected_user = isset($_GET['user']) ? $_GET['user'] : (isset($users[0]) ? $users[0]['username'] : '');

// Ustawienie aktualnego użytkownika
$user = $_SESSION['username'];

// Walidacja użytkownika
if (!in_array($selected_user, array_column($users, 'username'))) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy użytkownik.']);
    exit();
}
?>

<?php require_once 'head.php'; ?>
<body class="bg-gray-100">

<style>
.messages {
    display: flex;
    flex-direction: column-reverse; /* Nowe wiadomości na dole */
}

.message {
    max-width: 70%;
    margin: 0 0 0.5rem; /* Ustawienie odstępu między wiadomościami */
    padding: 0.2rem 1.2rem;
    border-radius: 12px;
}

.outgoing {
    align-self: flex-end; /* Wiadomości nadawcy po prawej */
    background-color: #D1E8FF;
}

.incoming {
    align-self: flex-start; /* Wiadomości odbiorcy po lewej */
    background-color: #F3F4F6;
}
</style>


<?php require_once 'header.php'; ?>
<main class="py-10">
    <section class="container mx-auto">
        <div class="flex">
            <!-- Lista użytkowników -->
            <div class="w-1/4 bg-white p-4 rounded-lg shadow-lg">
                <h5 class="text-xl font-semibold text-center mb-4">Użytkownicy</h5>
                <ul class="space-y-3">
                    <?php foreach ($users as $user_item): ?>
                        <li class="cursor-pointer p-2 rounded-md hover:bg-indigo-100 <?php echo $user_item['username'] == $selected_user ? 'bg-indigo-200' : ''; ?>">
                            <a href="?user=<?php echo htmlspecialchars($user_item['username']); ?>" class="flex items-center space-x-2">
                                <?php 
                                // Sprawdzanie, czy plik z obrazem istnieje
                                $image_path = "media/user_images/{$user_item['username']}.jpg";
                                if (file_exists($image_path)) {
                                    echo "<img src='$image_path' alt='{$user_item['username']}' class='rounded-full w-8 h-8'>";
                                } else {
                                    echo "<img src='media/user_images/default.jpg' alt='Default Image' class='rounded-full w-8 h-8'>";
                                }
                                ?>
                                <span class="text-lg font-medium"><?php echo htmlspecialchars($user_item['username']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

<div class="w-3/4 bg-white p-6 rounded-lg shadow-lg ml-6">
    <h5 class="text-xl font-semibold text-center mb-4">Czat z <?php echo htmlspecialchars($selected_user); ?></h5>
    <div class="messages overflow-y-auto h-96 mb-4 p-4 border border-gray-300 rounded-lg">
        <!-- Wiadomości będą wczytywane dynamicznie przez AJAX -->
    </div>

    <form id="messageForm" enctype="multipart/form-data" class="flex items-center space-x-3">
        <input type="hidden" name="recipient" value="<?php echo htmlspecialchars($selected_user); ?>">
        <input type="text" id="messageInput" name="message" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Napisz wiadomość...">
        <input type="file" id="fileInput" name="image" accept="image/jpeg" class="hidden" onchange="handleFileSelect(event)">
        <label for="fileInput" class="cursor-pointer p-2 bg-gray-100 hover:bg-gray-200 rounded-md">
            <img src="media/menu_icons/paperclip-solid.svg" alt="Załącz" class="h-6 w-6 text-gray-600">
        </label>
        <button class="bg-indigo-500 hover:bg-indigo-600 text-white p-2 rounded-md">Wyślij</button>
    </form>
</div>




        </div>
    </section>
</main>

<!-- Skrypt AJAX do wysyłania wiadomości -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

function handleFileSelect(event) {
        const fileInput = event.target;
        const messageInput = document.getElementById('messageInput');
        
        // Pobierz nazwę wybranego pliku
        const fileName = fileInput.files[0]?.name || '';

        if (fileName) {
            // Ustaw nazwę pliku w polu tekstowym i zablokuj je
            messageInput.value = fileName;
            messageInput.disabled = true;
        } else {
            // Jeśli plik został odznaczony, odblokuj pole tekstowe
            messageInput.value = '';
            messageInput.disabled = false;
        }
    }

$(document).ready(function() {
    // Funkcja do pobierania wiadomości
    function fetchMessages() {
        $.ajax({
            type: 'GET',
            url: 'api/chat/get_messages.php?user=<?php echo urlencode($selected_user); ?>',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('.messages').empty();  // Czyści poprzednie wiadomości
                    response.messages.forEach(function(message) {
                        if (message.is_image) {
                            $('.messages').append(
                                `<div class="message ${message.user == '<?php echo htmlspecialchars($user); ?>' ? 'outgoing' : 'incoming'} bg-indigo-100 p-3 rounded-lg max-w-xs">
                                    <strong class="text-sm text-gray-700">${message.user}:</strong>
                                    <p class="text-sm mt-2"><img src="${message.message}" alt="Przesłane zdjęcie" class="w-48 rounded-lg"></p>
                                    <small class="text-xs text-gray-500 mt-1">${message.datetime}</small>
                                </div>`
                            );
                        } else {
                            $('.messages').append(
                                `<div class="message ${message.user == '<?php echo htmlspecialchars($user); ?>' ? 'outgoing' : 'incoming'} bg-indigo-100 p-3 rounded-lg max-w-xs">
                                    <strong class="text-sm text-gray-700">${message.user}:</strong>
                                    <p class="text-sm mt-2">${message.message}</p>
                                    <small class="text-xs text-gray-500 mt-1">${message.datetime}</small>
                                </div>`
                            );
                        }
                    });
                    $('.messages').scrollTop($('.messages')[0].scrollHeight);
                }
            },
            error: function() {
                console.log('Wystąpił błąd przy pobieraniu wiadomości.');
            }
        });
    }

    // Ustawienie interwału do odświeżania wiadomości co 5 sekund
    setInterval(fetchMessages, 5000); // 5000 ms = 5 sekundy

    // Wywołanie pobierania wiadomości po załadowaniu strony
    fetchMessages();

    $('#messageForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this); // Użycie FormData do obsługi plików
        $.ajax({
            type: 'POST',
            url: 'api/chat/send_message.php',
            data: formData,
            contentType: false, // Wyłącza domyślne kodowanie nagłówka
            processData: false, // Zapobiega przetwarzaniu danych jako tekst
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.image) {
                        // Wyświetlenie wiadomości z obrazem
                        $('.messages').append(
                            `<div class="message outgoing bg-indigo-100 p-3 rounded-lg max-w-xs self-end">
                                <strong class="text-sm text-gray-700">${response.user}:</strong>
                                <p class="text-sm mt-2"><img src="${response.image}" alt="Przesłane zdjęcie" class="w-48 rounded-lg"></p>
                                <small class="text-xs text-gray-500 mt-1">${new Date().toLocaleString()}</small>
                            </div>`
                        );
                    } else {
                        // Wyświetlenie wiadomości tekstowej
                        $('.messages').append(
                            `<div class="message outgoing bg-indigo-100 p-3 rounded-lg max-w-xs self-end">
                                <strong class="text-sm text-gray-700">${response.user}:</strong>
                                <p class="text-sm mt-2">${response.message}</p>
                                <small class="text-xs text-gray-500 mt-1">${new Date().toLocaleString()}</small>
                            </div>`
                        );
                    }
                    $('input[name="message"]').val('');
                    $('input[name="image"]').val('');
                    fetchMessages();  // Odswieżenie wiadomości
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Wystąpił błąd przy wysyłaniu wiadomości.');
            }
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>
</body>
</html>
