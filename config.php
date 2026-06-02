<?php
// Titulo de la app
$APP_TITLE = 'AEWhite Devs FileHub';

// Carpeta raiz de archivos
$ROOT_DIR = __DIR__ . '/files';

// Carpeta para logs y datos
$DATA_DIR = __DIR__ . '/data';

// Archivos por pagina
define('FILES_PER_PAGE', 30);

if (!is_dir($ROOT_DIR)) {
    @mkdir($ROOT_DIR, 0775, true);
}

if (!is_dir($DATA_DIR)) {
    @mkdir($DATA_DIR, 0775, true);
}
