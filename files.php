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

if(!(is_dir('uploads/cloud/' . $disk . '/' . $path)))
{
    echo "nawet nie próbuj";
    exit();
}

// Jeśli użytkownik ma dostęp, pobieramy pliki z wybranego dysku i ścieżki
$query = "SELECT * FROM files WHERE disk = ? AND path LIKE ? ORDER BY 
            CASE WHEN file_type = 'folder' THEN 0 ELSE 1 END, last_modified DESC";

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
    if($path){
    $fullFilePath = 'uploads/cloud/'. $row['path'] . '/' . $row['file_name'];}
    else{
    $fullFilePath = 'uploads/cloud/' . $row['path'] . $row['file_name'];
    }

    // Dodajemy pełną ścieżkę do tablicy wyników
    $row['full_file_path'] = $fullFilePath;

    $files[] = $row;
}



require_once 'head.php';
?>



<body class="bg-gray-100">
    <?php require_once 'header.php'; ?>

    <div id="mediaContainer" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex justify-center items-center z-50" style="display: none;">
    <div class="relative bg-white p-4 rounded-lg max-w-full max-h-full overflow-auto">
        <button id="closeButton" class="absolute top-0 right-0 text-white bg-transparent border-0 p-2 focus:outline-none z-50">
            <img src="media/storage_icons/x-solid.svg" alt="Close" class="w-4 h-4">
        </button>

        <div id="mediaContent" class="flex justify-center p-3 items-center">
            <!-- media content -->
        </div>
    </div>
</div>


    <main class="py-10">
        <section class="sekcja1">
            <div class="container mx-auto">
                <div class="w-full shadow-md sm:rounded-lg text-gray-500 bg-gray-50 flex items-center justify-between mb-2 text-gray-700 uppercase bg-gray-50 p-2">
                    <span class="text-md text-gray-900 ml-2">Dysk: <?php echo htmlspecialchars($disk); ?></span>
                    <div class="flex items-center">
                        <button id="backButton" class="flex items-center text-white p-2 bg-blue-100 hover:bg-blue-200 rounded-3xl">
                            <img src="media/storage_icons/chevron-left-solid.svg" alt="Back" class="w-5 h-5">
                        </button>
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

                <div id="newFolderForm" class="hidden fixed inset-0 bg-gray-800 bg-opacity-75 flex justify-center items-center z-50">
    <div class="relative bg-white p-8 rounded-lg max-w-sm w-full">
        <button id="closeFormButton" class="absolute top-0 right-0 text-white bg-transparent border-0 p-2 focus:outline-none z-50">
            <img src="media/storage_icons/x-solid.svg" alt="Close" class="w-4 h-4">
        </button>

        <!-- Formularz -->
        <form action="api/cloud/create_dir.php" method="POST">
            <div class="flex items-center">
                <input type="text" name="new_folder" id="new_folder" placeholder="Nowy folder" class="w-full p-2 border border-gray-300 rounded-l-md" required>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white p-2  rounded-r-md">Utwórz</button>
            </div>

            <input type="hidden" name="disk" value="<?php echo htmlspecialchars($disk); ?>">
            <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>">
        </form>
    </div>
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
                            <th scope="col" class="px-6 py-3">ICON</th>
                            <th scope="col" class="px-6 py-3">File Name</th>
                            <th scope="col" class="px-6 py-3">Owner</th>
                            <th scope="col" class="px-6 py-3">Last Modified</th>
                            <th scope="col" class="px-6 py-3">File Type</th>
                            <th scope="col" class="px-6 py-3">Size</th>
                            <th scope="col" class="py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                        $icons = json_decode(file_get_contents('assets/storage_icons.json'), true); 
                        $fileIcons = $icons['file_icons'];
                        $actionIcons = $icons['action_icons'];
                    ?>
<?php foreach ($files as $file): ?>
    <tr class="bg-white border-b hover:bg-gray-50">
        <td class="w-4 p-4">
            <div class="flex items-center">
                <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            </div>
        </td>
        <td class="px-6 py-4 flex justify-center items-center">
            <?php
            $fileType = $file['file_type'];
            $icon = isset($fileIcons[$fileType]) ? $fileIcons[$fileType] : $fileIcons['default'];
            ?>
            <img src="<?php echo htmlspecialchars($icon); ?>" alt="Icon" class="w-5 h-5">
        </td>
        <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($file['file_name']); ?></td>
        <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($file['owner']); ?></td>
        <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($file['last_modified']); ?></td>
        <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($file['file_type']); ?></td>
        <td class="text-center">
        <?php
        if(!($file['file_type']=="folder"))
        {
          $filePath = $file['full_file_path'];
          if (file_exists($filePath)) {
              $fileSize = filesize($filePath);  // getting filesize
              echo formatSize($fileSize); 
          } else {
              echo "Brak pliku";
          }
        }
        else
        {
          echo "-";
        }
        ?>
    </td>
        <td class="py-4 text-center">
        <div class="w-32 mt-0 mb-0 ml-auto mr-auto">
    <div class="flex justify-end gap-4">
        <?php if ($fileType == 'folder'): ?>
            <a href="#" onclick="openFile('<?php echo htmlspecialchars($file['file_name']); ?>', '<?php echo htmlspecialchars($file['file_type']); ?>')">
                <img src="<?php echo htmlspecialchars($actionIcons['folder-open']); ?>" alt="Open" class="w-5 h-5" title="Open">
            </a>
        <?php endif; ?>

        <?php if (in_array($fileType, ['jpg', 'bmp', 'mp4', 'png', 'svg', 'mp3'])): ?>
            <a href="#" onclick="play('<?php echo htmlspecialchars($file['file_name']); ?>')">
                <img src="<?php echo htmlspecialchars($actionIcons['play']); ?>" alt="Play" class="w-5 h-5" title="Play">
            </a>
        <?php endif; ?>

        <?php if ($fileType != 'folder'): ?>
            <a href="#" onclick="downloadFile('<?php echo htmlspecialchars($file['file_name']); ?>')">
                <img src="<?php echo htmlspecialchars($actionIcons['download']); ?>" alt="Download" class="w-5 h-5" title="Download">
            </a>
            <a href="#" onclick="shareFile('<?php echo htmlspecialchars($file['file_name']); ?>')">
                <img src="<?php echo htmlspecialchars($actionIcons['share']); ?>" alt="Share" class="w-5 h-5" title="Share">
            </a>
        <?php endif; ?>

        <!-- Delete button visible for all files -->
        <a href="#" onclick="deleteFile('<?php echo htmlspecialchars($file['file_name']); ?>')">
            <img src="<?php echo htmlspecialchars($actionIcons['delete']); ?>" alt="Delete" class="w-5 h-5" title="Delete">
        </a>
    </div>
</div>

        </td>
    </tr>
<?php endforeach; ?>

                    </tbody>
                </table>

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
const disk = "<?php echo $_GET['disk']; ?>";
const currentPath = "<?php echo isset($_GET['path']) ? $_GET['path'] : ''; ?>";
const username = "<?php echo $_SESSION['username']; ?>";

document
  .getElementById("newFolderButton")
  .addEventListener("click", function () {
    const form = document.getElementById("newFolderForm");
    document.getElementById("newFolderForm").style.display = "flex";
  });

document.getElementById("backButton").addEventListener("click", function () {
  const pathSegments = currentPath.split("/"); // Dzielimy ścieżkę na segmenty
  pathSegments.pop(); // Usuwamy ostatni segment (cofanie się o jeden poziom)
  const newPath = pathSegments.join("/"); // Łączymy ponownie segmenty

  // Przekierowanie do nowej ścieżki
  window.location.href = `?disk=<?php echo htmlspecialchars($disk); ?>&path=${newPath}`;
});

document
  .getElementById("closeFormButton")
  .addEventListener("click", function () {
    document.getElementById("newFolderForm").style.display = "none";
  });

function openNewFolderForm() {
  document.getElementById("newFolderForm").style.display = "flex";
}

document.getElementById("submitPath").addEventListener("click", function () {
  const newPath = document.getElementById("goToPath").value.trim(); // Pobieramy nową ścieżkę z pola tekstowego
  if (newPath) {
    window.location.href = `?disk=<?php echo htmlspecialchars($disk); ?>&path=${newPath}`;
  } else {
    alert("Wprowadź ścieżkę.");
  }
});

function play(fileName) {
  const data = {
    fileName: fileName,
    disk: "<?php echo htmlspecialchars($disk, ENT_QUOTES, 'UTF-8'); ?>",
    path: "<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>",
  };

  fetch("api/cloud/generate_token.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.text())
    .then((text) => {
      console.log("Response from server:", text);
      return JSON.parse(text);
    })
    .then((result) => {
      if (result.error) {
        throw new Error(result.error);
      }

      const mediaUrl = `api/cloud/streaming.php?token=${encodeURIComponent(
        result.token
      )}`;
      const fileExtension = fileName.split(".").pop().toLowerCase();
      const mediaContainer = document.getElementById("mediaContent");

      if (
        ["jpg", "jpeg", "png", "gif", "svg", "bmp", "webp"].includes(
          fileExtension
        )
      ) {
        mediaContainer.innerHTML = `<img src="${mediaUrl}" alt="Media" style="max-width: 100%; height: auto;" />`;
        if (fileExtension === "svg") {
          const svgElement = mediaContainer.querySelector("img");
          svgElement.style.width = "100%";
          svgElement.style.height = "auto";
        }
      } else if (["mp4", "webm", "ogg"].includes(fileExtension)) {
        mediaContainer.innerHTML = `
          <video id="mediaVideo" controls style="max-width: 100%; height: auto;">
              <source src="${mediaUrl}" type="video/${fileExtension}">
              Your browser does not support the video tag.
          </video>
        `;
      } else if (["mp3"].includes(fileExtension)) {
        mediaContainer.innerHTML = `
          <audio id="mediaAudio" controls style="width: 300px; height: 40px;">
              <source src="${mediaUrl}" type="audio/mpeg">
          </audio>
        `;
      } else {
        throw new Error("Unsupported file type");
      }

      const mediaModal = document.getElementById("mediaContainer");
      mediaModal.style.display = "flex";
    })
    .catch((error) => {
      console.error("Error:", error);
      alert(error.message);
    });
}

document.getElementById("closeButton").addEventListener("click", function () {
  const mediaModal = document.getElementById("mediaContainer");
  mediaModal.style.display = "none";


  const videoElement = document.getElementById("mediaVideo");  // Stop video
  if (videoElement) {
    videoElement.pause();
    videoElement.src = ""; // Exit source
  }

  const audioElement = document.getElementById("mediaAudio"); // Stop audio
  if (audioElement) {
    audioElement.pause();
    audioElement.src = "";
  }

  const mediaContainer = document.getElementById("mediaContent");
  mediaContainer.innerHTML = "";
});


// Share
function shareFile(fileName) {
  let sharedWith = prompt(
    "Wprowadź nazwę użytkownika, z którym chcesz udostępnić plik:"
  );
  if (sharedWith) {
    $.ajax({
      url: "api/cloud/share_file.php",
      method: "POST",
      data: {
        file_name: fileName,
        owner: username,
        shared_with: sharedWith,
      },
      success: function (response) {
        if (response.success) {
          alert("Plik został udostępniony użytkownikowi " + sharedWith);
        } else {
          alert(
            "Wystąpił błąd podczas udostępniania pliku: " +
              (response.error || "Nieznany błąd")
          );
        }
      },
      error: function () {
        alert("Wystąpił błąd w trakcie komunikacji z serwerem.");
      },
    });
  }
}

function downloadFile(fileName) {
  // Przygotowanie danych do wygenerowania tokenu
  const data = {
    fileName: fileName,
    disk: "<?php echo htmlspecialchars($disk, ENT_QUOTES, 'UTF-8'); ?>",
    path: "<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>",
  };

  // Żądanie tokenu z backendu
  fetch("api/cloud/generate_token.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.text()) // Tymczasowo odbierz jako tekst
    .then((text) => {
      console.log("Response from server:", text); // Zaloguj odpowiedź serwera
      return JSON.parse(text); // Ręcznie zparsuj JSON
    })
    .then((result) => {
      if (result.error) {
        throw new Error(result.error);
      }
      const downloadUrl = `api/cloud/download_file.php?token=${encodeURIComponent(
        result.token
      )}`;

      window.location.href = downloadUrl;
    })
    .catch((error) => {
      console.error("Error:", error);
      alert(error.message);
    });
}

// Funkcja "Otwórz"
function openFile(fileName, fileType) {
  if (fileType === "folder") {
    const newPath = currentPath ? `${currentPath}/${fileName}` : fileName;
    window.location.href = `?disk=${disk}&path=${newPath}`;
  }
}

// Funkcja do usuwania pliku lub folderu
function deleteFile(fileName) {
  if (confirm("Czy na pewno chcesz usunąć: " + fileName + "?")) {
    const disk = "<?php echo $_GET['disk']; ?>"; // Pobieramy nazwę dysku z URL
    const path = "<?php echo isset($_GET['path']) ? $_GET['path'] : ''; ?>"; // Pobieramy ścieżkę z URL, domyślnie ""

    // Wysyłanie zapytania AJAX do backendu
    $.ajax({
      url: "api/cloud/delete_file.php",
      method: "POST",
      data: {
        file_name: fileName,
        disk_name: disk,
        path: path,
      },
      success: function (response) {
        if (response.success) {
          location.reload();
        } else {
          alert(
            "Wystąpił błąd podczas usuwania: " +
              (response.error || "Nieznany błąd")
          );
        }
      },
      error: function () {
        alert("Wystąpił błąd podczas komunikacji z serwerem.");
      },
    });
  }
}

const fileTable = document.getElementById("fileTable");
const dropOverlay = document.getElementById("dropOverlay");

let dragCounter = 0; // Licznik aktywnych zdarzeń dragenter/dragleave

// Pokaż nakładkę, gdy plik jest przeciągany nad obszarem tabeli
fileTable.addEventListener("dragenter", function (event) {
  event.preventDefault();
  dragCounter++; // Zwiększamy licznik
  dropOverlay.classList.remove("hidden"); // Pokazujemy nakładkę
});

fileTable.addEventListener("dragleave", function (event) {
  event.preventDefault();
  dragCounter--; // Zmniejszamy licznik

  // Ukryj nakładkę tylko, gdy licznik wynosi 0
  if (dragCounter === 0) {
    dropOverlay.classList.add("hidden");
  }
});

fileTable.addEventListener("dragover", function (event) {
  event.preventDefault(); // Pozwala na upuszczenie pliku
});

fileTable.addEventListener("drop", function (event) {
  event.preventDefault();
  dragCounter = 0; // Reset licznika po upuszczeniu pliku
  dropOverlay.classList.add("hidden"); // Ukryj nakładkę po upuszczeniu

  const files = event.dataTransfer.files;

  if (files.length > 0) {
    const formData = new FormData();
    formData.append("file_upload", files[0]);

    const urlParams = new URLSearchParams(window.location.search);
    const path = urlParams.get("path") || "";

    formData.append("disk", "<?php echo $_GET['disk']; ?>");
    formData.append("path", path);

    document.getElementById("upload-status").innerText = "Uploading...";

    fetch("api/cloud/upload_file.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          document.getElementById("upload-status").innerText =
            "Plik został przesłany pomyślnie.";
        } else {
          document.getElementById("upload-status").innerText =
            "Błąd przesyłania pliku: " + data.error;
        }
      })
      .catch((error) => {
        console.error("Błąd:", error);
        document.getElementById("upload-status").innerText =
          "Wystąpił błąd podczas przesyłania.";
      });
  }
});

</script>
</body>
</html>
