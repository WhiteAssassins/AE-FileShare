<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/errors.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/settings.php';
require_once __DIR__ . '/lib/stats.php';
require_once __DIR__ . '/lib/shares.php';

startSecureSession();
sendSecurityHeaders();

$settings = readSettings($DATA_DIR, $USERS);
$USERS = $settings['users'];
$privateMode = isPrivateModeEnabled($settings);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$target = $_GET['t'] ?? $_POST['t'] ?? '';

function redirectToDir(string $dir = ''): void
{
    header('Location: index.php?' . http_build_query(['d' => cleanRelativePath($dir)]));
    exit;
}

function redirectHome(): void
{
    header('Location: index.php');
    exit;
}

function redirectLogin(): void
{
    header('Location: login.php');
    exit;
}

function isAjaxRequest(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function actionDone(string $dir, string $type, string $message, array $extra = []): void
{
    if (isAjaxRequest()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => $type === 'success',
            'type' => $type,
            'message' => $message,
            'redirect' => 'index.php?' . http_build_query(['d' => cleanRelativePath($dir)]),
        ] + $extra);
        exit;
    }

    flash($type, $message);
    redirectToDir($dir);
}

function actionError(int $status, string $message, string $href = 'index.php'): never
{
    if (isAjaxRequest()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'type' => 'error', 'message' => $message]);
        exit;
    }

    renderErrorPage($status, $message, $href);
}

function addZipEntry(ZipArchive $zip, string $root, string $absPath, string $baseName = ''): void
{
    $name = $baseName !== '' ? $baseName : relativeFromRoot($root, $absPath);
    $name = trim(str_replace('\\', '/', $name), '/');
    if ($name === '' || str_starts_with(basename($name), '.')) return;

    if (is_dir($absPath)) {
        $zip->addEmptyDir($name);
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            if (str_starts_with($file->getFilename(), '.')) continue;
            $localName = $name . '/' . trim(str_replace('\\', '/', substr($file->getPathname(), strlen($absPath) + 1)), '/');
            if ($file->isDir()) {
                $zip->addEmptyDir($localName);
            } elseif ($file->isFile()) {
                $zip->addFile($file->getPathname(), $localName);
            }
        }
        return;
    }

    if (is_file($absPath)) {
        $zip->addFile($absPath, $name);
    }
}

function streamZip(string $tmpZip, string $filename): void
{
    $size = filesize($tmpZip);
    $filename = safeDownloadFilename($filename);

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . $size);
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    readfile($tmpZip);
    @unlink($tmpZip);
    exit;
}

function streamFile(string $abs, string $mode): void
{
    $size = filesize($abs);
    $filename = safeDownloadFilename(basename($abs));
    $mime = @mime_content_type($abs) ?: 'application/octet-stream';
    $disposition = $mode === 'preview' ? 'inline' : 'attachment';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($mode === 'preview' ? $mime : 'application/octet-stream'));
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('X-Content-Type-Options: nosniff');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $size);
    readfile($abs);
    exit;
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (loginUser($username, $password, $USERS)) {
        logAudit($DATA_DIR, 'login');
        redirectHome();
    }

    flash('error', 'Usuario o clave incorrectos');
    redirectLogin();
}

if ($action === 'sharedownload') {
    $token = (string)($_GET['s'] ?? '');
    $share = getValidShare($DATA_DIR, $token);

    if (!$share) {
        renderErrorPage(410, 'Este enlace compartido no existe o ya expiro.', 'index.php');
    }

    if (shareNeedsPassword($share) && empty($_SESSION['share_ok'][$token])) {
        renderErrorPage(403, 'Este enlace requiere contrasena antes de descargar.', 'share.php?' . http_build_query(['s' => $token]), 'Abrir enlace');
    }

    $abs = resolvePath($ROOT_DIR, $share['path']);
    if (!is_file($abs)) {
        renderErrorPage(404, 'El archivo de este enlace ya no esta disponible.', 'index.php');
    }

    logDownload($DATA_DIR, relativeFromRoot($ROOT_DIR, $abs), filesize($abs), 'shared-file');
    logAudit($DATA_DIR, 'sharedownload', relativeFromRoot($ROOT_DIR, $abs), ['token' => $token]);
    streamFile($abs, 'download');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !($action === 'multizip' && !$privateMode)) {
    requireAuth();
    requireCsrf();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    logAudit($DATA_DIR, 'logout');
    logoutUser();
    redirectHome();
}

if ($action === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    $dir = cleanRelativePath($_POST['d'] ?? '');
    $settings['private_mode'] = isset($_POST['private_mode']);

    foreach (['admin', 'guest'] as $username) {
        $password = trim((string)($_POST[$username . '_password'] ?? ''));
        if ($password !== '') {
            if (!isset($settings['users'][$username])) {
                $settings['users'][$username] = [
                    'role' => $username === 'admin' ? 'admin' : 'guest',
                    'permissions' => $username === 'admin' ? ['upload', 'mkdir', 'rename', 'delete', 'share'] : ['upload'],
                ];
            }
            $settings['users'][$username]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    writeSettings($DATA_DIR, $settings);
    logAudit($DATA_DIR, 'settings', '', ['private_mode' => $settings['private_mode']]);
    actionDone($dir, 'success', 'Configuracion guardada');
}

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('upload');
    $dir = cleanRelativePath($_POST['d'] ?? '');
    $destDir = resolvePath($ROOT_DIR, $dir);

    if (!is_dir($destDir) || empty($_FILES['files'])) {
        actionDone($dir, 'error', 'No se pudo subir archivos');
    }

    $uploaded = 0;
    foreach ($_FILES['files']['name'] as $i => $originalName) {
        if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($_FILES['files']['size'][$i] > MAX_UPLOAD_BYTES) continue;

        $name = safeStorageName($originalName);
        if (isBlockedExtension($name, $BLOCKED_UPLOAD_EXTENSIONS)) continue;

        $dest = buildPathInRoot($ROOT_DIR, trim($dir . '/' . $name, '/'));
        if ($dest === null) continue;

        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $counter = 1;
        while (file_exists($dest)) {
            $candidate = $base . '-' . $counter . ($ext !== '' ? '.' . $ext : '');
            $dest = buildPathInRoot($ROOT_DIR, trim($dir . '/' . $candidate, '/'));
            $counter++;
        }

        if ($dest && move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
            $uploaded++;
            logAudit($DATA_DIR, 'upload', relativeFromRoot($ROOT_DIR, $dest));
        }
    }

    actionDone($dir, $uploaded > 0 ? 'success' : 'error', $uploaded > 0 ? "Subidos {$uploaded} archivo(s)" : 'No se subio ningun archivo valido');
}

if ($action === 'mkdir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('mkdir');
    $dir = cleanRelativePath($_POST['d'] ?? '');
    $name = safeStorageName($_POST['name'] ?? '');
    $dest = buildPathInRoot($ROOT_DIR, trim($dir . '/' . $name, '/'));

    if ($dest && !file_exists($dest) && mkdir($dest, 0775, true)) {
        logAudit($DATA_DIR, 'mkdir', relativeFromRoot($ROOT_DIR, $dest));
        actionDone($dir, 'success', 'Carpeta creada');
    } else {
        actionDone($dir, 'error', 'No se pudo crear la carpeta');
    }
}

if ($action === 'rename' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('rename');
    $dir = cleanRelativePath($_POST['d'] ?? '');
    $oldRel = cleanRelativePath($_POST['old'] ?? '');
    $newName = safeStorageName($_POST['new'] ?? '');
    $oldAbs = resolvePath($ROOT_DIR, $oldRel);
    $newAbs = buildPathInRoot($ROOT_DIR, trim(dirname($oldRel) . '/' . $newName, './'));

    if ($oldRel !== '' && $newAbs && file_exists($oldAbs) && !file_exists($newAbs) && rename($oldAbs, $newAbs)) {
        logAudit($DATA_DIR, 'rename', $oldRel, ['new' => relativeFromRoot($ROOT_DIR, $newAbs)]);
        actionDone($dir, 'success', 'Elemento renombrado');
    } else {
        actionDone($dir, 'error', 'No se pudo renombrar');
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('delete');
    $dir = cleanRelativePath($_POST['d'] ?? '');
    $rel = cleanRelativePath($_POST['t'] ?? '');
    $abs = resolvePath($ROOT_DIR, $rel);
    $ok = false;

    if (is_file($abs)) {
        $ok = unlink($abs);
    } elseif (is_dir($abs) && relativeFromRoot($ROOT_DIR, $abs) !== '') {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        $ok = rmdir($abs);
    }

    if ($ok) {
        logAudit($DATA_DIR, 'delete', $rel);
        actionDone($dir, 'success', 'Elemento borrado');
    } else {
        actionDone($dir, 'error', 'No se pudo borrar');
    }
}

if ($action === 'share' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('share');
    $dir = cleanRelativePath($_POST['d'] ?? '');
    $rel = cleanRelativePath($_POST['t'] ?? '');
    $abs = resolvePath($ROOT_DIR, $rel);

    if (!is_file($abs)) {
        actionDone($dir, 'error', 'Solo se pueden compartir archivos');
    }

    $ttl = max(1, min(24 * 30, (int)($_POST['ttl_hours'] ?? DEFAULT_SHARE_TTL_HOURS)));
    $password = trim((string)($_POST['password'] ?? ''));
    $share = createShare($DATA_DIR, relativeFromRoot($ROOT_DIR, $abs), $ttl, $password !== '' ? $password : null, currentUser()['username']);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/share.php?s=' . $share['token'];

    logAudit($DATA_DIR, 'share', $rel, ['expires_at' => $share['expires_at'], 'password' => $password !== '']);
    actionDone($dir, 'success', 'Enlace creado: ' . $url, ['share_url' => $url]);
}

if ($action === 'multizip' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($privateMode) requireAuth();
    $dir = cleanRelativePath($_POST['d'] ?? '');
    $items = $_POST['items'] ?? [];

    if (!is_array($items) || empty($items) || !class_exists('ZipArchive')) {
        flash('error', 'No se pudo crear el ZIP');
        redirectToDir($dir);
    }

    $zip = new ZipArchive();
    $tmpZip = tempnam(sys_get_temp_dir(), 'fhub_multi_') . '.zip';
    if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        flash('error', 'No se pudo crear el ZIP');
        redirectToDir($dir);
    }

    foreach ($items as $rel) {
        $rel = cleanRelativePath((string)$rel);
        $abs = resolvePath($ROOT_DIR, $rel);
        if (file_exists($abs)) {
            addZipEntry($zip, $ROOT_DIR, $abs);
        }
    }
    $zip->close();

    logAudit($DATA_DIR, 'multizip', $dir, ['count' => count($items)]);
    streamZip($tmpZip, 'seleccion.zip');
}

if ($action === 'download' || $action === 'preview') {
    if ($privateMode) requireAuth();
    $abs = resolvePath($ROOT_DIR, $target);
    if (!is_file($abs)) {
        actionError(404, 'El archivo solicitado no existe o fue movido.');
    }

    if ($action === 'preview' && !isPreviewable($abs)) {
        actionError(415, 'Este tipo de archivo no tiene vista previa disponible.');
    }

    $rel = relativeFromRoot($ROOT_DIR, $abs);
    if ($action === 'download') {
        logDownload($DATA_DIR, $rel, filesize($abs), 'file');
        logAudit($DATA_DIR, 'download', $rel);
    } else {
        logAudit($DATA_DIR, 'preview', $rel);
    }

    streamFile($abs, $action);
}

if ($action === 'zipdir') {
    if ($privateMode) requireAuth();
    $absDir = resolvePath($ROOT_DIR, $target);
    if (!is_dir($absDir) || !class_exists('ZipArchive')) {
        actionError(404, 'La carpeta no existe o la extension ZIP no esta disponible.');
    }

    $zip = new ZipArchive();
    $tmpZip = tempnam(sys_get_temp_dir(), 'fhub_') . '.zip';
    if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        actionError(500, 'No se pudo crear el archivo ZIP.');
    }

    addZipEntry($zip, $ROOT_DIR, $absDir, basename($absDir) ?: 'carpeta');
    $zip->close();

    $rel = relativeFromRoot($ROOT_DIR, $absDir) ?: '/';
    logDownload($DATA_DIR, $rel . ' (ZIP)', filesize($tmpZip), 'folder');
    logAudit($DATA_DIR, 'zipdir', $rel);
    streamZip($tmpZip, (basename($absDir) ?: 'carpeta') . '.zip');
}

http_response_code(400);
renderErrorPage(400, 'La accion solicitada no es valida.');
