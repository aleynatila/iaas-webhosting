<?php
$dbHost = getenv('DB_HOST') ?: 'db';
$dbName = getenv('MYSQL_DATABASE') ?: 'iaas_demo';
$dbUser = getenv('MYSQL_USER') ?: 'iaasuser';
$dbPass = getenv('MYSQL_PASSWORD') ?: 'iaaspass';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $stmt = $pdo->query('SELECT id, title, content, created_at FROM posts ORDER BY created_at DESC');
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $posts = [];
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!doctype html>
<html>
  <head>
    <title>IaaS Web Hosting - Posts</title>
    <style>
      body { font-family: Arial, sans-serif; margin: 40px; max-width: 800px; }
      .post { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
      .post h3 { margin-top: 0; color: #333; }
      .meta { font-size: 0.9em; color: #666; }
      a { color: #0066cc; text-decoration: none; }
      a:hover { text-decoration: underline; }
    </style>
  </head>
  <body>
    <h1>Blog Posts</h1>
    <p><a href="index.php">← Back to Home</a></p>
    
    <?php if (isset($error)): ?>
      <div style="background: #f8d7da; padding: 10px; color: #721c24;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <?php if (empty($posts)): ?>
      <p>No posts found.</p>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <div class="post">
          <h3><?php echo htmlspecialchars($post['title']); ?></h3>
          <p><?php echo htmlspecialchars($post['content']); ?></p>
          <div class="meta">Posted on <?php echo htmlspecialchars($post['created_at']); ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </body>
</html>
