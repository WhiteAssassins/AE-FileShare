<?php

function settingsFile(string $dataDir): string
{
    return rtrim($dataDir, '/\\') . DIRECTORY_SEPARATOR . 'settings.json';
}

function defaultSettings(array $users): array
{
    return [
        'private_mode' => defined('PRIVATE_MODE') ? PRIVATE_MODE : true,
        'users' => $users,
    ];
}

function readSettings(string $dataDir, array $users): array
{
    $defaults = defaultSettings($users);
    $file = settingsFile($dataDir);
    if (!is_file($file)) return $defaults;

    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) return $defaults;

    return [
        'private_mode' => array_key_exists('private_mode', $data) ? (bool)$data['private_mode'] : $defaults['private_mode'],
        'users' => is_array($data['users'] ?? null) ? array_replace_recursive($defaults['users'], $data['users']) : $defaults['users'],
    ];
}

function writeSettings(string $dataDir, array $settings): void
{
    @file_put_contents(settingsFile($dataDir), json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function isPrivateModeEnabled(array $settings): bool
{
    return (bool)($settings['private_mode'] ?? true);
}
