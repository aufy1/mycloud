<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$query = "SELECT ip_address, login_datetime, successful_login, username, screen_resolution, window_resolution, color_depth, cookies_enabled, browser_name, browser_language, location FROM login_attempts ORDER BY login_datetime DESC";
$result = mysqli_query($database, $query);
$logins = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $logins[] = $row;
    }
    mysqli_free_result($result);
}

mysqli_close($database);
?>

<?php require_once 'head.php'; ?>
<body class="bg-gray-100">
<?php require_once 'header.php'; ?>
<main>
    <section class="sekcja1 py-10">
        <div class="container mx-auto">
            <div class="px-5 overflow-x-auto bg-white shadow-md rounded-lg">
                <table id="loginsTable" class="min-w-full text-center table-auto">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600">
                            <th class="px-4 py-2 text-sm font-semibold">Date & Time</th>
                            <th class="px-4 py-2 text-sm font-semibold">Location</th>
                            <th class="px-4 py-2 text-sm font-semibold">IP Address</th>
                            <th class="px-4 py-2 text-sm font-semibold">Username</th>
                            <th class="px-4 py-2 text-sm font-semibold">Successful Login</th>
                            <th class="px-4 py-2 text-sm font-semibold">Screen Resolution</th>
                            <th class="px-4 py-2 text-sm font-semibold">Window Resolution</th>
                            <th class="px-4 py-2 text-sm font-semibold">Color Depth</th>
                            <th class="px-4 py-2 text-sm font-semibold">Cookies Enabled</th>
                            <th class="px-4 py-2 text-sm font-semibold">Browser Name</th>
                            <th class="px-4 py-2 text-sm font-semibold">Browser Language</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logins as $login): ?>
                        <tr class="border-t border-gray-200">
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['login_datetime']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['location']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['ip_address']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['username']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['successful_login']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['screen_resolution']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['window_resolution']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['color_depth']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['cookies_enabled']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['browser_name']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($login['browser_language']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<?php require_once 'footer.php'; ?>
<script type="text/javascript">
$(document).ready(function() {
    $('#loginsTable').DataTable({
        "order": [[0, "desc"]],  // Ustawienie domyślnego sortowania na kolumnę z datą logowania
        "pageLength": 50       // Ustawienie domyślnej liczby rekordów na stronie na 100
    });
});
</script>

</body>
</html>
