<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/shares.php';
require_once __DIR__ . '/lib/stats.php';
require_once __DIR__ . '/lib/errors.php';

startSecureSession();
sendSecurityHeaders();

$token = (string)($_GET['s'] ?? $_POST['s'] ?? '');
$share = getValidShare($DATA_DIR, $token);
$error = '';

if (!$share) {
    renderErrorPage(410, 'Este enlace compartido no existe o ya expiro.', 'index.php');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    if (verifySharePassword($share, $password)) {
        $_SESSION['share_ok'][$token] = true;
        logAudit($DATA_DIR, 'share_password_ok', $share['path'], ['token' => $token]);
        header('Location: action.php?' . http_build_query(['action' => 'sharedownload', 's' => $token]));
        exit;
    }
    $error = 'Clave incorrecta.';
} elseif (!shareNeedsPassword($share)) {
    $_SESSION['share_ok'][$token] = true;
    header('Location: action.php?' . http_build_query(['action' => 'sharedownload', 's' => $token]));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?= h($APP_TITLE) ?> - Enlace compartido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4">
    <main class="w-full max-w-sm rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-2xl">
        <h1 class="text-xl font-semibold text-white mb-1">Archivo compartido</h1>
        <?php if ($share): ?>
            <p class="text-sm text-slate-400 mb-5"><?= h(basename($share['path'])) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-3 rounded-xl border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-100">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($share && shareNeedsPassword($share)): ?>
            <form method="post" class="space-y-3">
                <input type="hidden" name="s" value="<?= h($token) ?>">
                <label class="block text-sm">
                    <span class="text-slate-300">Clave del enlace</span>
                    <input name="password" type="password" required class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </label>
                <button class="w-full rounded-xl border border-sky-500/70 bg-sky-500/20 px-4 py-2 text-sm font-medium text-sky-100 hover:bg-sky-500/30">
                    Descargar
                </button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
