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



            <h2>Akcje <span style="color: red;">(blokada serwera dla bezpieczeństwa - mogę wyłączyć do pokazania!)</span></h2>

        <h4>Wyswietlenie listy procesów serwera WWW</h4>
        <?php
        exec ('TERM=xterm /usr/bin/top n 1 b i', $top, $error );
        echo nl2br(implode("\n",$top));
        if ($error){
        exec ('TERM=xterm /usr/bin/top n 1 b 2>&1', $error );
        echo "Error: ";
        exit ($error[0]);
        }
        ?>

<h4>Whoami</h4>
<?php
echo exec ('whoami');
?>


<h4>Wyswietlenie listy plików i uprawnien</h4>
<?php
$output = shell_exec ('ls -al');
echo "<pre>$output</pre>";
?>


<h4>DNS PBS</h4>
<?php
$result = dns_get_record("pbs.edu.pl");
print_r($result);
?>

<?php
$ip = gethostbyname('pbs.edu.pl');
echo $ip . '<BR />';
$ip = $_SERVER["REMOTE_ADDR"];
echo $ip. '<BR />';
$hostname = gethostbyaddr("8.8.8.8");
echo $hostname. '<BR />';
$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
echo $hostname;
?>


<h4>Netstat</h4>
<?php
// Wykonanie polecenia `netstat` i pobranie wyników jako string
$output = shell_exec('netstat -a');
echo "<pre>$output</pre>";
?>



        </div>
    </section>
</main>	
<?php require_once 'footer.php'; ?>			
</body>
</html>
