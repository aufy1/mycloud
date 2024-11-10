<?php 
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}?>
<?php require_once 'head.php'; ?>	
<body>
<?php require_once 'header.php'; ?>	
	<main> 
		<section class="sekcja1">	
			Polecenie 1.2
		</section>
	</main>	
<?php require_once 'footer.php'; ?>			
</body>
</html>