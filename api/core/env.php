<?php
$envPath = dirname(__DIR__, 2) . '/.env';

if (!file_exists($envPath)) {
    throw new Exception('.env file not found');
}

$env = parse_ini_file($envPath);

foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}