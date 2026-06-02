<?php

require_once __DIR__ . '/helpers.php';

function logDownload(string $dataDir, string $relPath, int $sizeBytes, string $kind): void
{
    $logFile = $dataDir . '/downloads.log';
    $line = implode('|', [
        date('c'),
        getClientIp(),
        str_replace(["\n","|"], ' ', getUserAgent()),
        $relPath,
        $sizeBytes,
        $kind,   // file o folder
    ]) . "\n";

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function readStats(string $dataDir, string $rootDir): array
{
    $logFile = $dataDir . '/downloads.log';
    $stats = [
        'total_downloads' => 0,
        'total_bytes'     => 0,
        'per_file'        => [],
        'first'           => null,
        'last'            => null,
        'disk'            => [
            'total' => @disk_total_space($rootDir) ?: 0,
            'free'  => @disk_free_space($rootDir) ?: 0,
        ],
    ];

    if (!is_file($logFile)) {
        return $stats;
    }

    $fh = @fopen($logFile, 'r');
    if (!$fh) return $stats;

    while (($line = fgets($fh)) !== false) {
        $parts = explode('|', trim($line));
        if (count($parts) < 6) continue;
        [$ts, $ip, $ua, $rel, $size, $kind] = $parts;
        $size = (int)$size;

        $stats['total_downloads']++;
        $stats['total_bytes'] += $size;

        if ($stats['first'] === null || $ts < $stats['first']) {
            $stats['first'] = $ts;
        }
        if ($stats['last'] === null || $ts > $stats['last']) {
            $stats['last'] = $ts;
        }

        if (!isset($stats['per_file'][$rel])) {
            $stats['per_file'][$rel] = ['count' => 0, 'bytes' => 0];
        }
        $stats['per_file'][$rel]['count']++;
        $stats['per_file'][$rel]['bytes'] += $size;
    }
    fclose($fh);

    // Ordenar por descargas
    uasort($stats['per_file'], function($a, $b) {
        return $b['count'] <=> $a['count'];
    });

    return $stats;
}
