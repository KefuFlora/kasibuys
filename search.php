<?php
header("Location: listings.php?q=" . urlencode($_GET['q'] ?? ''));
exit;
?>