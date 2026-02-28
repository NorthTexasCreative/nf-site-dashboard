# Deployment (Laravel Cloud)

## Environment variables

Set these in your Laravel Cloud (or host) environment:

- **APP_NAME** – Application name (e.g. `NF Site Dashboard`)
- **APP_ENV** – `production`
- **APP_DEBUG** – `false` in production
- **APP_KEY** – Laravel encryption key (generate with `php artisan key:generate --show` if needed)
- **APP_URL** – Full public URL (e.g. `https://your-app.laravelcloud.com`)

Database (use the DB credentials Laravel Cloud provides or your own):

- **DB_CONNECTION** – `mysql` or `pgsql`
- **DB_HOST**, **DB_PORT**, **DB_DATABASE**, **DB_USERNAME**, **DB_PASSWORD**

Session and cache (database is fine for both):

- **SESSION_DRIVER** – `database`
- **CACHE_STORE** – `database`

WP Engine API (required for inventory sync):

- **WPE_API_BASE_URL** – `https://api.wpengineapi.com/v1`
- **WPE_API_USER** – WP Engine API user
- **WPE_API_PASSWORD** – WP Engine API password

## Build and deploy

Deploy should run:

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci` (or `npm install`) then `npm run build` (Vite assets, including Filament theme)
3. `php artisan migrate --force`
4. `php artisan config:cache` and `php artisan route:cache` (optional)

Do **not** run `php artisan db:seed` in production unless you intend to seed; the app only seeds a test user when `APP_ENV=local`.

## Admin access

- **Local:** After seeding, log in at `/admin` with `test@example.com` / `password`.
- **Production:** Create the first admin user manually (e.g. Tinker or a one-off script). The seeder does not create users in production.

## Inventory sync

- **UI:** In the dashboard at `/admin`, use the **Sync WP Engine Inventory** button. A lock prevents concurrent runs; each run is logged in `wpe_sync_runs`.
- **CLI:** `php artisan wpe:sync-inventory` (with an optional progress callback). Use this from a scheduler or SSH if you want automated syncs.
