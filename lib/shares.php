<?php

function sharesFile(string $dataDir): string
{
    return rtrim($dataDir, '/\\') . DIRECTORY_SEPARATOR . 'shares.json';
}

function readShares(string $dataDir): array
{
    $file = sharesFile($dataDir);
    if (!is_file($file)) return [];

    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function writeShares(string $dataDir, array $shares): void
{
    @file_put_contents(sharesFile($dataDir), json_encode($shares, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function createShare(string $dataDir, string $relPath, int $ttlHours, ?string $password, string $createdBy): array
{
    $shares = readShares($dataDir);
    $token = bin2hex(random_bytes(24));
    $shares[$token] = [
        'path' => $relPath,
        'expires_at' => time() + max(1, $ttlHours) * 3600,
        'password_hash' => $password !== null && $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
        'created_by' => $createdBy,
        'created_at' => time(),
    ];
    writeShares($dataDir, $shares);

    return ['token' => $token] + $shares[$token];
}

function getValidShare(string $dataDir, string $token): ?array
{
    $shares = readShares($dataDir);
    if (!isset($shares[$token])) return null;

    if (($shares[$token]['expires_at'] ?? 0) < time()) {
        unset($shares[$token]);
        writeShares($dataDir, $shares);
        return null;
    }

    return ['token' => $token] + $shares[$token];
}

function shareNeedsPassword(array $share): bool
{
    return !empty($share['password_hash']);
}

function verifySharePassword(array $share, string $password): bool
{
    if (!shareNeedsPassword($share)) return true;
    return password_verify($password, $share['password_hash']);
}
