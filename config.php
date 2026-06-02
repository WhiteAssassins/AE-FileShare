<?php
// Titulo de la app
$APP_TITLE = 'AEWhite Devs FileHub';

// Carpeta raiz de archivos
$ROOT_DIR = __DIR__ . '/files';

// Carpeta para logs y datos
$DATA_DIR = __DIR__ . '/data';

// Archivos por pagina
define('FILES_PER_PAGE', 30);
define('PRIVATE_MODE', true);
define('MAX_UPLOAD_BYTES', 1024 * 1024 * 1024);
define('DEFAULT_SHARE_TTL_HOURS', 24);

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
