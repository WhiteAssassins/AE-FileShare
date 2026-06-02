<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/fs.php';
require_once __DIR__ . '/lib/stats.php';

startSecureSession();
sendSecurityHeaders();

if (PRIVATE_MODE && !isAuthenticated()) {
    $messages = takeFlash();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?= h($APP_TITLE) ?> - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4">
    <main class="w-full max-w-sm rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-2xl">
        <h1 class="text-xl font-semibold text-white mb-1"><?= h($APP_TITLE) ?></h1>
        <p class="text-sm text-slate-400 mb-5">Acceso privado para amigos e invitados.</p>

        <?php foreach ($messages as $message): ?>
            <div class="mb-3 rounded-xl border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-100">
                <?= h($message['message']) ?>
            </div>
        <?php endforeach; ?>

        <form method="post" action="action.php" class="space-y-3">
            <input type="hidden" name="action" value="login">
            <label class="block text-sm">
                <span class="text-slate-300">Usuario</span>
                <input name="username" required autocomplete="username" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500">
            </label>
            <label class="block text-sm">
                <span class="text-slate-300">Clave</span>
                <input name="password" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500">
            </label>
            <button class="w-full rounded-xl border border-sky-500/70 bg-sky-500/20 px-4 py-2 text-sm font-medium text-sky-100 hover:bg-sky-500/30">
                Entrar
            </button>
        </form>
    </main>
</body>
</html>
    <?php
    exit;
}

$currentRel  = $_GET['d'] ?? '';
$q           = trim($_GET['q'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$previewRel  = $_GET['preview'] ?? '';
$infoRel     = $_GET['info'] ?? '';

$currentDir = resolvePath($ROOT_DIR, $currentRel);
$currentRel = relativeFromRoot($ROOT_DIR, $currentDir);

// Datos directorio
$listing = listDirectory($currentDir);

// Filtro busqueda
$dirs  = filterByQuery($listing['dirs'], $q);
$files = filterByQuery($listing['files'], $q);

// Para paginar SOLO archivos; carpetas siempre visibles arriba
$pagination = paginate($files, $page, FILES_PER_PAGE);
$filesPage  = $pagination['items'];

// Breadcrumbs
$breadcrumbs = [];
if ($currentRel === '') {
    $breadcrumbs[] = ['label' => 'Inicio', 'path' => ''];
} else {
    $breadcrumbs[] = ['label' => 'Inicio', 'path' => ''];
    $parts = explode('/', $currentRel);
    $accum = '';
    foreach ($parts as $p) {
        $accum = $accum === '' ? $p : $accum . '/' . $p;
        $breadcrumbs[] = ['label' => $p, 'path' => $accum];
    }
}

// Stats globales (para panel friki abajo)
$stats = readStats($DATA_DIR, $ROOT_DIR);
$messages = takeFlash();
$user = currentUser();
$csrf = csrfToken();

// Helper URLs
function dirUrl(string $rel, array $extra = []): string {
    $params = array_merge(['d' => $rel], $extra);
    return '?' . http_build_query($params);
}
function dlUrl(string $rel): string {
    return 'action.php?' . http_build_query(['action' => 'download', 't' => $rel]);
}
function previewUrl(string $rel): string {
    return 'action.php?' . http_build_query(['action' => 'preview', 't' => $rel]);
}
function zipDirUrl(string $rel): string {
    return 'action.php?' . http_build_query(['action' => 'zipdir', 't' => $rel]);
}

// Previews
$previewFilePath = null;
$previewType = null;
if ($previewRel !== '') {
    $previewFilePath = resolvePath($ROOT_DIR, $previewRel);
    if (!is_file($previewFilePath)) {
        $previewFilePath = null;
    } else {
        $ext = strtolower(pathinfo($previewFilePath, PATHINFO_EXTENSION));
        $previewType = classifyFileType($ext);
    }
}

// Info friki
$infoFile = null;
$infoMeta = null;
if ($infoRel !== '') {
    $infoFile = resolvePath($ROOT_DIR, $infoRel);
    if (is_file($infoFile)) {
        $infoMeta = [
            'name'   => basename($infoFile),
            'rel'    => $infoRel,
            'size'   => filesize($infoFile),
            'mtime'  => filemtime($infoFile),
            'perms'  => substr(sprintf('%o', fileperms($infoFile)), -4),
            'mime'   => @mime_content_type($infoFile) ?: 'desconocido',
            'md5'    => @md5_file($infoFile) ?: null,
            'sha1'   => @sha1_file($infoFile) ?: null,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?= h($APP_TITLE) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 overflow-y-auto">

<!-- Fondo con degradados y neblina -->
<div class="fixed inset-0 -z-10">
    <div class="w-full h-full bg-gradient-to-b from-slate-900 via-slate-950 to-slate-950"></div>
    <div class="pointer-events-none absolute inset-0 opacity-60 mix-blend-screen">
        <div class="absolute -top-40 -left-40 w-80 h-80 bg-purple-700/30 blur-3xl rounded-full"></div>
        <div class="absolute top-20 right-0 w-80 h-80 bg-sky-500/30 blur-3xl rounded-full"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-96 h-96 bg-fuchsia-500/20 blur-3xl rounded-full"></div>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12 space-y-6">

    <!-- Header -->
    <header class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white">
                <?= h($APP_TITLE) ?>
            </h1>
   
        </div>
        <div class="hidden sm:flex items-center gap-3">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-3 py-1 text-xs text-emerald-100">
                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Servidor en linea</span>
            </div>
            <?php if ($user): ?>
                <span class="text-xs text-slate-400"><?= h($user['username']) ?> / <?= h($user['role']) ?></span>
                <form method="post" action="action.php">
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <button class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 hover:bg-slate-800">Salir</button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <div id="flash-stack" class="space-y-3">
        <?php foreach ($messages as $message): ?>
            <div class="rounded-xl border px-4 py-3 text-sm <?= $message['type'] === 'success' ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-100' : 'border-red-500/40 bg-red-500/10 text-red-100' ?>">
                <?= h($message['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <section id="transfer-panel" class="hidden rounded-2xl border border-slate-800 bg-slate-900/90 p-4 shadow-[0_0_40px_rgba(15,23,42,0.8)]">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-sky-100">Transferencias</h2>
            <button type="button" id="clear-transfers" class="rounded-lg border border-slate-700 px-2 py-1 text-[11px] text-slate-300 hover:bg-slate-800">Limpiar</button>
        </div>
        <div id="transfer-list" class="space-y-3"></div>
    </section>

    <!-- Panel de busqueda + breadcrumbs -->
    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 shadow-[0_0_40px_rgba(15,23,42,0.9)] backdrop-blur-xl overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-800 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex flex-wrap items-center gap-1 text-xs sm:text-sm text-slate-300">
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <?php if ($i > 0): ?>
                        <span class="text-slate-500 mx-1">/</span>
                    <?php endif; ?>
                    <?php if ($i === count($breadcrumbs) - 1): ?>
                        <span class="text-sky-300 font-medium"><?= h($crumb['label']) ?></span>
                    <?php else: ?>
                        <a href="<?= h(dirUrl($crumb['path'])) ?>" class="hover:text-sky-300 transition-colors">
                            <?= h($crumb['label']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <form method="get" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                <input
                    name="q"
                    value="<?= h($q) ?>"
                    placeholder="Buscar archivo..."
                    class="w-full sm:w-56 rounded-xl border border-slate-700/80 bg-slate-900/70 px-3 py-1.5 text-xs sm:text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/70 focus:border-sky-500/70 transition" />
                <button
                    class="text-xs sm:text-sm px-3 py-1.5 rounded-xl border border-slate-700 bg-slate-900/80 hover:bg-slate-800 hover:border-slate-500 transition-all duration-150">
                    Buscar
                </button>
            </form>
        </div>

        <div class="px-4 sm:px-6 py-4 border-b border-slate-800 grid gap-3 lg:grid-cols-3">
            <?php if (canDo('upload')): ?>
                <form method="post" action="action.php" enctype="multipart/form-data" class="rounded-xl border border-slate-800 bg-slate-950/50 p-3" data-upload-form>
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                    <label class="block text-xs text-slate-400 mb-2">Subir archivos</label>
                    <div class="flex gap-2">
                        <input type="file" name="files[]" multiple class="min-w-0 flex-1 text-xs text-slate-300 file:mr-2 file:rounded-lg file:border-0 file:bg-sky-500/20 file:px-3 file:py-1.5 file:text-sky-100">
                        <button class="rounded-lg border border-sky-500/70 bg-sky-500/15 px-3 py-1.5 text-xs text-sky-100 hover:bg-sky-500/30">Subir</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (canDo('mkdir')): ?>
                <form method="post" action="action.php" class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                    <input type="hidden" name="action" value="mkdir">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                    <label class="block text-xs text-slate-400 mb-2">Crear carpeta</label>
                    <div class="flex gap-2">
                        <input name="name" required placeholder="Nombre" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-100">
                        <button class="rounded-lg border border-emerald-500/70 bg-emerald-500/15 px-3 py-1.5 text-xs text-emerald-100 hover:bg-emerald-500/30">Crear</button>
                    </div>
                </form>
            <?php endif; ?>

            <form id="multi-download-form" method="post" action="action.php" class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                <input type="hidden" name="action" value="multizip">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                <label class="block text-xs text-slate-400 mb-2">Descarga multiple</label>
                <button class="rounded-lg border border-fuchsia-500/70 bg-fuchsia-500/15 px-3 py-1.5 text-xs text-fuchsia-100 hover:bg-fuchsia-500/30">Descargar seleccion</button>
            </form>
        </div>

        <!-- Previsualizacion arriba si hay -->
        <?php if ($previewFilePath && $previewType): ?>
            <div class="px-4 sm:px-6 pt-4">
                <div class="rounded-xl border border-sky-500/40 bg-sky-500/10 p-3 sm:p-4 mb-3 transition-all duration-200">
                    <div class="flex items-center justify-between mb-2 gap-2">
                        <h2 class="text-sm sm:text-base font-semibold text-sky-100">
                            Previsualizando: <?= h(basename($previewFilePath)) ?>
                        </h2>
                        <a href="<?= h(dirUrl($currentRel, ['q' => $q, 'page' => $page])) ?>"
                           class="text-xs text-slate-300 hover:text-sky-200 transition">
                            Cerrar
                        </a>
                    </div>
                    <div class="bg-slate-950/70 rounded-lg p-2 sm:p-3 border border-slate-800">
                        <?php if ($previewType === 'image'): ?>
                            <img src="<?= h(previewUrl($previewRel)) ?>" alt="" class="max-h-96 w-full object-contain rounded-lg transition-transform duration-200 hover:scale-[1.01]" />
                        <?php elseif ($previewType === 'video'): ?>
                            <video src="<?= h(previewUrl($previewRel)) ?>" controls class="w-full max-h-96 rounded-lg"></video>
                        <?php elseif ($previewType === 'audio'): ?>
                            <audio src="<?= h(previewUrl($previewRel)) ?>" controls class="w-full"></audio>
                        <?php elseif ($previewType === 'doc' && strtolower(pathinfo($previewFilePath, PATHINFO_EXTENSION)) === 'pdf'): ?>
                            <iframe src="<?= h(previewUrl($previewRel)) ?>" class="w-full h-96 rounded-lg bg-white"></iframe>
                        <?php else: ?>
                            <p class="text-xs text-slate-300">
                                Tipo de archivo no soportado para vista directa. Puedes descargarlo.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Listado -->
        <div class="px-2 sm:px-4 pb-4 pt-1 sm:pt-2">
            <div class="mt-2 rounded-xl border border-slate-800 bg-slate-950/40 divide-y divide-slate-800">

                <?php if (empty($dirs) && empty($files)): ?>
                    <div class="px-4 py-6 text-sm text-slate-400">
                        Esta carpeta esta vacia.
                    </div>
                <?php endif; ?>

                <!-- Carpetas -->
                <?php foreach ($dirs as $dirName): ?>
                    <?php
                    $dirRel = trim($currentRel === '' ? $dirName : $currentRel . '/' . $dirName, '/');
                    ?>
                    <div class="group hover:bg-slate-900/80 transition-colors duration-150">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between px-3 sm:px-4 py-3">
                            <div class="flex items-center gap-3">
                                <input form="multi-download-form" type="checkbox" name="items[]" value="<?= h($dirRel) ?>" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-sky-500">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500/15 text-sky-300 border border-sky-500/40 group-hover:bg-sky-500/25 group-hover:scale-[1.03] transition-transform duration-150">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M4 6.75A1.75 1.75 0 0 1 5.75 5h4.086c.464 0 .909.184 1.237.512l1.414 1.414A1.75 1.75 0 0 0 14.73 7.5H18.25A1.75 1.75 0 0 1 20 9.25v8A1.75 1.75 0 0 1 18.25 19h-12A1.75 1.75 0 0 1 4 17.25v-10.5Z" />
                                    </svg>
                                </div>
                                <div>
                                    <a href="<?= h(dirUrl($dirRel, ['q' => $q])) ?>"
                                       class="text-sm sm:text-base font-medium text-slate-50 hover:text-sky-300 transition-colors">
                                        <?= h($dirName) ?>
                                    </a>
                                    <p class="text-[11px] sm:text-xs text-slate-400">
                                        Carpeta
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:mt-0 flex flex-wrap items-center gap-2 sm:gap-3 justify-between sm:justify-end">
                                <a href="<?= h(zipDirUrl($dirRel)) ?>"
                                   data-download
                                   data-filename="<?= h(safeDownloadFilename($dirName . '.zip')) ?>"
                                   class="inline-flex items-center gap-1.5 rounded-xl border border-sky-500/70 bg-sky-500/15 px-3 py-1.5 text-[11px] sm:text-xs font-medium text-sky-100 hover:bg-sky-500/30 hover:border-sky-400 transition-all duration-150">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 17v2.5A1.5 1.5 0 0 0 5.5 21h13A1.5 1.5 0 0 0 20 19.5V17"/>
                                        <path d="M12 3v13m0 0l-4-4m4 4l4-4"/>
                                    </svg>
                                    Descargar carpeta
                                </a>
                                <?php if (canDo('rename')): ?>
                                    <form method="post" action="action.php" class="flex items-center gap-1">
                                        <input type="hidden" name="action" value="rename">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                                        <input type="hidden" name="old" value="<?= h($dirRel) ?>">
                                        <input name="new" placeholder="Nuevo nombre" class="w-28 rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-[11px] text-slate-100">
                                        <button class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-[11px] text-slate-300 hover:text-sky-200">Renombrar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (canDo('delete')): ?>
                                    <form method="post" action="action.php" onsubmit="return confirm('Borrar esta carpeta y todo su contenido?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                                        <input type="hidden" name="t" value="<?= h($dirRel) ?>">
                                        <button class="rounded-lg border border-red-500/60 bg-red-500/10 px-2 py-1 text-[11px] text-red-100 hover:bg-red-500/20">Borrar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Archivos -->
                <?php foreach ($filesPage as $fileName): ?>
                    <?php
                    $filePath = $currentDir . DIRECTORY_SEPARATOR . $fileName;
                    $fileSize = filesize($filePath);
                    $fileRel  = trim($currentRel === '' ? $fileName : $currentRel . '/' . $fileName, '/');
                    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $type     = classifyFileType($ext);
                    ?>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between px-3 sm:px-4 py-3 hover:bg-slate-900/80 transition-colors duration-150">
                        <div class="flex items-center gap-3">
                            <input form="multi-download-form" type="checkbox" name="items[]" value="<?= h($fileRel) ?>" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-sky-500">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-700
                            <?php
        $clsExtra = ' bg-slate-800/90 text-slate-200';

        if ($type === 'image') {
            $clsExtra = ' bg-emerald-500/10 text-emerald-300';
        } elseif ($type === 'video') {
            $clsExtra = ' bg-purple-500/10 text-purple-300';
        } elseif ($type === 'audio') {
            $clsExtra = ' bg-pink-500/10 text-pink-300';
        } elseif ($type === 'archive') {
            $clsExtra = ' bg-amber-500/10 text-amber-300';
        } elseif ($type === 'doc') {
            $clsExtra = ' bg-sky-500/10 text-sky-300';
        } elseif ($type === 'code') {
            $clsExtra = ' bg-fuchsia-500/10 text-fuchsia-300';
        }

        echo $clsExtra;
    ?> transition-transform duration-150 hover:scale-[1.03]">
                                <?= fileTypeIconSvg($type) ?>
                            </div>
                            <div>
                                <p class="text-sm sm:text-base font-medium text-slate-50 break-all">
                                    <?= h($fileName) ?>
                                </p>
                                <p class="text-[11px] sm:text-xs text-slate-400">
                                    <?= h(strtoupper($ext ?: 'FILE')) ?> - <?= h(humanFilesize($fileSize)) ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-2 sm:mt-0 flex flex-wrap items-center justify-between sm:justify-end gap-2 sm:gap-3">
                            <?php if (in_array($type, ['image','video','audio']) || ($type === 'doc' && $ext === 'pdf')): ?>
                                <a href="<?= h(dirUrl($currentRel, ['q' => $q, 'page' => $page, 'preview' => $fileRel])) ?>"
                                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-600 bg-slate-800/70 px-3 py-1.5 text-[11px] sm:text-xs text-slate-100 hover:bg-slate-700 hover:border-slate-400 transition-all duration-150">
                                    Ver
                                </a>
                            <?php endif; ?>

                            <a href="<?= h(dirUrl($currentRel, ['q' => $q, 'page' => $page, 'info' => $fileRel])) ?>"
                               class="inline-flex items-center gap-1 rounded-xl border border-slate-700 bg-slate-900/80 px-2 py-1 text-[10px] sm:text-[11px] text-slate-300 hover:bg-slate-800 hover:border-sky-500/70 hover:text-sky-200 transition-all duration-150">
                                Info friki
                            </a>

                            <a href="<?= h(dlUrl($fileRel)) ?>"
                               data-download
                               data-filename="<?= h(safeDownloadFilename($fileName)) ?>"
                               class="inline-flex items-center gap-1.5 rounded-xl border border-sky-500/70 bg-sky-500/15 px-3 py-1.5 text-[11px] sm:text-xs font-medium text-sky-100 hover:bg-sky-500/30 hover:border-sky-400 transition-all duration-150">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 17v2.5A1.5 1.5 0 0 0 5.5 21h13A1.5 1.5 0 0 0 20 19.5V17"/>
                                    <path d="M12 3v13m0 0l-4-4m4 4l4-4"/>
                                </svg>
                                Descargar
                            </a>

                            <?php if (canDo('share')): ?>
                                <form method="post" action="action.php" class="flex items-center gap-1">
                                    <input type="hidden" name="action" value="share">
                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                                    <input type="hidden" name="t" value="<?= h($fileRel) ?>">
                                    <input name="ttl_hours" type="number" min="1" max="720" value="<?= DEFAULT_SHARE_TTL_HOURS ?>" class="w-14 rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-[11px] text-slate-100" title="Horas">
                                    <input name="password" type="password" placeholder="Clave opc." class="w-24 rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-[11px] text-slate-100">
                                    <button class="rounded-lg border border-fuchsia-500/60 bg-fuchsia-500/10 px-2 py-1 text-[11px] text-fuchsia-100 hover:bg-fuchsia-500/20">Link</button>
                                </form>
                            <?php endif; ?>

                            <?php if (canDo('rename')): ?>
                                <form method="post" action="action.php" class="flex items-center gap-1">
                                    <input type="hidden" name="action" value="rename">
                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                                    <input type="hidden" name="old" value="<?= h($fileRel) ?>">
                                    <input name="new" placeholder="Nuevo nombre" class="w-28 rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-[11px] text-slate-100">
                                    <button class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-[11px] text-slate-300 hover:text-sky-200">Renombrar</button>
                                </form>
                            <?php endif; ?>

                            <?php if (canDo('delete')): ?>
                                <form method="post" action="action.php" onsubmit="return confirm('Borrar este archivo?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="d" value="<?= h($currentRel) ?>">
                                    <input type="hidden" name="t" value="<?= h($fileRel) ?>">
                                    <button class="rounded-lg border border-red-500/60 bg-red-500/10 px-2 py-1 text-[11px] text-red-100 hover:bg-red-500/20">Borrar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- Paginacion -->
            <?php if ($pagination['pages'] > 1): ?>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-400 px-1">
                    <div>
                        Pagina <?= $pagination['page'] ?> de <?= $pagination['pages'] ?> -
                        <?= $pagination['total'] ?> archivos
                    </div>
                    <div class="flex items-center gap-1">
                        <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                            <a href="<?= h(dirUrl($currentRel, ['q' => $q, 'page' => $i])) ?>"
                               class="px-2 py-1 rounded-lg border
                               <?= $i === $pagination['page']
                                   ? 'border-sky-500 bg-sky-500/20 text-sky-100'
                                   : 'border-slate-700 hover:border-sky-500/60 hover:bg-slate-800'; ?>
                               transition-all duration-150">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Panel info friki y estadisticas -->
    <section class="grid gap-4 lg:grid-cols-2">
        <?php if ($infoMeta): ?>
            <div class="rounded-2xl border border-fuchsia-600/50 bg-fuchsia-600/10 backdrop-blur-xl p-4 shadow-[0_0_40px_rgba(134,25,143,0.45)] transition-all duration-200">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-sm font-semibold text-fuchsia-100">
                        Info friki del archivo
                    </h2>
                    <a href="<?= h(dirUrl($currentRel, ['q' => $q, 'page' => $page])) ?>"
                       class="text-[11px] text-fuchsia-200/80 hover:text-white transition">
                        Cerrar
                    </a>
                </div>
                <div class="text-xs text-fuchsia-50 space-y-1.5">
                    <p><span class="font-semibold">Nombre:</span> <?= h($infoMeta['name']) ?></p>
                    <p><span class="font-semibold">Ruta:</span> <span class="font-mono"><?= h($infoMeta['rel']) ?></span></p>
                    <p><span class="font-semibold">Tamano:</span> <?= h(humanFilesize($infoMeta['size'])) ?> (<?= number_format($infoMeta['size']) ?> bytes)</p>
                    <p><span class="font-semibold">Modificado:</span> <?= date('Y-m-d H:i:s', $infoMeta['mtime']) ?></p>
                    <p><span class="font-semibold">Permisos:</span> <?= h($infoMeta['perms']) ?></p>
                    <p><span class="font-semibold">MIME:</span> <?= h($infoMeta['mime']) ?></p>
                    <?php if ($infoMeta['md5']): ?>
                        <p><span class="font-semibold">MD5:</span> <span class="font-mono break-all"><?= h($infoMeta['md5']) ?></span></p>
                    <?php endif; ?>
                    <?php if ($infoMeta['sha1']): ?>
                        <p><span class="font-semibold">SHA1:</span> <span class="font-mono break-all"><?= h($infoMeta['sha1']) ?></span></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 text-xs text-slate-400">
                Selecciona "Info friki" en un archivo para ver detalles tecnicos (hashes, permisos, MIME, etc.).
            </div>
        <?php endif; ?>

        <?php
        $diskTotal = $stats['disk']['total'];
        $diskFree  = $stats['disk']['free'];
        $diskUsed  = max(0, $diskTotal - $diskFree);
        $diskPct   = $diskTotal > 0 ? ($diskUsed / $diskTotal) * 100 : 0;
        ?>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 text-xs text-slate-200 space-y-2">
            <h2 class="text-sm font-semibold text-sky-100 mb-1">
                Estadisticas del servidor
            </h2>
            <p>
                Descargas registradas: <span class="font-semibold"><?= $stats['total_downloads'] ?></span> -
                Transferido: <span class="font-semibold"><?= humanFilesize($stats['total_bytes']) ?></span>
            </p>
            <p>
                Disco (<?= humanFilesize($diskTotal) ?>):
                <span class="font-semibold"><?= number_format($diskPct, 1) ?>%</span> usado
            </p>
            <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                <div class="h-2 rounded-full bg-sky-500 transition-all duration-300" style="width: <?= min(100, max(0, $diskPct)) ?>%;"></div>
            </div>
            <?php if ($stats['first'] && $stats['last']): ?>
                <p class="text-[11px] text-slate-400">
                    Primer registro: <?= h($stats['first']) ?> - Ultimo: <?= h($stats['last']) ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($stats['per_file'])): ?>
                <div class="mt-2">
                    <p class="text-[11px] text-slate-400 mb-1">Top archivos descargados:</p>
                    <ul class="space-y-1 max-h-24 overflow-y-auto pr-1">
                        <?php $i = 0; foreach ($stats['per_file'] as $path => $s): $i++; if ($i > 5) break; ?>
                            <li class="flex justify-between gap-2">
                                <span class="truncate max-w-[70%]" title="<?= h($path) ?>"><?= h($path) ?></span>
                                <span class="text-slate-300"><?= $s['count'] ?>x</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
