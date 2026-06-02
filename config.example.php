<?php
// Copia este archivo como config.php y ajusta los valores de tu servidor.

require_once __DIR__ . '/lib/env.php';

$ENV = loadEnvFile(__DIR__ . '/.env');

define('APP_VERSION', trim((string)@file_get_contents(__DIR__ . '/VERSION')) ?: '0.1.0');

$APP_TITLE = (string)envValue($ENV, 'APP_TITLE', 'AE-FileShare');

$ROOT_DIR = envPath($ENV, 'ROOT_DIR', 'files', __DIR__);
$DATA_DIR = envPath($ENV, 'DATA_DIR', 'data', __DIR__);

define('FILES_PER_PAGE', envInt($ENV, 'FILES_PER_PAGE', 30));
define('PRIVATE_MODE', envBool($ENV, 'PRIVATE_MODE', true));
define('MAX_UPLOAD_BYTES', envInt($ENV, 'MAX_UPLOAD_BYTES', 1024 * 1024 * 1024));
define('DEFAULT_SHARE_TTL_HOURS', envInt($ENV, 'DEFAULT_SHARE_TTL_HOURS', 24));

// Acceso inicial de desarrollo:
// admin / admin123
// guest / guest123
// Cambia estas claves desde el panel de administracion despues del primer acceso.
$USERS = [
    'admin' => [
        'password_hash' => '$2y$10$b7Ws3fBSwLMiGHb8PHS6euRFc9Mc/b2lWAPMMspU3UiIyJzvhAGa.',
        'role' => 'admin',
        'permissions' => ['upload', 'mkdir', 'rename', 'delete', 'share'],
    ],
    'guest' => [
        'password_hash' => '$2y$10$XNLstHMVGTNlLOY8/WVHrOrA9zTtuwXVSGZZmluVUcAx3/CWOqn5K',
        'role' => 'guest',
        'permissions' => ['upload'],
    ],
];

$BLOCKED_UPLOAD_EXTENSIONS = [
    'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
    'cgi', 'pl', 'asp', 'aspx', 'jsp', 'jspx', 'htaccess', 'htpasswd',
    'exe', 'com', 'bat', 'cmd', 'sh', 'ps1',
];

if (!is_dir($ROOT_DIR)) {
    @mkdir($ROOT_DIR, 0775, true);
}

if (!is_dir($DATA_DIR)) {
    @mkdir($DATA_DIR, 0775, true);
}
