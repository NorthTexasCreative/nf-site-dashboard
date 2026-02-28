# Phase 4 – Manual “Sync WP Engine Inventory” Button (Service-based, queue-ready later)

Goal:
Add a Filament admin button so Carla can run the WP Engine inventory sync without using terminal commands.
We will refactor to a service so both the Artisan command and UI button use the same core logic (best practice).
No Laravel queue required for now (run inline), but structure it so we can switch to a queued job later.

Current CLI command used in DDEV:
- ddev exec php artisan wpe:sync-inventory 2>&1 | cat
Note: In-app we do NOT run ddev exec or shell pipes. We call the service directly (and the command calls the service).

---

## 1) Create a Service (single source of truth)

Create `app/Services/Wpe/WpeInventorySyncService.php` (or similar namespace).

Responsibilities:
- Perform the existing inventory sync logic currently inside the `wpe:sync-inventory` command.
- Return a structured result object/array:
  - accounts_synced_count
  - sites_synced_count
  - environments_synced_count
  - duration_seconds
  - any warnings
- Throw exceptions on hard failures so caller can record failure.

Design:
- `public function run(?callable $progress = null): WpeSyncResult`
  - $progress optional callback can be no-op for now.

Move the sync implementation from the Artisan command into this service (do not duplicate logic).

---

## 2) Update the Artisan Command to call the Service

Modify the existing `wpe:sync-inventory` Artisan command so it only:
- resolves the service from container
- calls `$service->run()`
- prints concise progress/summary (optional)
- exits with success/failure code

The command should remain usable from terminal, but the logic lives in the service.

---

## 3) Add Sync Run Logging (recommended)

Create a table/model like:
- `wpe_sync_runs` (or `sync_runs` scoped to WPE)

Fields:
- id
- sync_type (string) e.g. "inventory"
- triggered_by_user_id (nullable)
- started_at (datetime)
- finished_at (datetime nullable)
- status (string) "running" | "success" | "failed"
- duration_seconds (int nullable)
- accounts_count (int nullable)
- sites_count (int nullable)
- environments_count (int nullable)
- message (string nullable) // short human summary
- output (longText nullable) // optional; store warnings/stack traces
- error (longText nullable)  // optional

Keep it simple. This is mainly to show “last sync” and help debug.

---

## 4) Prevent Concurrent Runs (Locking)

Use a cache lock:
- key: `wpe-sync-inventory-lock`
- TTL: 30 minutes (or suitable value)

Behavior:
- If lock cannot be acquired:
  - show Filament notification: “Sync already running.”
  - do not start another sync.

Ensure lock is released in finally block.

---

## 5) Add Filament UI Button (“Sync WP Engine Inventory”)

Where:
- Add to Filament Dashboard header actions OR a small “Tools”/“Servers” area (whichever fits your app).
- The action should run inline (no queue required).

Button behavior:
1) Try to acquire lock; if locked, notify and stop.
2) Create a SyncRun row with:
   - sync_type="inventory"
   - triggered_by_user_id=current user
   - started_at=now
   - status="running"
3) Call the service:
   - `$result = app(WpeInventorySyncService::class)->run();`
4) On success:
   - update SyncRun:
     - finished_at, status="success"
     - duration_seconds + counts from result
     - message like "Synced X servers, Y sites, Z environments"
5) On failure (catch Throwable):
   - update SyncRun:
     - finished_at, status="failed"
     - error/stack trace (store exception message + trace in `error`)
     - message like "Sync failed: <exception message>"
   - show Filament notification error
6) Release lock.
7) Show Filament success notification on completion.

Important UX:
- Keep notifications concise.
- No yellow statuses.

---

## 6) Display “Last Sync” Info

Add a small dashboard widget/card (or top-of-page info) that shows:
- Last inventory sync status (success/failed/running)
- Last finished_at time
- Last counts (servers/sites/environments)

Optional:
- Add a Filament resource/page to view the SyncRun history table (filter by sync_type="inventory").

---

## 7) Future-proofing (do NOT implement now, just structure for it)

- Keep the service pure so later we can:
  - dispatch a Job that calls the service
  - run it via queue worker
  - schedule daily with Laravel Scheduler on an always-on server

But for now, the button runs inline.

---

## 8) Acceptance Criteria

- Clicking the button runs the sync without using terminal.
- A lock prevents double-runs.
- A SyncRun record is created and updated with success/failure.
- Dashboard shows “Last Sync” info.
- Artisan command `wpe:sync-inventory` still works and uses the same service.