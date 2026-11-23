# CRM Leads MVP

CRM de leads sencillo para un único administrador. Laravel 13, PHP 8.4+, PostgreSQL, Livewire 4, Tailwind 4 y SortableJS.

## Qué es

- Acceso solo para el administrador (no hay registro público).
- Kanban con arrastrar y soltar para mover los leads entre etapas.
- Listado de leads con búsqueda.
- Ficha de cada lead con su historial de actividades.
- Exportación a CSV con filtro y protección contra inyección de fórmulas (una celda que una hoja de cálculo podría evaluar se neutraliza).
- Las horas se muestran en `America/Bogota` y se guardan en UTC.

## Requisitos

- PHP 8.4 o superior con las extensiones habituales de Laravel (`mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `ctype`, `fileinfo`, `tokenizer`, `xml`, `curl`).
- Composer 2.
- Node.js y npm (para compilar los assets con Vite).
- PostgreSQL 16 o superior.

## Puesta en marcha local

1. Clona el repositorio y entra en la carpeta.
2. Instala las dependencias:
   ```sh
   composer install
   npm ci
   ```
3. Crea el `.env` y la clave de la aplicación:
   ```sh
   cp .env.example .env
   php artisan key:generate
   ```
   En `.env` rellena `DB_USERNAME` (y `DB_PASSWORD` si hace falta); `.env.example` los deja vacíos.
4. Crea las bases de datos (la de test se usa en la suite):
   ```sh
   createdb crm
   createdb crm_test
   ```
5. Migra y compila los assets:
   ```sh
   php artisan migrate
   npm run build
   ```
   > `migrate` puede crear la BD indicada en `DB_DATABASE` si no existe: revísala antes de migrar.
6. Crea el administrador. El comando pide la contraseña, así que necesita una terminal interactiva; la contraseña debe tener 12 caracteres o más:
   ```sh
   php artisan crm:create-admin tu@email.com --name="Tu nombre"
   ```
7. Arranca el servidor y abre <http://localhost:8000>:
   ```sh
   php artisan serve
   ```

Atajo: `composer setup` hace instalación, `.env`, clave, migraciones y build de una vez (pero no crea las BD ni el admin). Para desarrollo con recarga de assets: `composer dev`.

## Variables de entorno relevantes

Ver `.env.example`. Nunca subas el `.env` al repositorio.

| Variable | Para qué sirve |
|---|---|
| `APP_DISPLAY_TIMEZONE` | Zona en la que se muestran y escriben las fechas (por defecto `America/Bogota`). Se guardan siempre en UTC. |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión a PostgreSQL. |
| `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_SECURE_COOKIE` | Sesión (en BD por defecto). `SESSION_SECURE_COOKIE` es `true` por defecto en producción. |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Solo desarrollo: los lee el seeder (`php artisan db:seed`), que falla si faltan y se niega a correr en producción. En producción usa `crm:create-admin`. |

## Tests y calidad

```sh
composer check     # lint + analyse + test
composer test      # tests (Pest)
composer lint      # Pint en modo comprobación
composer analyse   # Larastan, nivel max
```

- Los tests corren siempre contra la BD `crm_test`; una guardia aborta si se usa otra.
- **No ejecutes `./vendor/bin/pint` en modo escritura** (sin `--test`): reformatearía `tests/Fixtures/Arch`, que contiene clases infractoras a propósito, y rompería los tests canario de arquitectura. Usa `composer lint`.
- E2E: `npm run test:e2e` con Playwright y la BD `crm_e2e`. Se está añadiendo en paralelo y puede no estar disponible todavía.

## Arquitectura en breve

Capas dentro de `app/`:

- `Domain/<Contexto>/`: modelos, enums, value objects, contratos de repositorio y eventos.
- `Application/<Contexto>/`: Actions (casos de uso) y DTOs.
- `Infrastructure/`: repositorios Eloquent, exportador CSV, comandos de consola.
- `Http/`: controllers delgados, Form Requests, componentes Livewire y middleware.

Las reglas de dependencias entre capas las comprueban los tests de `tests/Arch`. Detalle en [docs/design.md](docs/design.md) y en [CLAUDE.md](CLAUDE.md).

## Producción

Lista de comprobación:

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` (y `SESSION_ENCRYPT=true`), todo servido por HTTPS.
- [ ] Si hay proxy o balanceador, configurar los proxies de confianza (si no, el límite por IP y el log verán la IP del proxy). Hoy no está configurado.
- [ ] `composer install --no-dev --optimize-autoloader --classmap-authoritative`
- [ ] `php artisan optimize` y OPcache activo.
- [ ] `npm run build`
- [ ] Servir los assets con `Cache-Control: immutable` (llevan hash de Vite).
- [ ] Compresión gzip o brotli en el servidor web.
- [ ] Ejecutar un worker de colas (`php artisan queue:work`) si se usan listeners lentos (`QUEUE_CONNECTION=database`).
- [ ] Crear el admin con `php artisan crm:create-admin`; el seeder no corre en producción.

## Seguridad

- No hay registro público ni restablecimiento de contraseña.
- El login tiene límite de intentos (5/min por email+IP, 20/min por IP y 50/hora por cuenta).
- Los eventos de autenticación se registran en `storage/logs/security-*.log` (un fichero por día, 90 días por defecto, configurable con `SECURITY_LOG_DAYS`). Los emails se guardan como hash y nunca las contraseñas.
- Para desbloquear un login bloqueado: `php artisan cache:clear`.
- En producción las contraseñas nuevas se comprueban además contra HaveIBeenPwned.
- La cabecera HSTS solo se envía en producción y sobre HTTPS.
