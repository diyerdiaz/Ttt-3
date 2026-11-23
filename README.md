# CRM Leads MVP

MVP de gestión de leads (Laravel + PostgreSQL). Ver CLAUDE.md.

## Puesta en marcha

Requisitos: PHP 8.4+, Composer, Node, PostgreSQL 16+.

```sh
createdb crm
createdb crm_test
composer setup
```

`composer setup` instala dependencias, crea `.env` desde `.env.example`, genera la clave, ejecuta `php artisan migrate --force` y compila los assets.

Configura en `.env` el usuario de PostgreSQL (`DB_USERNAME`, y `DB_PASSWORD` si procede); `.env.example` los deja vacíos.

> Aviso: `php artisan migrate` puede crear automáticamente la BD indicada en `DB_DATABASE` si no existe. Revisa siempre `DB_DATABASE` antes de migrar.

## Tests y calidad

```sh
composer check   # pint --test + phpstan + tests
```

Los tests corren siempre contra la BD `crm_test` (PostgreSQL). `phpunit.xml` fuerza `APP_ENV`, `DB_CONNECTION`, `DB_DATABASE` y `DB_URL`, y `tests/TestCase.php` aborta antes de migrar si la BD no es `crm_test`. `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` se pueden sobrescribir por entorno (CI, Docker).

## Administrador y acceso

No hay registro público ni restablecimiento de contraseña. Crea el administrador con:

```sh
php artisan crm:create-admin tu@email.com --name="Tu nombre"   # pide la contraseña (12-72 caracteres)
```

Alternativa solo para desarrollo: define `ADMIN_EMAIL` y `ADMIN_PASSWORD` en `.env` y ejecuta `php artisan db:seed` (el seeder falla si faltan y se niega a ejecutarse en producción).

El login tiene límites de intentos (5/min por email+IP, 20/min por IP y 50/hora por cuenta). Para desbloquear un acceso: `php artisan cache:clear`. Los eventos de autenticación se registran en `storage/logs/security.log` (emails como hash sha256, nunca contraseñas).

## Producción

Valores mínimos en el `.env` de producción:

```dotenv
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

Notas de seguridad:

- `SESSION_SECURE_COOKIE` es `true` por defecto cuando `APP_ENV=production`; si la app se sirve por HTTP, ponlo explícitamente a `false`.
- En producción las contraseñas nuevas se comprueban además contra HaveIBeenPwned (`Password::uncompromised()`).
- La cabecera HSTS solo se envía en producción y sobre HTTPS.
- Si la app va detrás de un proxy o balanceador, configura los proxies de confianza (`TrustProxies`) antes de depender de la IP del cliente: sin ello, el límite por IP y el registro de seguridad verán la IP del proxy y `isSecure()` no detectará HTTPS. No está configurado porque la app se usa en local.
- `php artisan optimize` y `composer install --no-dev --optimize-autoloader` como en `~/.claude/CLAUDE.md` §3.1.
