<?php
$dbHost = getenv('DB_HOST') ?: 'db';
$dbName = getenv('MYSQL_DATABASE') ?: 'iaas_demo';
$dbUser = getenv('MYSQL_USER') ?: 'iaasuser';
$dbPass = getenv('MYSQL_PASSWORD') ?: 'iaaspass';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $stmt = $pdo->query('SELECT NOW() as now');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbStatus = 'OK (' . $row['now'] . ')';
} catch (Exception $e) {
    $dbStatus = 'ERROR: ' . $e->getMessage();
}
?>
<!doctype html>
<html>
  <head><title>IaaS Web Hosting Demo</title></head>
  <body>
    <h1>Hello from IaaS Web Hosting demo</h1>
    <p>Database status: <?php echo htmlspecialchars($dbStatus); ?></p>
  </body>
</html>
