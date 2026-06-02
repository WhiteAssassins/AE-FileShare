# AEWhite Devs FileHub

Mini file hub en PHP para compartir archivos desde una carpeta del servidor.

## Requisitos

- PHP 8.0 o superior
- Apache recomendado
- Extension `zip` de PHP para descargar carpetas como `.zip`

## Instalacion

1. Copia el proyecto al hosting.
2. Sube los archivos que quieras compartir dentro de `files/`.
3. Asegurate de que PHP pueda escribir en `data/` para registrar descargas.
4. Abre el dominio configurado, por ejemplo `https://file.aewhitedevs.com`.

## Notas de seguridad

- Todo lo que pongas en `files/` sera accesible desde la app.
- `files/` y `data/` estan ignorados por Git para no subir archivos privados ni logs.
- Los `.htaccess` incluidos bloquean el acceso directo a `files/` y `data/`; las descargas pasan por `action.php`.
- Si quieres usarlo con un grupo cerrado, agrega autenticacion antes de publicarlo ampliamente.

## Configuracion

Edita `config.php` para cambiar:

- `$APP_TITLE`
- `$ROOT_DIR`
- `$DATA_DIR`
- `FILES_PER_PAGE`
