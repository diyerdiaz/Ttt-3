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
- Livewire: solo componentes de clase en `App\Http\Livewire` (`config/livewire.php`: `make_command.type = class`). No se usan SFC ni MFC (componentes en vistas), porque quedarían fuera de strict types, PHPStan y las reglas arch. Los tests arch prohíben `app/Livewire`.
- Bindings interfaz→implementación en `App\Providers\DomainServiceProvider`.

## Convenciones
- `declare(strict_types=1);` en todo el código (lo exige `tests/Arch/ArchitectureTest.php`).
- Los tests de arquitectura viven en `tests/Arch`; los de feature usan `RefreshDatabase`.
- Sin secretos en el repo: `.env` no se versiona, mantener `.env.example`.
- Todos los modelos Eloquent viven en `app/Domain/<Contexto>/Models` (no existe `App\Models`). Los tests abortan si la BD no es `crm_test` (`tests/Support/TestDatabaseGuard.php`).
