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

## Producción

Valores mínimos en el `.env` de producción:

```dotenv
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```
