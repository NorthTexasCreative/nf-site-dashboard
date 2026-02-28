# Deployment (Laravel Cloud)

## Pushing to GitHub

The project is initialized as a Git repo with an initial commit on `master`. The remote `origin` may point to a placeholder URL. Point it at your actual GitHub repo and push:

```bash
git remote set-url origin https://github.com/YOUR_GITHUB_USERNAME_OR_ORG/nf-site-dashboard.git
# or SSH: git remote set-url origin git@github.com:YOUR_GITHUB_USERNAME_OR_ORG/nf-site-dashboard.git
git push -u origin master
```

Replace `YOUR_GITHUB_USERNAME_OR_ORG` with your GitHub username or organization. Ensure the repo `nf-site-dashboard` exists on GitHub first.

---

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

---

## Laravel Cloud setup

1. **Create application** from your GitHub repo `nf-site-dashboard` (connect the repo after pushing; see “Pushing to GitHub” above).

2. **Runtime**
   - PHP 8.2+ (e.g. 8.4 if available).
   - Add a database (MySQL or Postgres); Laravel Cloud will provide `DB_*` env vars.

3. **Environment variables** (set in Laravel Cloud dashboard)
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL` = your app URL (e.g. `https://your-app.laravelcloud.com`)
   - `APP_KEY` (generate with `php artisan key:generate --show` if not set by the platform)
   - Database: use the vars provided by Laravel Cloud for the attached DB.
   - `SESSION_DRIVER=database`
   - `CACHE_STORE=database`
   - `WPE_API_BASE_URL=https://api.wpengineapi.com/v1`
   - `WPE_API_USER` and `WPE_API_PASSWORD` (WP Engine API credentials)

4. **Build / deploy commands**
   - Install PHP deps: `composer install --no-dev --optimize-autoloader`
   - Install Node deps: `npm ci` or `npm install`
   - Build assets: `npm run build`
   - Run migrations: `php artisan migrate --force`
   - Optional: `php artisan config:cache` and `php artisan route:cache`

5. **Post-deploy verification**
   - Open `APP_URL/admin` and confirm the login page loads.
   - Create the first admin user (e.g. via Tinker or a one-off script); do not rely on seeding in production.
   - Confirm “Last Inventory Sync” widget and “Sync WP Engine Inventory” button appear; run sync once and check that a row is created in `wpe_sync_runs`.
   - Verify `/admin/environments` loads and that Filament/Vite assets (CSS/JS) load correctly.
