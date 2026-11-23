# Diseño del MVP CRM de leads

Base: `develop` tras `feat/project-setup` (Laravel 13, PHP 8.5, Livewire 4 solo componentes de clase en `App\Http\Livewire`, Pest 4, Larastan/PHPStan level max). `User` ya vive en `App\Domain\Identity\Models\User` (hecho en project-setup; admin-auth NO debe moverlo).
Decisión transversal: los modelos Eloquent viven en `Domain/*/Models` (pragmatismo); las Actions solo dependen de **contratos**, y las queries viven en `Infrastructure/Persistence`.

## 1. Modelo de datos (PostgreSQL 18)

**users**: `id bigint identity PK`, `name varchar(120) NOT NULL`, `email varchar(254) NOT NULL`, `password varchar(255) NOT NULL`, `is_admin boolean NOT NULL DEFAULT false`, `remember_token varchar(100) NULL`, `created_at/updated_at timestamptz`. Índice `UNIQUE (lower(email))`. Se mantienen `sessions` (driver database) y `cache` de Laravel.

**leads**
| columna | tipo | notas |
|---|---|---|
| id | bigint generated always as identity | PK |
| name | varchar(150) NOT NULL | `CHECK (btrim(name) <> '')` |
| email | varchar(254) NULL | |
| phone | varchar(32) NULL | |
| company | varchar(150) NULL | |
| notes | text NULL | |
| status | varchar(20) NOT NULL DEFAULT 'new' | `CHECK (status IN ('new','contacted','qualified','proposal','won','lost'))` |
| position | integer NOT NULL | `CHECK (position >= 0)` |
| created_at, updated_at | timestamptz | |

Índices: `CONSTRAINT leads_status_position_unique UNIQUE (status, position) DEFERRABLE INITIALLY DEFERRED` (también sirve para pintar el tablero ordenado: `WHERE status = ? ORDER BY position`); `INDEX (lower(email))` para la búsqueda/duplicados. Se exporta con keyset sobre la PK, así que no hace falta otro índice.

**activities**: `id bigint identity PK`; `lead_id bigint NOT NULL REFERENCES leads(id) ON DELETE CASCADE`; `type varchar(20) NULL CHECK (type IN ('call','email','meeting','note'))`; `description text NOT NULL CHECK (btrim(description) <> '')`; `occurred_at timestamptz NOT NULL`; `created_at, updated_at timestamptz`. Índice `(lead_id, occurred_at DESC, id DESC)`: sirve para la FK y para el timeline.
Que el borrado vaya en cascada se justifica porque una actividad no tiene sentido sin su lead. El borrado de leads queda fuera del alcance del MVP, pero la FK ya está preparada.

**¿Estados como enum o como tabla?** Elijo un **enum PHP backed (string) + CHECK constraint**, con estas razones:
- El conjunto de estados es fijo y lo decide el negocio, no el usuario. No hay requisito de que el admin edite estados. Por YAGNI, una tabla `lead_statuses` obligaría a añadir CRUD, seeds, joins y orden de columnas configurable.
- Las reglas (etiqueta, orden de columnas, transiciones, si un estado es terminal) se tipan en el enum, Larastan las verifica y se testean de forma unitaria sin BD.
- El CHECK protege la integridad frente a escrituras fuera de la app.
- Añadir un estado supone una migración que recrea el CHECK, algo trivial. Se haría la migración a tabla solo si apareciera "estados configurables".

No uso el tipo `ENUM` nativo de PG porque su `ALTER TYPE` es más rígido (no deja quitar valores) y Laravel lo soporta peor.

**Orden del kanban sin colisiones.** Uso posiciones densas por columna (0..n-1) con tres garantías:
1. **Serialización**: toda escritura que toque `status/position` (crear lead, mover, cambiar estado desde el formulario) toma `pg_advisory_xact_lock(<clave constante 'leads.board'>)` dentro de la transacción. Al haber un único lock global no pueden darse deadlocks, y la contención es despreciable con un solo admin.
2. **Reordenación atómica**: el repositorio reescribe la columna destino en un único `UPDATE leads SET status = $s, position = t.ord - 1 FROM unnest($ids::bigint[]) WITH ORDINALITY t(id, ord) WHERE leads.id = t.id`.
3. **Red de seguridad**: la UNIQUE diferida permite posiciones intermedias duplicadas dentro de la sentencia y comprueba al hacer COMMIT. En la columna origen queda un hueco, que no colisiona y no necesita compactarse.

Para crear un lead: `position = COALESCE(MAX(position), -1) + 1` en su estado, bajo el mismo lock.
Escala: la reordenación es O(n) por columna, adecuada para cientos de leads. Si alguna columna llegara a miles, se pasaría a rank fraccional o LexoRank (está documentado como ADR futuro).

## 2. Contextos

### Shared (`App\Domain\Shared`)
- `Contracts\TransactionManager`: `run(Closure $fn): mixed`. Implementación `Infrastructure\Persistence\DatabaseTransactionManager` con `DB::transaction`.
- `ValueObjects\EmailAddress`: inmutable, `fromString(string): self` normaliza (trim + lowercase) y valida; `value(): string`.

### Identity
- Models: `User`, con `isAdmin(): bool` y cast `is_admin => bool`.
- Contracts: `UserRepository`: `findByEmail(EmailAddress $email): ?User`; `createAdmin(string $name, EmailAddress $email, string $passwordHash): User`. Desviación respecto al diseño inicial (`create(NewUserData)`): Domain no puede depender de `Application` (regla arch), así que el contrato recibe el hash ya calculado en vez del DTO.
- Application: `Actions\CreateAdminUser`: `__invoke(NewUserData $data): User`. Aplica la política de contraseña (`Password::defaults()`: 12-72 caracteres, y `uncompromised()` solo en producción; lanza `WeakPassword`), hashea con `Hasher` (inyectado) y lanza `EmailAlreadyTaken` si el email existe (también si el repositorio recibe una violación de unicidad). DTO `NewUserData(string $name, EmailAddress $email, string $plainPassword)`.
- `EmailAddress::normalize()` (trim + minúsculas) es la única fuente de normalización: VO, mutador de `User`, login y rate limiter.
- Infra: `EloquentUserRepository`. Consola: `Infrastructure\Console\CreateAdminCommand` (`crm:create-admin`), registrada con `withCommands` en `bootstrap/app.php`; vive en Infrastructure y no en `app/Console` porque la lista blanca de `app/` no admite más raíces. Seeder `AdminUserSeeder` (solo desarrollo; falla en producción).
- Http: `LoginController`, `LogoutController`, `LoginRequest`, middleware `EnsureUserIsAdmin` y `SecurityHeaders` (global), `RateLimiting\LoginRateLimiter`, `Security\SecurityAuditLogger` (canal de log `security`).
- Providers: `IdentityServiceProvider` agrupa el gate `access-admin` (`Domain\Identity\Abilities`), `Password::defaults()`, el limiter `login` y los listeners de auditoría, para no tocar `AppServiceProvider` desde cada rama.

### Leads
- Models: `Lead` (casts `status => LeadStatus`; relación `activities()`).
- Enums: `LeadStatus: string` con los casos `New, Contacted, Qualified, Proposal, Won, Lost` y los métodos `label(): string`, `isTerminal(): bool`, `canTransitionTo(self $to): bool`, `static boardOrder(): list<self>`.
- Regla de transición del MVP: **libre entre cualquier par de estados** (incluidos `Won`/`Lost`, en ambos sentidos). `canTransitionTo` devuelve `true` para todo cambio de estado; mover dentro de la misma columna es reorden y siempre válido. Al estar la regla en un único método del enum, endurecerla es un cambio local.
- VOs: `EmailAddress` (Shared); `PhoneNumber` (trim, solo `+ 0-9 espacio - ( )`, de 6 a 32 caracteres).
- Contracts (segregados por ISP, lo que además evita conflictos de merge entre ramas paralelas):
  - `LeadRepository`: `find(int $id): ?Lead`; `findOrFail(int $id): Lead`; `create(LeadData $d, int $position): Lead`; `update(Lead $l, LeadData $d): Lead`; `nextPositionIn(LeadStatus $s): int`; `search(?string $term, int $perPage): LengthAwarePaginator`.
  - `LeadBoardRepository`: `lockBoard(): void`; `columns(): array<value-of<LeadStatus>, list<Lead>>`; `findForUpdate(int $id): Lead`; `idsInColumn(LeadStatus $s): list<int>`; `placeColumn(LeadStatus $s, list<int> $orderedIds): void`.
  - `LeadExportSource`: `stream(LeadExportFilter $f): iterable<Lead>` (implementado con `lazyById(500)`, sin cargar todo en memoria).
  - `LeadExporter` (Strategy): `contentType(): string`; `extension(): string`; `write(iterable<Lead> $leads, resource $out): void`.
- Actions (`App\Application\Leads\Actions`, final, `__invoke`):
  - `CreateLead(LeadData): Lead`: lock, `nextPositionIn`, `create`, y después `LeadCreated`.
  - `UpdateLead(int $id, LeadData): Lead`: si cambia el estado, valida la transición y añade el lead al final de la nueva columna bajo el lock. Emite `LeadUpdated` y, si cambió el estado, `LeadStatusChanged`.
  - `MoveLead(MoveLeadData): void`: ver el flujo en la sección 4.
  - `ExportLeads(LeadExportFilter, LeadExporter, resource $out): void`.
- DTOs: `LeadData(string $name, ?EmailAddress $email, ?PhoneNumber $phone, ?string $company, ?string $notes, LeadStatus $status)`; `MoveLeadData(int $leadId, LeadStatus $to, list<int> $orderedIds)`; `LeadExportFilter(list<LeadStatus> $statuses)`, donde una lista vacía significa todos.
- Excepciones de dominio: `InvalidStatusTransition`, `StaleBoardState`.
- Events: `LeadCreated(int $leadId)`, `LeadUpdated(int $leadId)` y `LeadStatusChanged(int $leadId, LeadStatus $from, LeadStatus $to)`. Todos implementan `ShouldDispatchAfterCommit`.

### Activities
- Models: `Activity` (casts `type => ?ActivityType`, `occurred_at => immutable_datetime`). Enums: `ActivityType: string` con `Call, Email, Meeting, Note` y `label()`.
- Contracts: `ActivityRepository`: `create(int $leadId, ActivityData $d): Activity`; `forLead(int $leadId, int $perPage): LengthAwarePaginator` (ordenado por `occurred_at DESC, id DESC`).
- Actions: `LogActivity(int $leadId, ActivityData): Activity`, que emite `ActivityLogged(int $activityId, int $leadId)`.
- DTO: `ActivityData(string $description, CarbonImmutable $occurredAt, ?ActivityType $type)`.
- Listener opcional (Observer): `RecordStatusChangeActivity` escucha `LeadStatusChanged` y registra una actividad `note`. Es el único acoplamiento entre contextos y va por evento. Si se incluye, lo hace `feat/lead-activities`.

## 3. Patrones y SOLID
| Patrón | Dónde | Principio |
|---|---|---|
| Repository + interfaz | `Domain/*/Contracts` → `Infrastructure/Persistence/Eloquent*` (bindings en `DomainServiceProvider`) | DIP (las Actions dependen de abstracciones); ISP (repositorio CRUD, de tablero y de exportación separados) |
| Action / caso de uso | `Application/*/Actions` | SRP (un caso de uso por clase); los controllers y Livewire quedan delgados |
| DTO / Value Object | `Application/*/DTOs`, `Domain/*/ValueObjects` | SRP y encapsulación: los datos ya validados y tipados cruzan las capas sin arrays ni `Request` |
| Strategy | `LeadExporter` → `Infrastructure/Export/CsvLeadExporter`; se elige con `LeadExporterFactory::for(string $format)` | OCP: añadir XLSX o JSON es una clase nueva, sin tocar `ExportLeads`; LSP |
| Policy / Gate | `LeadPolicy`, `ActivityPolicy`, gate `access-admin` | SRP: la autorización queda fuera de controllers y Actions |
| Observer (Events) | `LeadStatusChanged` → listener de Activities | OCP y bajo acoplamiento entre contextos |
| Template en el enum | `LeadStatus::canTransitionTo` | Las reglas de dominio están en un único punto (DRY) |

Tests de arquitectura que hay que añadir en `tests/Arch`: `App\Application` y `App\Infrastructure` son `final`; `App\Http` no usa `App\Infrastructure` ni `Illuminate\Support\Facades\DB`; `App\Application` no usa `Illuminate\Http`; `App\Domain` no usa `App\Application`.

## 4. Contrato HTTP y Livewire
Las rutas se reparten en un archivo por feature, `routes/web/{auth,leads,pipeline,activities,export}.php`, que `routes/web.php` carga con un glob. Así se evitan conflictos entre ramas paralelas.

| Método | URI | Handler | Middleware |
|---|---|---|---|
| GET | /login | `LoginController@create` | guest |
| POST | /login | `LoginController@store` (`LoginRequest`) | guest, `throttle:login` (5/min email+IP, 20/min IP, 50/h cuenta; al superar un límite se responde con un 302 de vuelta al formulario con el error genérico "Demasiados intentos", no con un 429) |
| POST | /logout | `LogoutController` | auth |
| GET | / | `dashboard` (vista placeholder; las ramas siguientes la sustituyen o redirigen a `pipeline`) | auth, admin |
| GET | /leads | Livewire `Leads\LeadIndex` (búsqueda y paginación) | auth, admin |
| GET | /leads/create | Livewire `Leads\LeadForm` | auth, admin |
| GET | /leads/{lead}/edit | Livewire `Leads\LeadForm` (`whereNumber`) | auth, admin |
| GET | /leads/{lead} | Livewire `Leads\LeadShow`, que embebe `Activities\ActivityTimeline` | auth, admin |
| GET | /pipeline | Livewire `Pipeline\PipelineBoard` | auth, admin |
| GET | /leads/export?status[]=new&status[]=won | `ExportLeadsController` (invocable, `ExportLeadsRequest`) | auth, admin, `throttle:10,1` |

La validación en el borde se hace así: `LeadForm` usa un Livewire Form object (`Http/Livewire/Forms/LeadFormData`) con reglas de nombre, email y teléfono, y `toDto(): LeadData`. `ActivityTimeline` valida `description` (requerida, máximo 5000), `occurred_at` (fecha, como mucho `now()+1 day`) y `type` (`Rule::enum`, nullable). `ExportLeadsRequest` valida `status` (array, como mucho 6 elementos) y `status.*` (`Rule::enum(LeadStatus::class)`, distinct).

**Flujo de drag & drop**
1. **Blade**: cada columna es `<ul data-status="qualified" wire:key="col-qualified" x-init="Sortable.create($el,{group:'pipeline', animation:150, onEnd: e => $wire.moveLead(+e.item.dataset.id, e.to.dataset.status, [...e.to.children].map(li => +li.dataset.id))})">`, y cada `<li wire:key="lead-{id}" data-id>`. SortableJS se importa en `resources/js/app.js`.
2. **`PipelineBoard::moveLead(int $leadId, string $toStatus, array $orderedIds)`**:
   - `authorize('move', $lead)`;
   - valida con `LeadStatus::tryFrom`, ids enteros y distintos, como mucho 1000, y que `$leadId` esté entre ellos;
   - construye `MoveLeadData` e invoca `MoveLead`.
3. **`MoveLead`** ejecuta dentro de `TransactionManager::run`:
   1. `lockBoard()`;
   2. `findForUpdate($id)`;
   3. si `from ≠ to` y `!from->canTransitionTo(to)`, lanza `InvalidStatusTransition`;
   4. si `set(orderedIds) ≠ set(idsInColumn(to)) ∪ {id}`, lanza `StaleBoardState` (otra pestaña cambió el tablero);
   5. `placeColumn(to, orderedIds)`;
   6. COMMIT, en el que se valida la UNIQUE diferida, y después se emite `LeadStatusChanged` si cambió el estado.
4. **Respuesta**: el componente se vuelve a renderizar desde BD, que es la fuente de verdad; con `wire:key` el morph reconcilia el DOM. Si salta `InvalidStatusTransition` o `StaleBoardState`, se captura, se muestra un toast de error y el re-render devuelve la tarjeta a su sitio. En los demás casos de error, 403 o 422.

## 5. Seguridad
- **Sin registro**: no hay rutas de registro ni de reset de contraseña.
  - El admin se crea con `php artisan crm:create-admin {email} {--name=}`, que pide la contraseña con `secret()` (mínimo 12, `Password::defaults()`).
  - Alternativa: `AdminUserSeeder`, que lee `ADMIN_EMAIL`/`ADMIN_PASSWORD` del `.env` y, si faltan, falla en lugar de inventar credenciales. Las variables quedan documentadas en `.env.example`, sin valores.
- **CSRF**: middleware `web` por defecto. Login y logout son formularios con `@csrf`, y el logout es solo POST. Livewire firma y protege sus peticiones con CSRF.
- **Sesión**: en el login, `session()->regenerate()`. En el logout, `invalidate()` + `regenerateToken()`. Cookies `secure`, `http_only` y `same_site=lax`.
- **Rate limit**: `RateLimiter::for('login')` con `Limit::perMinute(5)->by(lower(email).'|'.ip)` y `Limit::perMinute(20)->by(ip)`. El `LoginRequest` limpia el contador en un login correcto. Se devuelve un error genérico ("Credenciales inválidas") para no permitir enumerar usuarios; al superar un límite, un 302 al formulario con "Demasiados intentos" (idéntico para cuentas existentes e inexistentes). La exportación lleva `throttle:10,1`.
- **Autorización** (defensa en profundidad):
  - el middleware `admin` (`EnsureUserIsAdmin` → `Gate::allows('access-admin')`, 403) protege todo el grupo;
  - las Policies (`viewAny`, `view`, `create`, `update`, `move`, `export`; `ActivityPolicy::create`) se comprueban también en cada acción Livewire, porque los métodos de Livewire son endpoints públicos;
  - las propiedades públicas sensibles de Livewire llevan `#[Locked]` (por ejemplo, `leadId`).
- **Inyección de fórmulas en CSV (OWASP)**: `Infrastructure/Export/CsvCellSanitizer::sanitize(?string): string`.
  - Si la celda empieza por `= + - @ \t \r` (o por `\n`, `\0`), se le antepone `'`. Se aplica a todas las celdas de texto, también a las que llevan espacios en blanco delante.
  - Las celdas se escriben con `fputcsv` (comillas y escape correctos), con BOM UTF-8 para Excel y con `Content-Disposition: attachment; filename="leads-YYYYmmdd-His.csv"` y `X-Content-Type-Options: nosniff`.
  - Streaming con `response()->streamDownload` sobre `php://output` y `lazyById`, con memoria constante.
- **XSS**: Blade escapa la salida con `{{ }}` y nunca se usa `{!! !!}` con datos de lead. Las notas se muestran con `whitespace-pre-line`, no como HTML.
- **Mass assignment**: no se usa `$fillable` con datos del request; los repositorios mapean desde los DTOs. `Model::shouldBeStrict()` está activo fuera de producción.

## 6. Plan de ramas (todas desde `develop`, cada una en su worktree)
```
feat/project-setup ──► feat/admin-auth ──► feat/lead-management ──┬─► feat/kanban-pipeline
                                                                  ├─► feat/lead-activities   (en paralelo)
                                                                  └─► feat/csv-export
```
- `feat/admin-auth`: es la base de todo. Mueve el User, crea el layout base, el login y el gate. No se puede paralelizar porque todas las rutas cuelgan de su middleware.
- `feat/lead-management` es dueña de la migración `leads` completa (incluidas `status`, `position` y la UNIQUE diferida), del enum, del lock y de `CreateLead` (que añade al final de la columna). Así las tres ramas siguientes no tocan el esquema de `leads`.
- Las tres finales van en paralelo: cada una añade su propio contrato (ISP), su propio archivo de rutas y su propio binding. El único punto de conflicto previsible es una línea en `DomainServiceProvider`, que se resuelve de forma trivial.
- Opcional, tras las anteriores: `feat/ui-polish` (`ui-polisher` + `accessibility-pro`) y `test/e2e-critical-flows` (`playwright-expert`: login → crear lead → arrastrar → exportar).

## 7. Tests primero (TDD) y agente por rama

### feat/admin-auth
Implementa `laravel-specialist`; audita `security-scanner`.
- Unit:
  - `it('normalizes an email address to lowercase without surrounding spaces')`
  - `it('rejects an invalid email address')`
  - `it('creates an admin user with a hashed password')`
  - `it('refuses to create an admin when the email already exists')`
- Feature:
  - `it('shows the login form to guests')`
  - `it('logs in an admin with valid credentials and regenerates the session')`
  - `it('rejects invalid credentials with a generic error')`
  - `it('throttles login after five failed attempts per email and ip')`
  - `it('logs out and invalidates the session')`
  - `it('rejects logout via GET')`
  - `it('redirects guests from protected routes to login')`
  - `it('forbids authenticated non-admin users')`
  - `it('does not expose a registration route')`
  - `it('creates an admin via the crm:create-admin command')`
  - `it('fails the command with a weak password')`
  - `it('seeds the admin from env and fails when env vars are missing')`
- Arch:
  - `arch('application and infrastructure classes are final')`
  - `arch('http layer does not use infrastructure')`

### feat/lead-management
Primero `database-expert` (migraciones, CHECK, UNIQUE diferida, índices; primer commit) y después `laravel-specialist`, en secuencia y nunca a la vez. Audita `code-reviewer`.
- Unit:
  - `it('exposes the board columns in business order')`
  - `it('allows free transitions between open statuses')`
  - `it('allows moving between any two statuses')`
  - `it('treats a same-status move as a valid reorder')`
  - `it('validates and normalizes phone numbers')`
- Integración (repositorio y PG):
  - `it('rejects an unknown status at the database level')`
  - `it('rejects a blank name at the database level')`
  - `it('appends a new lead at the end of its status column')`
  - `it('assigns distinct positions to leads created concurrently in the same column')` (dos conexiones, o simulación del lock)
  - `it('moves a lead to the end of the new column when its status changes on edit')`
  - `it('dispatches LeadStatusChanged only when the status changes')`
- Feature (Livewire):
  - `it('creates a lead from the form with minimal data')`
  - `it('validates required name and email format')`
  - `it('edits an existing lead')`
  - `it('lists and searches leads by name, email or company')`
  - `it('forbids lead management to non-admins')`

### feat/kanban-pipeline
TODO (seguridad N-4, diferido desde admin-auth): el endpoint `livewire/update` debe llevar `auth` + `admin` (p. ej. `Livewire::setUpdateRoute` con esos middleware) en cuanto exista el primer componente con datos; añadir un test que haga POST al endpoint como invitado y como no admin.
Implementan `laravel-specialist` (Action y componente) y después `ui-polisher` (Blade, SortableJS, estilos), en secuencia. E2E de arrastre con `playwright-expert`.
- Unit/Action:
  - `it('reorders leads within the same column')`
  - `it('moves a lead to another column at the dropped index and updates its status')`
  - `it('leaves a gap but no collision in the source column')`
  - `it('rejects a disallowed transition and leaves the board untouched')`
  - `it('rejects a stale ordering that does not match the column contents')`
  - `it('rolls back all position changes when the move fails')`
  - `it('dispatches LeadStatusChanged after commit only on status change')`
- Feature:
  - `it('renders one column per status with leads ordered by position')`
  - `it('persists the order after moveLead is called')`
  - `it('rejects an invalid status string or foreign ids in moveLead')`
  - `it('forbids moving leads to non-admins')`
  - `it('shows an error and re-renders the board on a stale move')`

### feat/lead-activities
Implementa `laravel-specialist`; audita `code-reviewer`.
- Unit:
  - `it('requires a non-blank description')`
  - `it('accepts an activity without type')`
  - `it('rejects an unknown activity type')`
- Integración:
  - `it('lists activities of a lead newest first')`
  - `it('deletes activities when their lead is deleted')`
  - `it('rejects an unknown type at the database level')`
- Feature:
  - `it('logs an activity from the lead page')`
  - `it('validates occurred_at as a date not far in the future')`
  - `it('does not show activities of other leads')`
  - `it('forbids logging activities to non-admins')`
  - `it('records a note activity when a lead changes status')` (solo si se incluye el listener)

### feat/csv-export
Implementa `laravel-specialist`; audita `security-scanner` (inyección CSV).
- Unit:
  - `it('prefixes cells starting with = + - @ tab or carriage return with a quote')`
  - `it('leaves safe cells untouched')`
  - `it('writes a header row and one row per lead with fputcsv quoting')`
  - `it('resolves the csv exporter strategy by format and fails on unknown format')`
- Integración:
  - `it('streams leads in id order using lazy chunks')`
  - `it('filters exported leads by one or several statuses')`
- Feature:
  - `it('downloads a streamed csv with attachment headers')`
  - `it('exports all leads when no status filter is given')`
  - `it('rejects an invalid status filter with 422')`
  - `it('neutralizes formula payloads in exported names and notes')`
  - `it('forbids export to guests and non-admins')`
  - `it('throttles repeated exports')`

## Decisiones del orquestador (2026-11-22)
1. Listener de actividad automática al cambiar estado: NO en el MVP (YAGNI).
2. Transiciones: libres entre cualquier par de estados (el usuario pidió cambiar estado arrastrando). `LeadStatus::canTransitionTo` se mantiene como único punto de extensión; hoy solo rechaza mover al mismo estado si no cambia el orden (no-op).
3. Borrado de leads: fuera del MVP.

## Rendimiento y caché del MVP (ver ~/.claude/CLAUDE.md §3.1)
- **Kanban**: 1 consulta para todos los leads del tablero (`select` de columnas de tarjeta + `withCount('activities')` si se muestra), agrupado en PHP por `status`. Índice `(status, position)` ya cubre el orden. Test: `it('renders the board with a constant number of queries')` con 5 y con 200 leads → mismo número de queries.
- **Listado de leads**: `paginate(25)` + índice en `lower(email)` y `created_at`; búsqueda por nombre/empresa con índice `pg_trgm` (GIN) solo si se añade búsqueda.
- **Timeline de actividades**: índice `(lead_id, occurred_at DESC, id DESC)`; paginado/limitado a las N últimas.
- **Export CSV**: `streamDownload` + `lazyById(500)` + `select` mínimo → memoria constante. Test: `it('exports 5000 leads without loading them all in memory')` (pico de memoria acotado).
- **Caché**: el MVP NO cachea el tablero (un solo admin, datos que cambian en cada drag → invalidación constante, coste > beneficio). Se cachean solo los conteos por estado del tablero (`leads:board:counts:v1`, TTL 5 min) invalidados por el evento `LeadStatusChanged` y por alta de lead, con test de invalidación. Driver `database`.
- **Livewire**: tablero con `#[Computed]` para columnas, `#[Locked]` en IDs, payload del drag solo IDs; formularios con `wire:model.blur`.
- **Producción**: `php artisan optimize`, OPcache, autoload optimizado (README §Producción).
- **Auditoría**: `performance-engineer` revisa `feat/kanban-pipeline` y `feat/csv-export` además de `code-reviewer`.
