# CRM Leads MVP

Reglas globales de trabajo (TDD, SOLID, delegación, Conventional Commits): ver `~/.claude/CLAUDE.md`. Aplican íntegramente a este proyecto.

## Stack
Laravel 13, PHP 8.4+, PostgreSQL (BDs `crm` y `crm_test`, usuario `diyer`), Pest 4, Larastan nivel max, Pint, Livewire 4 (+ Alpine), Tailwind 4 (Vite), SortableJS.

## Comandos
- `php artisan test` / `composer test` — tests (Pest, contra pgsql `crm_test`)
- `./vendor/bin/pint --test` / `composer lint` — estilo (preset laravel + strict types)
- `./vendor/bin/phpstan analyse` / `composer analyse` — análisis estático (nivel max: app, database, routes)
- `composer check` — lint + analyse + test
- `composer test:coverage` — `pest --coverage --min=85` (requiere pcov/xdebug, aún no instalado)
- `npm run build` — compila assets con Vite

## Estructura de capas (`app/`)
- `Domain/<Contexto>/` — modelos, enums, value objects, contratos (interfaces de repositorio), eventos. Sin dependencias de `Illuminate\Http` ni de `Infrastructure`.
- `Application/<Contexto>/` — Actions (casos de uso) y DTOs.
- `Infrastructure/` — repositorios Eloquent, exportadores CSV, integraciones.
- `Http/` — controllers delgados (sin `DB` facade), Form Requests, componentes Livewire, middleware.
- **Lista blanca**: los únicos directorios de `app/` son `Domain`, `Application`, `Infrastructure`, `Http` y `Providers` (ni ficheros sueltos). Un namespace raíz nuevo (`App\Services`, `App\Actions`, `app/Models`, `app/Livewire`...) hace fallar `tests/Arch`; si hace falta uno, se decide en un ADR y se amplía `ArchRules::APP_DIRECTORIES`.
- Livewire: solo componentes de clase en `App\Http\Livewire` (`config/livewire.php`: `make_command.type = class`). No se usan SFC ni MFC (componentes en vistas), porque quedarían fuera de strict types, PHPStan y las reglas arch. La lista blanca de `app/` prohíbe `app/Livewire`. El layout de páginas completas es `layouts.app` (sin `::`: el namespace `layouts` no está registrado porque `component_namespaces` está vacío).
- Bindings interfaz→implementación en `App\Providers\DomainServiceProvider`.

## Convenciones
- `declare(strict_types=1);` en todo el código (lo exige `tests/Arch/ArchitectureTest.php`).
- Los tests de arquitectura viven en `tests/Arch`; los de feature usan `RefreshDatabase`.
- Sin secretos en el repo: `.env` no se versiona, mantener `.env.example`.
- Todos los modelos Eloquent viven en `app/Domain/<Contexto>/Models` (no existe `App\Models`). Los tests abortan si la BD no es `crm_test` (`tests/Support/TestDatabaseGuard.php`).

## Tests de arquitectura (`tests/Arch`)
- Las reglas se definen UNA vez en `tests/Support/ArchRules.php` (parametrizadas por namespace raíz); `ArchitectureTest.php` las aplica a `App` y `ArchCanaryTest.php` a fixtures infractores.
- **Canarios**: `tests/Fixtures/Arch/**` contiene clases que violan cada regla (una por dependencia prohibida). `ArchCanaryTest` exige que cada regla falle contra ellas, de modo que una regla no puede pasar en vacío mientras las capas están vacías ni perder una dependencia sin que falle un canario. Al añadir una dependencia prohibida, añade su fixture y su fila en el dataset (escrito a mano a propósito).
- **Nunca usar arrays como sujeto** de `not->toUse([...])`: Pest crea un `toUse` por dependencia y lo invierte, y cada uno recorre los sujetos abortando en el primero que no usa la dependencia; resultado: solo falla si TODOS los sujetos violan. Los arrays de dependencias sí se respetan (verificado), y `toBeFinal`/`toExtend`/`not->toExtend` con arrays también, aunque aquí se usa un sujeto por regla.
- Una regla que apunta a una capa vacía pasa en vacío; para dependencias dentro de `App\...` (p. ej. `App\Infrastructure`) Pest solo detecta el uso si la clase usada existe en esa capa.
- Capacidad de la regla de BD: `Http` y `Application` no pueden usar `Illuminate\Support\Facades\DB`, `DatabaseManager`, `ConnectionInterface` ni `Connection`.

### Limitaciones conocidas (no detectables estáticamente: revisar en code review)
- Alias global `DB` (`use DB;` / `\DB::`): Pest filtra por prefijo de namespace resuelto vía PSR-4 de Composer; `DB` no corresponde a ninguna capa, así que no se detecta (probado con un canario; tampoco da falsos positivos). Por eso no se añade a la regla.
- `app('db')`, `app(DatabaseManager::class)` resuelto por string, y `Model::query()` / `Model::where()` desde `Http` (acceso Eloquent directo sin pasar por repositorio).

## Soporte de tests (`tests/Support`) — reutilízalo, no dupliques
- Prohibido definir funciones o constantes globales en archivos de test (colisionan entre archivos y ramas). Los helpers van en clases de `tests/Support`.
- `Tests\Support\Accounts`: `admin($email)`, `user($email)`, `STRONG_PASSWORD` (única contraseña válida para `Password::defaults()`), `WRONG_PASSWORD`, `GENERIC_ERROR`, `attemptLogin($test, $email, $password, $ip)`.
- `Tests\Support\InMemoryUserRepository`: fake del contrato para tests unitarios de Actions.
- `Tests\Support\ConfigEnv::evaluate($file, $env)`: evalúa un fichero de `config/` con variables de entorno temporales.
- `Tests\Support\AuditLog::context($channel, $level, $message)`: recupera el contexto de un log espiado.
- `TestCase::withCsrfEnforced()` desactiva el bypass de CSRF de Laravel en tests y comprueba que `runningUnitTests()` es false; `TestCase::setUp` llama a `withoutVite()` (la suite no depende de `public/build`).
- `AUTH_TIMEBOX_DURATION=0` en `phpunit.xml`: sin él cada intento de login espera 500 ms.
- Reglas arch de Http (controllers sin `Illuminate\Database` ni modelos de Domain, `final` en controllers/middleware/requests, controllers extienden `Controller`) viven en `ArchRules` con sus canarios en `tests/Fixtures/Arch/Http`.
- Seguridad: `SecurityHeaders` es middleware global; el rate limiter `login` y la auditoría (`storage/logs/security.log`) están en `IdentityServiceProvider`. `php artisan cache:clear` desbloquea un login limitado.
