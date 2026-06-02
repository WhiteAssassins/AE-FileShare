<?php

require_once __DIR__ . '/helpers.php';

function classifyFileType(string $ext): string
{
    $ext = strtolower($ext);
    $images = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $videos = ['mp4', 'mkv', 'webm', 'avi', 'mov'];
    $audios = ['mp3', 'wav', 'ogg', 'flac'];
    $docs = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md', 'rtf', 'odt'];
    $code = ['php', 'js', 'ts', 'css', 'html', 'json', 'xml', 'c', 'cpp', 'h', 'hpp', 'py', 'rb', 'java', 'cs', 'go', 'sql', 'sh', 'bat', 'yml', 'yaml'];
    $archives = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'];

    if (in_array($ext, $images, true)) return 'image';
    if (in_array($ext, $videos, true)) return 'video';
    if (in_array($ext, $audios, true)) return 'audio';
    if (in_array($ext, $docs, true)) return 'doc';
    if (in_array($ext, $code, true)) return 'code';
    if (in_array($ext, $archives, true)) return 'archive';
    return 'other';
}

function fileTypeIconSvg(string $type): string
{
    $class = 'w-5 h-5';
    switch ($type) {
        case 'image':
            return '<svg class="'.$class.'" viewBox="0 0 24 24" fill="currentColor"><path d="M5 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5zm1 2h12v7l-3-3-3.5 3.5L9 11l-3 3V6z"/></svg>';
        case 'video':
            return '<svg class="'.$class.'" viewBox="0 0 24 24" fill="currentColor"><path d="M5 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-3.5l3.553 2.132A1 1 0 0 0 21 15.76V8.24a1 1 0 0 0-1.447-.872L16 9.5V6a2 2 0 0 0-2-2H5z"/></svg>';
        case 'audio':
            return '<svg class="'.$class.'" viewBox="0 0 24 24" fill="currentColor"><path d="M9 5v10.135A3.5 3.5 0 1 0 11 18V9.5l6-1.5v5.135A3.5 3.5 0 1 0 19 15V5L9 7.5V5z"/></svg>';
        case 'doc':
            return '<svg class="'.$class.'" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V9.414a2 2 0 0 0-.586-1.414l-4.414-4.414A2 2 0 0 0 10.586 3H6zm0 2h4.586L15 8.414V20H6V4z"/></svg>';
        case 'code':
            return '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 18l4-4-4-4M8 6L4 10l4 4M14 4l-4 16"/></svg>';
        case 'archive':
            return '<svg class="'.$class.'" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2a2 2 0 0 0-2 2v3h16V4a2 2 0 0 0-2-2H6zm0 7v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9H6zm5 2h2v2h-2v-2z"/></svg>';
        default:
            return '<svg class="'.$class.'" viewBox="0 0 24 24" fill="currentColor"><path d="M7 3.75A1.75 1.75 0 0 1 8.75 2h6.5A1.75 1.75 0 0 1 17 3.75V9h3l-4.5 4.5L11 9h3V3.5h-5V20h9v2H8.75A1.75 1.75 0 0 1 7 20.25V3.75Z"/></svg>';
    }
}

function listDirectory(string $dir): array
{
    if (!is_dir($dir)) {
        return ['dirs' => [], 'files' => []];
    }

    $items = scandir($dir) ?: [];
    $dirs = [];
    $files = [];

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (strpos($item, '.') === 0) continue;

        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($fullPath)) {
            $dirs[] = $item;
        } elseif (is_file($fullPath)) {
            $files[] = $item;
        }
    }

    sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return ['dirs' => $dirs, 'files' => $files];
}

function filterByQuery(array $names, string $q): array
{
    if ($q === '') return $names;
    $q = mb_strtolower($q);
    return array_values(array_filter($names, function($name) use ($q) {
        return mb_strpos(mb_strtolower($name), $q) !== false;
    }));
}

function paginate(array $items, int $page, int $perPage): array
{
    $total = count($items);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    $slice = array_slice($items, $offset, $perPage);

    return [
        'items' => $slice,
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'per_page' => $perPage,
        'offset' => $offset,
    ];
}
