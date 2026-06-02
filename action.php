<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/stats.php';

$action = $_GET['action'] ?? '';
$target = $_GET['t'] ?? '';

if ($action === 'download' || $action === 'preview') {
    $abs = resolvePath($ROOT_DIR, $target);
    if (!is_file($abs)) {
        http_response_code(404);
        echo 'Archivo no encontrado';
        exit;
    }

    if ($action === 'preview' && !isPreviewable($abs)) {
        http_response_code(415);
        echo 'Vista previa no soportada';
        exit;
    }

    $rel = relativeFromRoot($ROOT_DIR, $abs);
    $size = filesize($abs);

    if ($action === 'download') {
        logDownload($DATA_DIR, $rel, $size, 'file');
    }

    $filename = safeDownloadFilename(basename($abs));
    $mime = @mime_content_type($abs) ?: 'application/octet-stream';
    $disposition = $action === 'preview' ? 'inline' : 'attachment';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($action === 'preview' ? $mime : 'application/octet-stream'));
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('X-Content-Type-Options: nosniff');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $size);
    readfile($abs);
    exit;
}

if ($action === 'zipdir') {
    $absDir = resolvePath($ROOT_DIR, $target);
    if (!is_dir($absDir)) {
        http_response_code(404);
        echo 'Carpeta no encontrada';
        exit;
    }

    if (!class_exists('ZipArchive')) {
        echo 'ZipArchive no disponible en el servidor.';
        exit;
    }

    $zip = new ZipArchive();
    $tmpZip = tempnam(sys_get_temp_dir(), 'fhub_') . '.zip';
    if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo 'No se pudo crear el ZIP.';
        exit;
    }

    $rootLen = strlen($absDir) + 1;

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        $filePath = $file->getPathname();
        $localName = substr($filePath, $rootLen);
        if ($file->isDir()) {
            $zip->addEmptyDir($localName);
        } else {
            $zip->addFile($filePath, $localName);
        }
    }

    $zip->close();

    $size = filesize($tmpZip);
    $rel = trim($target, '/');
    if ($rel === '') $rel = '/';
    logDownload($DATA_DIR, $rel . ' (ZIP)', $size, 'folder');

    $zipName = safeDownloadFilename(basename($absDir) ?: 'carpeta');
    $zipFilename = $zipName . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFilename . '"; filename*=UTF-8\'\'' . rawurlencode($zipFilename));
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . $size);
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    readfile($tmpZip);
    @unlink($tmpZip);
    exit;
}

http_response_code(400);
echo 'Accion invalida';
