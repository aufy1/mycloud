<?php
session_start();

// Upewniamy się, że użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'];
$disk = isset($_GET['disk']) ? $_GET['disk'] : '';

// Sprawdzanie, czy dysk istnieje
if (empty($disk)) {
    echo "Brak określonego dysku.";
    exit;
}

// Połączenie z bazą danych
require_once 'config.php';
require_once 'functions.php';

if (!hasAccessToDisk($database, $_SESSION['username'], $disk)) {
    header('Location: storage.php'); // Przekierowanie na stronę storage.php
    exit();
}
else
{
// Pobieramy zmienną path z adresu URL (GET)
$path = isset($_GET['path']) ? $_GET['path'] : '';
var_dump($_GET['path']);
// Jeśli użytkownik ma dostęp, pobieramy pliki z wybranego dysku i ścieżki
$query = "SELECT * FROM files WHERE disk = ? AND path LIKE ?";
$stmt = $database->prepare($query);

// Formatowanie ścieżki dla SQL - dodajemy końcowy `/`, aby wyszukać dokładnie w tej ścieżce
$path_query = $disk . '/' . trim($path, '/'); // np. "dysk1/folder1/%"
var_dump($path_query);
// Bindowanie parametrów
$stmt->bind_param('ss', $disk, $path_query);
$stmt->execute();
$result = $stmt->get_result();

// Tablica na przechowanie wyników
$files = [];
while ($row = $result->fetch_assoc()) {
    $files[] = $row;
}

require_once 'head.php';

}

require_once 'head.php';
?>


<body class="bg-gray-100">
    <?php require_once 'header.php'; ?>

    <main class="py-10">
        <section class="sekcja1">



      
            <div class="container mx-auto">

            <?php echo $disk;?>

            <div class="flex items-center justify-center w-full pt-10">
    <form action="api/cloud/create_dir.php" method="GET" class="w-full max-w-sm">
        <div class="flex items-center">
            <input type="text" name="new_folder" id="new_folder" placeholder="Nowy folder" class="w-full p-2 border border-gray-300 rounded-l-md" required>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-r-md">Utwórz katalog</button>
        </div>
        <input type="hidden" name="disk" value="<?php echo htmlspecialchars($disk); ?>"> <!-- Ukryty input dla dysku -->
        <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>"> <!-- Ukryty input dla ścieżki -->
    </form>
</div>

                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 bg-white">
    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" class="p-4">
                <div class="flex items-center">
                    <input id="checkbox-all-search" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="checkbox-all-search" class="sr-only">checkbox</label>
                </div>
            </th>
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
            <td class="px-6 py-4"><?php echo htmlspecialchars($file['file_name']); ?></td>
            <td class="px-6 py-4"><?php echo htmlspecialchars($file['owner']); ?></td>
            <td class="px-6 py-4"><?php echo htmlspecialchars($file['last_modified']); ?></td>
            <td class="px-6 py-4"><?php echo htmlspecialchars($file['file_type']); ?></td>
            <td class="px-6 py-4">
                <a href="#" class="font-medium text-blue-600 hover:underline" onclick="openFile('<?php echo htmlspecialchars($file['file_name']); ?>')">Open</a> |
                <a href="#" class="font-medium text-red-600 hover:underline" onclick="deleteFile('<?php echo htmlspecialchars($file['file_name']); ?>')">Delete</a> |
                <a href="#" class="font-medium text-blue-600 hover:underline" onclick="shareFile('<?php echo htmlspecialchars($file['file_name']); ?>')">Share</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


        <nav class="flex items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400 mb-4 md:mb-0 block w-full md:inline md:w-auto">Showing <span class="font-semibold text-gray-900 dark:text-white">1-10</span> of <span class="font-semibold text-gray-900 dark:text-white">1000</span></span>
            <ul class="inline-flex -space-x-px rtl:space-x-reverse text-sm h-8">
                <li><a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Previous</a></li>
                <li><a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">1</a></li>
                <li><a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Next</a></li>
            </ul>
        </nav>
    </div>

    <div class="flex items-center justify-center w-full pt-10">
    <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
        <div class="flex flex-col items-center justify-center pt-5 pb-6">
            <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
            </svg>
            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
        </div>
        <input id="dropzone-file" type="file" class="hidden" />
    </label>
</div> 
<div id="upload-status" class="mt-4"></div>

            </div>



        </section>
    </main>

    <?php require_once 'footer.php'; ?>

    <script>
        // Przełączanie widoku "Kafelki" i "Lista"
        $(document).ready(function() {
            $('#toggleGrid').click(function() {
                $('#fileContainer').removeClass('w-full').addClass('grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3');
                $('#toggleGrid').addClass('bg-purple-500 text-white');
                $('#toggleList').removeClass('bg-purple-500 text-white');
            });

            $('#toggleList').click(function() {
                $('#fileContainer').removeClass('grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3').addClass('w-full');
                $('#toggleList').addClass('bg-purple-500 text-white');
                $('#toggleGrid').removeClass('bg-purple-500 text-white');
            });
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




            // Funkcja obsługująca wysyłanie pliku
    document.getElementById('dropzone-file').addEventListener('change', function(event) {
        const fileInput = event.target;
        const file = fileInput.files[0];

        // Sprawdzamy, czy plik został wybrany
        if (file) {
            const formData = new FormData();
            formData.append('file_upload', file);
            formData.append('disk', "<?php echo $_GET['disk']; ?>"); // Wysłanie zmiennej disk

            // Wyświetlamy status przesyłania
            document.getElementById('upload-status').innerText = "Uploading...";

            // Wysyłamy plik do upload_file.php za pomocą AJAX
            fetch('api/cloud/upload_file.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Oczekujemy odpowiedzi w formacie JSON
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
    </script>
</body>
</html>
