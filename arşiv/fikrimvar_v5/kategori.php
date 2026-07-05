<?php
declare(strict_types=1);
$category = trim((string) ($_GET['k'] ?? 'all'));
header('Location: hikayeler.php?kategori=' . rawurlencode($category), true, 302);
exit;
