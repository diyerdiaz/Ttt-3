# CRM Leads MVP

Reglas globales de trabajo (TDD, SOLID, delegación, Conventional Commits): ver `~/.claude/CLAUDE.md`. Aplican íntegramente a este proyecto.

## Stack
Laravel 13, PHP 8.5, PostgreSQL (BDs `crm` y `crm_test`, usuario `diyer`), Pest 4, Larastan nivel 8, Pint, Livewire 3 (+ Alpine), Tailwind 4 (Vite), SortableJS.

## Comandos
- `php artisan test` / `composer test` — tests (Pest, contra pgsql `crm_test`)
- `./vendor/bin/pint --test` / `composer lint` — estilo (preset laravel + strict types)
- `./vendor/bin/phpstan analyse` / `composer analyse` — análisis estático (nivel 8, `app/`)
- `composer check` — lint + analyse + test
- `composer test:coverage` — `pest --coverage --min=85` (requiere pcov/xdebug, aún no instalado)
- `npm run build` — compila assets con Vite

## Estructura de capas (`app/`)
- `Domain/<Contexto>/` — modelos, enums, value objects, contratos (interfaces de repositorio), eventos. Sin dependencias de `Illuminate\Http` ni de `Infrastructure`.
- `Application/<Contexto>/` — Actions (casos de uso) y DTOs.
- `Infrastructure/` — repositorios Eloquent, exportadores CSV, integraciones.
- `Http/` — controllers delgados (sin `DB` facade), Form Requests, componentes Livewire, middleware.
- Bindings interfaz→implementación en `App\Providers\DomainServiceProvider`.

## Convenciones
- `declare(strict_types=1);` en todo el código (lo exige `tests/Arch/ArchitectureTest.php`).
- Los tests de arquitectura viven en `tests/Arch`; los de feature usan `RefreshDatabase`.
- Sin secretos en el repo: `.env` no se versiona, mantener `.env.example`.
