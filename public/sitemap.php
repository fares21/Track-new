<?php
header('Content-Type: application/xml; charset=utf-8');
$cfg = include __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/core/Autoloader.php';

use App\core\Database;

$pdo = Database::conn($cfg);
$base = (function() use ($cfg){
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $protocol.'://'.$host.$dir;
})();
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?=$base?>/</loc><priority>1.0</priority></url>
  <url><loc><?=$base?>/my</loc><priority>0.5</priority></url>
<?php
$stmt = $pdo->query("SELECT track_number, GREATEST(COALESCE(last_update_at, created_at), created_at) as lu FROM track_items ORDER BY lu DESC LIMIT 500");
while ($row = $stmt->fetch()) {
    $loc = $base . '/track/' . urlencode($row['track_number']);
    $lastmod = date('c', strtotime($row['lu']));
    echo "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><priority>0.6</priority></url>";
}
?>
</urlset>
