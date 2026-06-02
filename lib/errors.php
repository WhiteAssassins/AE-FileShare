<?php

function errorTitleForStatus(int $status): string
{
    return match ($status) {
        401 => 'Necesitas iniciar sesion',
        403 => 'Acceso denegado',
        404 => 'No encontrado',
        410 => 'Enlace expirado',
        default => 'Algo no salio bien',
    };
}

function renderErrorPage(int $status, string $message, string $actionHref = 'index.php', string $actionLabel = 'Volver al inicio'): never
{
    global $APP_TITLE;

    http_response_code($status);
    $title = errorTitleForStatus($status);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?= h($APP_TITLE ?? 'AE-FileShare') ?> - <?= h($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4">
    <main class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-2xl">
        <p class="mb-2 text-xs font-medium uppercase tracking-[0.18em] text-sky-300">Error <?= $status ?></p>
        <h1 class="mb-2 text-2xl font-semibold text-white"><?= h($title) ?></h1>
        <p class="mb-5 text-sm leading-6 text-slate-300"><?= h($message) ?></p>
        <a href="<?= h($actionHref) ?>" class="inline-flex rounded-xl border border-sky-500/70 bg-sky-500/20 px-4 py-2 text-sm font-medium text-sky-100 hover:bg-sky-500/30">
            <?= h($actionLabel) ?>
        </a>
    </main>
</body>
</html>
    <?php
    exit;
}
