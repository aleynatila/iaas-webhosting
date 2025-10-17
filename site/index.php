<?php
// Environment configuration with validation
$dbHost = getenv('DB_HOST') ?: 'db';
$dbName = getenv('MYSQL_DATABASE') ?: 'iaas_demo';
$dbUser = getenv('MYSQL_USER') ?: 'iaasuser';
$dbPass = getenv('MYSQL_PASSWORD') ?: 'iaaspass';

// Logging function
function log_info($msg) {
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg);
}

// Validate required environment
$required_env = ['MYSQL_DATABASE', 'MYSQL_USER'];
$missing_env = [];
foreach ($required_env as $env_var) {
    if (empty(getenv($env_var))) {
        $missing_env[] = $env_var;
    }
}

log_info("Application started. DB Host: $dbHost, DB Name: $dbName");

// Database connection
$dbStatus = 'ERROR: Not connected';
$dbTime = 'N/A';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $stmt = $pdo->query('SELECT NOW() as now');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbTime = $row['now'];
    $dbStatus = 'Connected';
    log_info("Database connection successful. Time: $dbTime");
} catch (Exception $e) {
    $dbStatus = 'ERROR: ' . $e->getMessage();
    log_info("Database connection failed: " . $e->getMessage());
}
?>
<!doctype html>
<html>
  <head>
    <title>IaaS Web Hosting Demo</title>
    <style>
      body { font-family: Arial, sans-serif; margin: 40px; }
      .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
      .ok { background: #d4edda; color: #155724; }
      .error { background: #f8d7da; color: #721c24; }
      .warning { background: #fff3cd; color: #856404; }
      code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
  </head>
  <body>
    <h1>IaaS Web Hosting Demo</h1>
    
    <?php if (!empty($missing_env)): ?>
    <div class="status warning">
      ⚠️ <strong>Missing environment variables:</strong> <?php echo htmlspecialchars(implode(', ', $missing_env)); ?>
    </div>
    <?php endif; ?>
    
    <div class="status <?php echo strpos($dbStatus, 'ERROR') === false ? 'ok' : 'error'; ?>">
      <strong>Database Status:</strong> <?php echo htmlspecialchars($dbStatus); ?>
      <?php if ($dbStatus === 'Connected'): ?>
        <br><code>Time: <?php echo htmlspecialchars($dbTime); ?></code>
      <?php endif; ?>
    </div>
    
    <h2>Environment Info</h2>
    <ul>
      <li><code>DB_HOST</code>: <?php echo htmlspecialchars($dbHost); ?></li>
      <li><code>MYSQL_DATABASE</code>: <?php echo htmlspecialchars($dbName); ?></li>
      <li><code>PHP Version</code>: <?php echo phpversion(); ?></li>
      <li><code>Hostname</code>: <?php echo gethostname(); ?></li>
    </ul>
  </body>
</html>
