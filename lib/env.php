<?php

function loadEnvFile(string $path): array
{
    if (!is_file($path)) return [];

    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key === '') continue;

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $env[$key] = $value;
    }

    return $env;
}

function envValue(array $env, string $key, mixed $default): mixed
{
    return array_key_exists($key, $env) ? $env[$key] : $default;
}

function envBool(array $env, string $key, bool $default): bool
{
    if (!array_key_exists($key, $env)) return $default;
    return in_array(strtolower((string)$env[$key]), ['1', 'true', 'yes', 'on'], true);
}

function envInt(array $env, string $key, int $default): int
{
    if (!array_key_exists($key, $env)) return $default;
    return max(0, (int)$env[$key]);
}

function envPath(array $env, string $key, string $default, string $baseDir): string
{
    $value = (string)envValue($env, $key, $default);
    if (preg_match('/^[A-Za-z]:[\/\\\\]/', $value) || str_starts_with($value, '/') || str_starts_with($value, '\\')) {
        return $value;
    }

    return $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $value);
}
