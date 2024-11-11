<?php
// Ścieżka do pliku, który chcemy stworzyć
$sciezka = "../../nazwa.ts";

// Tworzenie pliku i otwarcie go w trybie zapisu
$plik = fopen($sciezka, "w");

// Sprawdzenie, czy udało się otworzyć plik
if ($plik) {
    // Zapisanie przykładowej zawartości do pliku (opcjonalne)
    fwrite($plik, "// To jest przykładowa zawartość pliku nazwa.ts\n");
    
    // Zamknięcie pliku po zakończeniu operacji
    fclose($plik);
    
    echo "Plik został utworzony pomyślnie!";
    echo ini_get('open_basedir');
    echo "dupa";
} else {
    echo "Błąd: nie udało się utworzyć pliku.";
    echo ini_get('open_basedir');
}
?>
