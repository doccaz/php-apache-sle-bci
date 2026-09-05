<?php
declare(strict_types=1);

$dbFile = __DIR__ . '/data/counter.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE IF NOT EXISTS visits (id INTEGER PRIMARY KEY CHECK (id = 1), count INTEGER NOT NULL)');
$pdo->exec('INSERT INTO visits (id, count) VALUES (1, 1) ON CONFLICT(id) DO UPDATE SET count = count + 1');
$count = (int) $pdo->query('SELECT count FROM visits WHERE id = 1')->fetchColumn();

$extensions = get_loaded_extensions();
sort($extensions, SORT_FLAG_CASE | SORT_STRING);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>php-apache-sle-bci</title>
<style>
  body { font-family: system-ui, sans-serif; max-width: 40rem; margin: 3rem auto; color: #1a1a1a; }
  code { background: #f0f0f0; padding: 0.1rem 0.3rem; border-radius: 3px; }
  .ext { display: inline-block; margin: 0.15rem; padding: 0.15rem 0.5rem; background: #eef; border-radius: 4px; font-size: 0.85rem; }
</style>
</head>
<body>
  <h1>PHP on SUSE BCI</h1>
  <p>Served by <code>registry.suse.com/bci/php-apache</code>, PHP <?= htmlspecialchars(PHP_VERSION) ?>.</p>
  <p>This page has been requested <strong><?= $count ?></strong> time<?= $count === 1 ? '' : 's' ?>,
     counted through a SQLite database written via <code>PDO</code> (<code>data/counter.sqlite</code>) —
     proof that the container's writable storage and the <code>pdo_sqlite</code> extension both work end to end.</p>
  <p>See also <a href="phpinfo.php">phpinfo()</a> and <a href="healthz.php">/healthz.php</a>.</p>
  <h2>Loaded extensions (<?= count($extensions) ?>)</h2>
  <p><?php foreach ($extensions as $ext): ?><span class="ext"><?= htmlspecialchars($ext) ?></span><?php endforeach; ?></p>
</body>
</html>
