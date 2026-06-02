# AE-FileShare

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-v0.1.0-blue)

Mini centro de archivos en PHP para compartir archivos desde una carpeta del servidor, con acceso opcional, subidas, descargas visuales con progreso, enlaces temporales y panel de administracion.

## Requisitos

- PHP 8.0 o superior
- Apache recomendado
- Extension `zip` de PHP para descargar carpetas o selecciones como `.zip`
- Permiso de escritura en `data/`

## Instalacion Paso A Paso

1. Sube el proyecto al servidor.
2. Copia `config.example.php` como `config.php`.
3. Asegurate de que existan las carpetas `files/` y `data/`.
4. Dale permiso de escritura a PHP sobre `data/`.
5. Entra al dominio, por ejemplo `https://file.aewhitedevs.com`.
6. Inicia sesion con el usuario inicial:

```text
admin / admin123
```

7. En el panel superior de administracion, cambia la clave de `admin`.
8. Si vas a usar invitados, cambia tambien la clave de `guest`.
9. Sube archivos a `files/` o desde la interfaz web.
10. Cuando todo este listo, decide si quieres dejarlo privado o abierto.

## Configuracion Desde La Web

Al entrar como `admin`, aparece un panel de configuracion donde puedes:

- Cambiar la clave de `admin`.
- Cambiar la clave de `guest`.
- Activar o desactivar el modo privado.

Si el modo privado esta activo, se necesita usuario y clave para navegar y descargar.

Si el modo privado esta desactivado, cualquiera puede navegar y descargar sin login. El admin puede seguir entrando desde `login.php` para administrar.

Los cambios hechos desde la web se guardan en:

```text
data/settings.json
```

Ese archivo no se sube a GitHub porque `data/` esta ignorado.

## Usuarios Iniciales

`config.example.php` incluye estos accesos solo para facilitar la primera entrada:

```text
admin / admin123
guest / guest123
```

Cambia esas claves desde la web antes de publicar el sitio.

## Configuracion Manual

Tambien puedes editar `config.php` directamente para cambiar:

- `$APP_TITLE`
- `$ROOT_DIR`
- `$DATA_DIR`
- `FILES_PER_PAGE`
- `PRIVATE_MODE`
- `MAX_UPLOAD_BYTES`
- `DEFAULT_SHARE_TTL_HOURS`
- `$USERS`
- `$BLOCKED_UPLOAD_EXTENSIONS`

Si existe `data/settings.json`, sus valores de usuarios y modo privado tienen prioridad sobre `config.php`.

## Uso

- Pon archivos dentro de `files/` o subelos desde la interfaz.
- Usa las casillas para seleccionar varios elementos y descargarlos como ZIP.
- Usa `Compartir` para crear enlaces temporales con clave opcional.
- Las subidas y descargas muestran progreso, velocidad y tamano transferido.

## Seguridad

- `config.php` es local y no debe subirse a GitHub.
- `files/` y `data/` estan ignorados por Git para no subir archivos privados ni logs.
- Los `.htaccess` bloquean acceso directo a `files/` y `data/`.
- Las descargas pasan por `action.php`.
- Las acciones sensibles usan CSRF.
- Las extensiones ejecutables quedan bloqueadas al subir.
- Si puedes, manten `files/` y `data/` fuera del document root en produccion.
