<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/settings.php';

startSecureSession();
sendSecurityHeaders();

$settings = readSettings($DATA_DIR, $USERS);
$USERS = $settings['users'];
$messages = takeFlash();

if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?= h($APP_TITLE) ?> - Acceso</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4">
    <main class="w-full max-w-sm rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-2xl">
        <h1 class="text-xl font-semibold text-white mb-1"><?= h($APP_TITLE) ?></h1>
        <p class="text-sm text-slate-400 mb-5">Acceso de administrador e invitados.</p>

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
