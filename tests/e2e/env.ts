export const PORT = 8010;
export const BASE_URL = `http://127.0.0.1:${PORT}`;
export const E2E_DATABASE = 'crm_e2e';
export const ADMIN_EMAIL = 'e2e-admin@example.test';
export const ADMIN_PASSWORD = 'correct-horse-battery';
export const STORAGE_STATE = 'test-results/.auth/admin.json';

export const appEnv = {
    APP_ENV: 'local',
    APP_URL: BASE_URL,
    DB_DATABASE: E2E_DATABASE,
    SESSION_SECURE_COOKIE: 'false',
    AUTH_TIMEBOX_DURATION: '0',
    CACHE_STORE: 'file',
    PHP_CLI_SERVER_WORKERS: '4',
};
