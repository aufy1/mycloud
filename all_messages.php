<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Pobranie wszystkich wiadomości
$message_query = "SELECT * FROM messages ORDER BY datetime DESC";
$message_result = mysqli_query($database, $message_query);
$messages = mysqli_fetch_all($message_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wszystkie wiadomości</title>
    <link rel="stylesheet" href="path/to/bootstrap.css"> <!-- Dodaj odpowiednią ścieżkę -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<?php require_once 'head.php'; ?>
<body>
<?php require_once 'header.php'; ?>

<main class="container mt-5">
    <h1>Wszystkie wiadomości</h1>
    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Użytkownik</th>
                <th>Odbiorca</th>
                <th>Wiadomość</th>
                <th>Akcja</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $message): ?>
                <tr>
                    <td><?php echo htmlspecialchars($message['id']); ?></td>
                    <td><?php echo htmlspecialchars($message['datetime']); ?></td>
                    <td><?php echo htmlspecialchars($message['user']); ?></td>
                    <td><?php echo htmlspecialchars($message['recipient']); ?></td>
                    <td><?php echo htmlspecialchars($message['message']); ?></td>
                    <td>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $message['id']; ?>">Usuń</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<script>
// Skrypt AJAX do usuwania wiadomości
$(document).on('click', '.delete-btn', function() {
    var messageId = $(this).data('id');
    if (confirm('Czy na pewno chcesz usunąć tę wiadomość?')) {
        $.ajax({
            url: 'api/chat/delete_message.php',
            type: 'POST',
            data: { id: messageId },
            success: function(response) {
                if (response.success) {
                    alert('Wiadomość została usunięta.');
                    location.reload();
                } else {
                    alert('Błąd podczas usuwania wiadomości.');
                }
            }
        });
    }
});
</script>

</body>
</html>
