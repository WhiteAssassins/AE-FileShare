# AE-FileShare

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
5. Cambia los usuarios y hashes de `config.php` antes de publicar.

## Acceso

Usuarios de desarrollo incluidos:

- `admin` / `admin123`
- `guest` / `guest123`

Genera hashes nuevos con:

```bash
php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT), PHP_EOL;"
```

## Notas de seguridad

- Todo lo que pongas en `files/` sera accesible desde la app.
- `files/` y `data/` estan ignorados por Git para no subir archivos privados ni logs.
- Los `.htaccess` incluidos bloquean el acceso directo a `files/` y `data/`; las descargas pasan por `action.php`.
- La autenticacion privada viene activa por defecto con `PRIVATE_MODE`.
- Las acciones de subida, carpeta, renombrado, borrado, links y ZIP multiple usan CSRF.
- Las extensiones ejecutables quedan bloqueadas al subir.
- Si puedes, manten `files/` y `data/` fuera del document root en produccion.

## Configuracion

Edita `config.php` para cambiar:

- `$APP_TITLE`
- `$ROOT_DIR`
- `$DATA_DIR`
- `FILES_PER_PAGE`
- `PRIVATE_MODE`
- `$USERS`
- `$BLOCKED_UPLOAD_EXTENSIONS`
