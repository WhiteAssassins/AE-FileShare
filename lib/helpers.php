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

function iniBytes(string $value): int
{
    $value = trim($value);
    if ($value === '') return 0;

    $unit = strtolower($value[strlen($value) - 1]);
    $bytes = (int)$value;

    return match ($unit) {
        'g' => $bytes * 1024 * 1024 * 1024,
        'm' => $bytes * 1024 * 1024,
        'k' => $bytes * 1024,
        default => $bytes,
    };
}

function uploadErrorMessage(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE => 'El archivo supera upload_max_filesize de PHP.',
        UPLOAD_ERR_FORM_SIZE => 'El archivo supera el limite permitido por el formulario.',
        UPLOAD_ERR_PARTIAL => 'La subida se interrumpio antes de completarse.',
        UPLOAD_ERR_NO_FILE => 'No se recibio ningun archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'PHP no tiene carpeta temporal para subidas.',
        UPLOAD_ERR_CANT_WRITE => 'PHP no pudo escribir el archivo temporal en disco.',
        UPLOAD_ERR_EXTENSION => 'Una extension de PHP bloqueo la subida.',
        default => 'Error desconocido durante la subida.',
    };
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

function cleanRelativePath(string $relative): string
{
    $relative = str_replace('\\', '/', trim($relative));
    $parts = [];

    foreach (explode('/', $relative) as $part) {
        $part = trim($part);
        if ($part === '' || $part === '.') continue;
        if ($part === '..') continue;
        $parts[] = $part;
    }

    return implode('/', $parts);
}

function buildPathInRoot(string $root, string $relative): ?string
{
    $rootReal = realpath($root);
    if ($rootReal === false) return null;

    $relative = cleanRelativePath($relative);
    $path = $rootReal . ($relative === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    $parent = dirname($path);
    $parentReal = realpath($parent);

    if ($parentReal === false) return null;

    $rootPrefix = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $parentPrefix = rtrim($parentReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if ($parentReal !== $rootReal && strpos($parentPrefix, $rootPrefix) !== 0) {
        return null;
    }

    return $path;
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
    $filename = str_replace(["\r", "\n", '"', '\\', '/', "\0"], '_', $filename);
    return trim($filename) !== '' ? $filename : 'download';
}

function safeStorageName(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/[^\w.\- ]+/u', '_', $name) ?? '';
    $name = trim($name, " .\t\n\r\0\x0B");
    return $name !== '' ? $name : 'archivo';
}

function fileExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isBlockedExtension(string $filename, array $blockedExtensions): bool
{
    return in_array(fileExtension($filename), $blockedExtensions, true);
}

function isPreviewable(string $path): bool
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'mp4', 'webm', 'mp3', 'wav', 'ogg', 'pdf'], true);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function takeFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
