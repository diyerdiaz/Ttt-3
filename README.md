# CRM Leads MVP

MVP de gestión de leads (Laravel + PostgreSQL). Ver CLAUDE.md.

## Puesta en marcha

```sh
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run build
composer check
```

Requiere PostgreSQL con las BDs `crm` y `crm_test`. Nota: `npm run build` descarga la fuente Instrument Sans (plugin de fuentes de Laravel Vite), por lo que necesita red.
