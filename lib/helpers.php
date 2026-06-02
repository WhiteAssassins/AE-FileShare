<?php

function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function humanFilesize(int $bytes, int $decimals = 2): string
{
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen((string)$bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
}

function resolvePath(string $root, string $relative): string
{
    $rootReal = realpath($root);
    if ($rootReal === false) {
        return rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR);
    }

    $relative = trim($relative, "/\\");
    if ($relative === '') {
        return $rootReal;
    }

    $candidateReal = realpath($rootReal . DIRECTORY_SEPARATOR . $relative);
    if ($candidateReal === false) {
        return $rootReal;
    }

    $rootPrefix = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $candidatePrefix = rtrim($candidateReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if ($candidateReal !== $rootReal && strpos($candidatePrefix, $rootPrefix) !== 0) {
        return $rootReal;
    }

    return $candidateReal;
}

function relativeFromRoot(string $root, string $path): string
{
    $rootReal = realpath($root) ?: $root;
    $rel = substr($path, strlen($rootReal));
    return trim(str_replace('\\', '/', $rel), '/');
}

function getClientIp(): string
{
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return trim(explode(',', $_SERVER[$key])[0]);
        }
    }
    return '0.0.0.0';
}

function getUserAgent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

function safeDownloadFilename(string $filename): string
{
    return str_replace(["\r", "\n", '"', '\\'], '_', $filename);
}

function isPreviewable(string $path): bool
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'mp4', 'webm', 'mp3', 'wav', 'ogg', 'pdf'], true);
}
