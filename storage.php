<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$username = isset($_GET['username']) ? $_GET['username'] : $_SESSION['username']; // Używamy sesyjnego użytkownika, jeśli brak w URL
$disk = isset($_GET['disk']) ? $_GET['disk'] : null; // Parametr dysku w URL

require_once 'config.php';
require_once 'head.php';

if (mysqli_connect_errno()) {
    echo json_encode(['success' => false, 'error' => 'Błąd połączenia z bazą danych: ' . mysqli_connect_error()]);
    exit;
}

// Sprawdzamy, czy mamy wybrany dysk
if ($disk) {
    // Pobieramy pliki z wybranego dysku
    $query = "SELECT file_name, file_size, upload_date FROM files WHERE disk_name = ? AND owner = ?";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "ss", $disk, $username); // Przekazujemy nazwę dysku i użytkownika
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $file_name, $file_size, $upload_date);

    $files = [];
    while (mysqli_stmt_fetch($stmt)) {
        $files[] = [
            'file_name' => $file_name,
            'file_size' => $file_size,
            'upload_date' => $upload_date
        ];
    }
    mysqli_stmt_close($stmt);
} else {
    // Pobieramy dyski użytkownika, gdy nie mamy wybranego dysku
    $query = "SELECT disk_name, shared_with, created_at FROM disks WHERE owner = ?";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "s", $username); // Przekazujemy nazwę użytkownika
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $disk_name, $shared_with, $created_at);

    $disks = [];
    while (mysqli_stmt_fetch($stmt)) {
        $disks[] = [
            'disk_name' => $disk_name,
            'shared_with' => $shared_with,
            'created_at' => $created_at
        ];
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($database);
?>

<body class="bg-gray-100">
<?php require_once 'header.php'; ?>

<main class="py-10">
    <section class="sekcja1">
        <div class="container mx-auto">
        
        <!-- Sprawdzamy, czy mamy parametr 'disk' w URL -->
        <?php if ($disk): ?>
            <!-- Wyświetlamy pliki z wybranego dysku w opcji kafelkowej -->
            <div class="flex space-x-4 flex-wrap">
                <?php if (!empty($files)): ?>
                    <?php foreach ($files as $file): ?>
                        <div class="file-item bg-white shadow-md rounded-xl p-6 text-center relative">
                            <div class="absolute top-4 right-4 w-11 h-11 bg-gray-50 rounded-lg flex items-center justify-center cursor-pointer" id="menuButton">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6 text-purple-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>

                            <div id="dropdownMenu" class="absolute top-12 right-0 bg-white shadow-lg rounded-lg w-40 p-2 hidden">
                                <ul class="space-y-2">
                                    <li><a href="#" class="text-sm text-gray-700 hover:text-purple-500">Otwórz</a></li>
                                    <li><a href="#" class="text-sm text-gray-700 hover:text-purple-500">Usuń</a></li>
                                </ul>
                            </div>

                            <img src="media/storage_icons/file_icon.svg" alt="Plik" class="w-16 h-16 mx-auto mb-4">
                            <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($file['file_name']); ?></h3>
                            <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($file['file_size']); ?> MB</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Wyświetlamy dyski, jeśli brak parametru 'disk' -->
            <div class="flex space-x-4 flex-wrap">
                <?php if (!empty($disks)): ?>
                    <?php foreach ($disks as $disk): ?>
                        <div class="bg-white font-dmsans shadow-md rounded-xl p-8 max-w-sm w-full relative flex-1 mb-4">
    <div class="absolute top-4 right-4 w-11 h-11 bg-gray-50 rounded-lg flex items-center justify-center cursor-pointer" id="menuButton_<?php echo $disk['disk_name']; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6 text-purple-400 transition-transform duration-300 ease-in-out">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>

    <div id="dropdownMenu_<?php echo $disk['disk_name']; ?>" class="absolute top-16 right-0 bg-white shadow-lg rounded-lg w-40 p-2 hidden">
        <ul class="space-y-2">
            <li><a href="files.php?disk=<?php echo urlencode($disk['disk_name']); ?>" class="text-sm text-gray-700 hover:text-purple-500">Otwórz</a></li>
            <li><a href="#" class="text-sm text-gray-700 hover:text-purple-500" onclick="deleteDisk('<?php echo $disk['disk_name']; ?>')">Usuń</a></li>
            <li><a href="#" class="text-sm text-gray-700 hover:text-purple-500" onclick="shareDisk('<?php echo $disk['disk_name']; ?>')">Udostępnij</a></li>
        </ul>
    </div>

    <div class="flex justify-center mt-6 mb-12">
        <img src="media/storage_icons/cloud_blank.svg" alt="Cloud" class="w-20 h-20">
    </div>

    <h2 class="text-2xl font-semibold text-center mt-4"><?php echo htmlspecialchars($disk['disk_name']); ?></h2>
    <p class="text-center text-gray-500 mt-2 mb-2">Przechowuj pliki w chmurze</p>

    <div class="flex justify-between mb-1 mt-8">
        <span class="text-xs text-gray-500">0GB</span>
        <span class="text-xs text-gray-500">100GB</span>
    </div>

    <div class="w-full bg-gray-200 rounded-full h-4">
        <div class="bg-purple-400 h-4 rounded-full" style="width: 95%;"></div>
    </div>
</div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Kafelek "Dodaj Dysk" -->
                <div id="addDiskTile" class="bg-white font-dmsans shadow-md rounded-xl p-8 max-w-sm w-full relative flex-1 cursor-pointer mb-4">
                    <div class="flex justify-center items-center h-full">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-8 h-8 text-purple-400">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"></path>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>

        </div>
    </section>
</main>

<?php require_once 'footer.php'; ?>

<script>



    // Funkcja obsługująca pokazanie i ukrycie menu rozwijanego dla każdego dysku
    $(document).ready(function() {
        // Pokazywanie i ukrywanie dropdown menu dla dysków
        $('[id^="menuButton_"]').click(function() {
            var diskName = $(this).attr('id').split('_')[1]; // Wyciągamy nazwę dysku z ID
            $('#dropdownMenu_' + diskName).toggleClass('hidden');
            var arrow = $(this).find('svg');
            arrow.toggleClass('rotate-180');
        });

        // Funkcja "Usuń"
        function deleteDisk(diskName) {
            if (confirm('Czy na pewno chcesz usunąć dysk ' + diskName + '?')) {
                // Przekierowanie na stronę lub wykonanie zapytania do API usuwającego dysk
                alert('Dysk ' + diskName + ' został usunięty!');
                // Tutaj należy dodać kod do faktycznego usuwania dysku
            }
        }

        // Funkcja "Udostępnij"
        function shareDisk(diskName) {
            const username = "<?php echo $_SESSION['username']; ?>"; // Nazwa użytkownika
            let sharedWith = prompt('Wprowadź nazwę użytkownika, z którym chcesz udostępnić dysk:');
            if (sharedWith) {
                // Wyślij żądanie do serwera, aby udostępnić dysk
                $.ajax({
                    url: 'api/cloud/share_disk.php', // Ścieżka do pliku PHP, który obsługuje udostępnianie dysków
                    method: 'POST',
                    data: {
                        disk_name: diskName,
                        owner: username,
                        shared_with: sharedWith
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Dysk został udostępniony użytkownikowi ' + sharedWith);
                        } else {
                            alert('Wystąpił błąd podczas udostępniania dysku: ' + (response.error || 'Nieznany błąd'));
                        }
                    },
                    error: function() {
                        alert('Wystąpił błąd w trakcie komunikacji z serwerem.');
                    }
                });
            }
        }

    });




        // Funkcja tworzenia nowego dysku (folderu)
        function createNewDisk() {
    // Wysyłamy żądanie do serwera, aby utworzyć nowy folder
    $.ajax({
        url: 'api/cloud/create_storage.php', // Ścieżka do pliku PHP, który stworzy folder
        method: 'POST',
        data: {},
        success: function(response) {
            if (response.success) {
                alert('Nowy dysk został utworzony! Folder: ' + response.folder);
            } else {
                alert('Wystąpił błąd przy tworzeniu dysku: ' + (response.error || 'Nieznany błąd'));
            }
        },
        error: function() {
            alert('Wystąpił błąd w trakcie komunikacji z serwerem.');
        }
    });
}

// Dodajemy zdarzenie kliknięcia do kafelka "Dodaj Dysk"
$('#addDiskTile').click(function() {
    createNewDisk(); // Wywołanie funkcji tworzącej folder
});
</script>

</body>
</html>
