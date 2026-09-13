<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/middleware.php';
require_once __DIR__ . '/includes/audit.php';

Middleware::admin();

header('Content-Type: text/plain; charset=utf-8');

$secretPatterns = [
    '/PASSWORD$/i',
    '/SECRET$/i',
    '/KEY$/i',
    '/^DATABASE_URL$/i',
    '/TOKEN$/i',
];

function is_secret(string $key): bool
{
    global $secretPatterns;
    foreach ($secretPatterns as $pattern) {
        if (preg_match($pattern, $key) === 1) {
            return true;
        }
    }
    return false;
}

function mask(string $value): string
{
    if ($value === '' || $value === null) {
        return '(empty)';
    }
    $len = strlen($value);
    if ($len <= 4) {
        return '***';
    }
    return substr($value, 0, 4) . str_repeat('*', max(0, $len - 4));
}

function get_combined_env(): array
{
    $combined = [];

    foreach ($_ENV as $key => $value) {
        $combined[$key] = $value;
    }

    foreach ($_SERVER as $key => $value) {
        $combined[$key] = $value;
    }

    foreach (getenv() as $key => $value) {
        $combined[$key] = $value;
    }

    ksort($combined);

    return $combined;
}

echo "=== ENVIRONMENT VARIABLES ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "APP_ENV: " . e(APP_ENV) . "\n";
echo "APP_DEBUG: " . (APP_DEBUG ? 'true' : 'false') . "\n";
echo "APP_URL: " . e(APP_URL) . "\n\n";

echo "=== $_ENV ===\n";
foreach ($_ENV as $key => $value) {
    echo $key . "=" . (is_secret($key) ? mask((string)$value) : e((string)$value)) . "\n";
}

echo "\n=== $_SERVER ===\n";
foreach ($_SERVER as $key => $value) {
    $display = is_array($value) ? json_encode($value) : (string)$value;
    echo $key . "=" . e($display) . "\n";
}

echo "\n=== getenv() ===\n";
foreach (getenv() as $key => $value) {
    echo $key . "=" . (is_secret($key) ? mask((string)$value) : e((string)$value)) . "\n";
}

echo "\n=== DATABASE CONFIG ===\n";
echo "driver: pgsql\n";
echo "host: " . e((string)($_ENV['DB_HOST'] ?? 'n/a')) . "\n";
echo "port: " . e((string)($_ENV['DB_PORT'] ?? 'n/a')) . "\n";
echo "dbname: " . e((string)($_ENV['DB_NAME'] ?? 'n/a')) . "\n";
echo "user: " . e((string)($_ENV['DB_USER'] ?? 'n/a')) . "\n";
echo "password: " . (is_secret('DB_PASSWORD') ? mask((string)($_ENV['DB_PASSWORD'] ?? '')) : e((string)($_ENV['DB_PASSWORD'] ?? ''))) . "\n";
echo "DATABASE_URL: " . (is_secret('DATABASE_URL') ? mask((string)($_ENV['DATABASE_URL'] ?? '')) : e((string)($_ENV['DATABASE_URL'] ?? ''))) . "\n";

echo "\n=== RENDER CONFIG (from render.yaml) ===\n";
echo "PHP_VERSION: " . e((string)($_ENV['PHP_VERSION'] ?? 'n/a')) . "\n";
echo "SESSION_SECURE_COOKIE: " . e((string)($_ENV['SESSION_SECURE_COOKIE'] ?? 'n/a')) . "\n";

echo "\n=== END ===\n";
