<?php
declare(strict_types=1);

header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'php_version' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'extensions' => get_loaded_extensions(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
