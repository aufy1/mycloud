<?php
session_start();

// Upewniamy się, że użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Włącz wyświetlanie błędów dla diagnostyki
error_reporting(E_ALL);
ini_set('display_errors', 1);


$username = $_SESSION['username'];
$disk = isset($_GET['disk']) ? $_GET['disk'] : '';

// Sprawdzanie, czy dysk istnieje
if (empty($disk)) {
    echo "Brak określonego dysku.";
    exit();
}

// Połączenie z bazą danych
require_once 'config.php';
require_once 'functions.php';

// Sprawdzanie połączenia z bazą danych
if (mysqli_connect_errno()) {
    echo "Błąd połączenia z bazą danych: " . mysqli_connect_error();
    exit();
}

// Sprawdzamy, czy użytkownik ma dostęp do dysku
if (!hasAccessToDisk($database, $_SESSION['username'], $disk)) {
    header('Location: storage.php'); // Przekierowanie na stronę storage.php
    exit();
}

// Pobieramy zmienną path z adresu URL (GET)
$path = isset($_GET['path']) ? $_GET['path'] : '';



// Jeśli użytkownik ma dostęp, pobieramy pliki z wybranego dysku i ścieżki
$query = "SELECT * FROM files WHERE disk = ? AND path LIKE ?";
$stmt = $database->prepare($query);

// Sprawdzanie błędów w zapytaniu
if (!$stmt) {
    echo "Błąd zapytania: " . mysqli_error($database);
    exit();
}

// Formatowanie ścieżki dla SQL - dodajemy końcowy `/`, aby wyszukać dokładnie w tej ścieżce
$path_query = $disk . '/' . trim($path, '/'); // np. "dysk1/folder1/%"



// Bindowanie parametrów
$stmt->bind_param('ss', $disk, $path_query);
$stmt->execute();
$result = $stmt->get_result();

// Sprawdzanie, czy zapytanie zakończyło się sukcesem
if (!$result) {
    echo "Błąd wykonania zapytania: " . mysqli_error($database);
    exit();
}

// Tablica na przechowanie wyników
$files = [];
while ($row = $result->fetch_assoc()) {
    $files[] = $row;
}


require_once 'head.php';
?>



<body class="bg-gray-100">
    <?php require_once 'header.php'; ?>

    <main class="py-10">
        <section class="sekcja1">



<?php


if (checkPathExists($database, "$disk", "asd", $path))
{
    echo "Folder istnieje!";
} else {
    echo "Folder nie istnieje!";
}
 ?>

            <div class="container mx-auto">
                <div class="w-full shadow-md sm:rounded-lg text-gray-500 bg-gray-50 flex items-center justify-between mb-2 text-gray-700 uppercase bg-gray-50 p-2">
                    <span class="text-md text-gray-900">Dysk: <?php echo htmlspecialchars($disk); ?></span>

                    <!-- Wyświetlanie aktualnej ścieżki -->
                    <div class="flex items-center">
                        <button id="backButton" class="flex items-center text-white p-2 bg-blue-100 hover:bg-blue-200 rounded-3xl">
                            <img src="media/storage_icons/chevron-left-solid.svg" alt="Back" class="w-5 h-5">
                        </button>


                        <!-- Przycisk do zmiany ścieżki -->
                        <input type="text" id="goToPath" placeholder="Path" class="ml-4 mr-4 p-2 min-w-80 rounded" value="<?php echo htmlspecialchars($path); ?>" />
                        <button id="submitPath" class="p-2 text-white bg-blue-100 hover:bg-blue-200 rounded-3xl">
                            <img src="media/storage_icons/arrow-right-solid.svg" alt="Go" class="w-5 h-5">
                        </button>
                    </div>


                    <button id="newFolderButton" class="flex items-center text-white bg-blue-500 hover:bg-blue-600 px-3 py-2 rounded">
                        <img src="media/storage_icons/folder-plus-solid.svg" alt="Add Folder" class="w-5 h-5 mr-2">
                        Utwórz folder
                    </button>
                </div>


                <!-- Formularz do tworzenia folderu (początkowo ukryty) -->
                <div id="newFolderForm" class="hidden mb-4">
                    <form action="api/cloud/create_dir.php" method="POST" class="w-full max-w-sm">
                        <div class="flex items-center">
                            <input type="text" name="new_folder" id="new_folder" placeholder="Nowy folder" class="w-full p-2 border border-gray-300 rounded-l-md" required>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-r-md">Utwórz katalog</button>
                        </div>



                        <input type="hidden" name="disk" value="<?php echo htmlspecialchars($disk); ?>"> <!-- Ukryty input dla dysku -->
                        <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>"> <!-- Ukryty input dla ścieżki -->
                    </form>
                </div>



                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table id="fileTable" class="w-full text-sm rtl:text-right text-gray-500 bg-white">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
                <th scope="col" class="p-4">
                    <div class="flex items-center">
                        <input id="checkbox-all-search" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500  focus:ring-blue-600  ring-offset-gray-800  focus:ring-offset-gray-800 focus:ring-2  bg-gray-700  border-gray-600">
                        <label for="checkbox-all-search" class="sr-only">checkbox</label>
                    </div>
                </th>
                <th scope="col" class="px-6 py-3">Type</th>
                <th scope="col" class="px-6 py-3">File Name</th>
                <th scope="col" class="px-6 py-3">Owner</th>
                <th scope="col" class="px-6 py-3">Last Modified</th>
                <th scope="col" class="px-6 py-3">File Type</th>
                <th scope="col" class="px-6 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="w-4 p-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center"><img src="media/storage_icons/file-regular.svg" alt="Go" class="w-5 h-5"></td>
                                <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($file['file_name']); ?></td>
                                <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($file['owner']); ?></td>
                                <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($file['last_modified']); ?></td>
                                <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($file['file_type']); ?></td>
                                <td class="px-6 py-4 text-center">
                                <a href="#" class="font-medium text-blue-600 hover:underline" onclick="openFile('<?php echo htmlspecialchars($file['file_name']); ?>', '<?php echo htmlspecialchars($file['file_type']); ?>')">Open</a> |
                                    <a href="#" class="font-medium text-red-600 hover:underline" onclick="deleteFile('<?php echo htmlspecialchars($file['file_name']); ?>')">Delete</a> |
                                    <a href="#" class="font-medium text-blue-600 hover:underline" onclick="shareFile('<?php echo htmlspecialchars($file['file_name']); ?>')">Share</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>


                    </table>

                            <!-- Nakładka dla przeciągania pliku -->
    <div id="dropOverlay" class="pointer-events-none absolute inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center text-white text-2xl hidden">
        Upuść tutaj
    </div>


                </div>

                <div id="upload-status" class="mt-4"></div>

            </div>
        </section>
    </main>

    <?php require_once 'footer.php'; ?>

    <script>

document.getElementById('newFolderButton').addEventListener('click', function() {
            const form = document.getElementById('newFolderForm');
            form.classList.toggle('hidden');
        });


        document.getElementById('backButton').addEventListener('click', function() {
    const currentPath = "<?php echo isset($_GET['path']) ? $_GET['path'] : ''; ?>"; // Pobieramy aktualną ścieżkę
    const pathSegments = currentPath.split('/'); // Dzielimy ścieżkę na segmenty
    pathSegments.pop(); // Usuwamy ostatni segment (cofanie się o jeden poziom)
    const newPath = pathSegments.join('/'); // Łączymy ponownie segmenty

    // Przekierowanie do nowej ścieżki
    window.location.href = `?disk=<?php echo htmlspecialchars($disk); ?>&path=${newPath}`;
});

document.getElementById('submitPath').addEventListener('click', function() {
    const newPath = document.getElementById('goToPath').value.trim(); // Pobieramy nową ścieżkę z pola tekstowego
    if (newPath) {
        window.location.href = `?disk=<?php echo htmlspecialchars($disk); ?>&path=${newPath}`; // Przekierowanie na nową ścieżkę
    } else {
        alert("Wprowadź ścieżkę.");
    }
});

        // Funkcja "Otwórz"
        function openFile(fileName) {
            alert('Otwieram plik: ' + fileName);
            // Możesz dodać funkcjonalność do otwierania pliku, np. przez przekierowanie na stronę lub inny sposób
        }

        // Funkcja "Usuń"
        function deleteFile(fileName) {
            if (confirm('Czy na pewno chcesz usunąć plik: ' + fileName + '?')) {
                alert('Plik ' + fileName + ' został usunięty.');
                // Dodaj kod do usuwania pliku z bazy danych
            }
        }

        // Funkcja "Udostępnij"
        function shareFile(fileName) {
            const username = "<?php echo $_SESSION['username']; ?>"; // Nazwa użytkownika
            let sharedWith = prompt('Wprowadź nazwę użytkownika, z którym chcesz udostępnić plik:');
            if (sharedWith) {
                // Wysłanie zapytania AJAX do backendu w celu udostępnienia pliku
                $.ajax({
                    url: 'api/cloud/share_file.php',
                    method: 'POST',
                    data: {
                        file_name: fileName,
                        owner: username,
                        shared_with: sharedWith
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Plik został udostępniony użytkownikowi ' + sharedWith);
                        } else {
                            alert('Wystąpił błąd podczas udostępniania pliku: ' + (response.error || 'Nieznany błąd'));
                        }
                    },
                    error: function() {
                        alert('Wystąpił błąd w trakcie komunikacji z serwerem.');
                    }
                });
            }
        }

// Funkcja "Otwórz"
function openFile(fileName, fileType) {
    const disk = "<?php echo $_GET['disk']; ?>"; // Pobieramy nazwę dysku z URL
    const currentPath = "<?php echo isset($_GET['path']) ? $_GET['path'] : ''; ?>"; // Pobieramy ścieżkę, domyślnie ""

    if (fileType === 'folder') {
        // Jeśli to folder, zmieniamy `path` w URL, aby przejść do jego zawartości
        const newPath = currentPath ? `${currentPath}/${fileName}` : fileName;
        window.location.href = `?disk=${disk}&path=${newPath}`;
    } else {
        alert('Otwieram plik: ' + fileName);
        // Tutaj możesz dodać funkcję do podglądu pliku lub jego pobrania
    }
}


// Funkcja do usuwania pliku lub folderu
function deleteFile(fileName) {
    if (confirm('Czy na pewno chcesz usunąć: ' + fileName + '?')) {
        const disk = "<?php echo $_GET['disk']; ?>";  // Pobieramy nazwę dysku z URL
        const path = "<?php echo isset($_GET['path']) ? $_GET['path'] : ''; ?>";  // Pobieramy ścieżkę z URL, domyślnie ""

        // Wysyłanie zapytania AJAX do backendu
        $.ajax({
            url: 'api/cloud/delete_file.php',
            method: 'POST',
            data: {
                file_name: fileName,
                disk_name: disk,
                path: path
            },
            success: function(response) {
                if (response.success) {
                    alert(fileName + ' został usunięty. ' + response.query + response.query_values.disk +  response.query_values.dbPath);
                    location.reload();  // Odśwież stronę po usunięciu
                } else {
                    alert('Wystąpił błąd podczas usuwania: ' + (response.error || 'Nieznany błąd'));
                }
            },
            error: function() {
                alert('Wystąpił błąd podczas komunikacji z serwerem.');
            }
        });
    }
}




const fileTable = document.getElementById('fileTable');
const dropOverlay = document.getElementById('dropOverlay');

// Pokaż nakładkę, gdy plik jest przeciągany nad obszarem tabeli
fileTable.addEventListener('dragover', function(event) {
    event.preventDefault();
    dropOverlay.classList.remove('hidden');  // Pokazujemy nakładkę
});
// Po upuszczeniu pliku, ukryj nakładkę i obsłuż przesyłanie pliku
fileTable.addEventListener('drop', function(event) {
    event.preventDefault();  // Zapobiegamy domyślnemu działaniu
    dropOverlay.classList.add('hidden');  // Ukryj nakładkę po upuszczeniu pliku

    const files = event.dataTransfer.files;  // Pobieramy pliki, które zostały upuszczone

    if (files.length > 0) {
        const formData = new FormData();
        formData.append('file_upload', files[0]);  // Zmieniamy na pierwszy plik z listy

        const urlParams = new URLSearchParams(window.location.search);
        const path = urlParams.get('path') || '';  // Jeśli 'path' nie ma w URL, ustawiamy pusty ciąg

        formData.append('disk', "<?php echo $_GET['disk']; ?>");  // Wysłanie dysku
        formData.append('path', path);  // Wysłanie ścieżki

        // Wyświetlamy status przesyłania
        document.getElementById('upload-status').innerText = "Uploading...";

        // Wysyłamy plik do upload_file.php za pomocą AJAX
        fetch('api/cloud/upload_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())  // Oczekujemy odpowiedzi w formacie JSON
        .then(data => {
            if (data.success) {
                document.getElementById('upload-status').innerText = "Plik został przesłany pomyślnie.";
            } else {
                document.getElementById('upload-status').innerText = "Błąd przesyłania pliku: " + data.error;
            }
        })
        .catch(error => {
            console.error('Błąd:', error);
            document.getElementById('upload-status').innerText = "Wystąpił błąd podczas przesyłania.";
        });
    }
});

// Ukryj nakładkę, gdy plik opuszcza obszar tabeli
fileTable.addEventListener('dragleave', function(event) {
    event.preventDefault();
    dropOverlay.classList.add('hidden');
});

    </script>
</body>
</html>
