<?php
// Copy this file to config.php and adjust values for your server.

$APP_TITLE = 'AE-FileShare';

$ROOT_DIR = __DIR__ . '/files';
$DATA_DIR = __DIR__ . '/data';

define('FILES_PER_PAGE', 30);
define('PRIVATE_MODE', true);
define('MAX_UPLOAD_BYTES', 1024 * 1024 * 1024);
define('DEFAULT_SHARE_TTL_HOURS', 24);

// Generate password hashes with:
// php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
$USERS = [
    'admin' => [
        'password_hash' => 'REPLACE_WITH_ADMIN_PASSWORD_HASH',
        'role' => 'admin',
        'permissions' => ['upload', 'mkdir', 'rename', 'delete', 'share'],
    ],
    'guest' => [
        'password_hash' => 'REPLACE_WITH_GUEST_PASSWORD_HASH',
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
