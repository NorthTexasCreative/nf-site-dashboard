# Phase 3 – Spreadsheet Import + Updates Integrity Layer (No Yellow)

Context:
- Phase 1 (done): WP Engine inventory + sync (Servers/Sites/Environments) as source-of-truth for inventory.
- Phase 2 (done): Excel-style Environments UX (inline edits, stable A→Z sorting, no yellow, reduced columns, notes/tasks page).
- Phase 3 goal: Bring spreadsheet update data into the dashboard so we can see what still needs updates set up, and flag inconsistencies.

Spreadsheet columns to import (ONLY):
- "Update Schedule"
- "Updates Schedule Set"

No yellow anywhere. Red = needs attention, Green = good, Gray = archived/neutral.

Sticky Column Fallback:
If Filament cannot do sticky/frozen columns, implement the closest acceptable fallback:
- Sticky header
- Environment Name as first column
- Reduce visible columns
- Full-width layout
- Wrap long text instead of forcing horizontal scroll
We will refine further if needed.

---

## 1) DB Changes (wpe_environments)

Add these columns to wpe_environments (or your environments table synced from WPE installs):

### Spreadsheet fields (imported + editable)
- update_schedule (string, nullable) — allow blank
- updates_schedule_set (boolean, default false)

### Ops fields (already in Phase 2, but confirm present)
- update_method (string, nullable) allowed values:
  - wpe_managed
  - script
  - manual
  - none
  Default: null or "none" (DO NOT auto-fill; leave unset until user sets it)

- lifecycle_status (string, default "active") allowed:
  - active
  - to_be_deleted
  - deleted
  Default: active
  IMPORTANT: Sync must NEVER overwrite lifecycle_status, update_schedule, updates_schedule_set, update_method, notes.

### WPE truth field
- Ensure WPE status from API is stored (read-only in UI), e.g. wpe_status/status already exists or add it.
  Sync may overwrite this.

---

## 2) Derived Column: Updates Integrity (NO yellow)

Create a derived/computed display value called "Updates Integrity".
This is NOT stored; it is derived from:
- update_schedule
- updates_schedule_set
- lifecycle_status

If lifecycle_status = deleted:
- Integrity must be neutral (gray) and should NOT show red/green states.
- Show "Archived" (gray) or blank.

Otherwise (lifecycle_status != deleted), apply this logic:

- update_schedule is NULL/blank AND updates_schedule_set = false -> "Not Set" (RED)
- update_schedule is NULL/blank AND updates_schedule_set = true  -> "No Schedule" (RED)
- update_schedule has value AND updates_schedule_set = false      -> "Not Confirmed" (RED)
- update_schedule has value AND updates_schedule_set = true       -> "OK" (GREEN, calmer/subtle)

Make green calmer and red louder:
- Green "OK" should be visually subtle (e.g., outline badge or lower emphasis)
- Red states should be solid/strong

---

## 3) Environments Table UI Updates (Phase 2 table, with Phase 3 additions)

Add/ensure these columns exist in the Environments table (fixed order as previously designed; key is to include Integrity + Schedule Set):

- Environment Name (sticky left; clickable to Notes/Tasks page)
- Lifecycle Status (inline editable)
- WPE Status (read-only)
- Updates Integrity (derived; not editable)
- Updates Schedule Set (inline editable Yes/No or toggle)
- Update Schedule (inline editable select; blank allowed)
- Update Method (inline editable select)
- Env Type badge (prod/staging/dev)
- Site name
- Server nickname
- WP Version / PHP Version (read-only)

Update Schedule allowed values (exact text):
- Tuesday AM (A-F)
- Wednesday AM (G-L)
- Thursday AM (M-R)
- Friday AM (S-Z)
- Daily
- Manual
Blank allowed; no placeholder text.

Sorting:
- Default sort remains Environment Name A→Z ONLY.

Filters (add to existing Phase 2 filters):
- Updates Schedule Set = No
- Update Schedule is blank
- Updates Integrity (Not Set / No Schedule / Not Confirmed / OK / Archived)
- Update Schedule dropdown
- (keep lifecycle/server/env-type filters)

Styling:
- No yellow anywhere.
- Deleted rows visually muted; Integrity neutral (gray Archived or blank).

---

## 4) CSV Import (websites.csv)

Implement an admin-only import action to update ONLY:
- update_schedule from CSV column "Update Schedule"
- updates_schedule_set from CSV column "Updates Schedule Set"

Matching:
- Primary key: CSV["Environment Name"] -> wpe_environments.name
- If ambiguous (multiple matches), use additional CSV fields when available (server nickname / environment type) to disambiguate.
- Produce import summary:
  - matched, updated, skipped, unmatched, ambiguous
  - allow export/download of unmatched + ambiguous rows

Overwrite behavior:
- Default: DO NOT overwrite non-empty existing values unless user selects “overwrite existing”.

Boolean normalization for "Updates Schedule Set":
- Yes/True/1 => true
- No/False/0/blank => false

Data honesty:
- Import values as-is (do not auto-correct mismatches). Integrity column will surface issues.

---

## 5) Sync constraints (repeat for safety)
- Sync jobs must never overwrite: lifecycle_status, update_schedule, updates_schedule_set, update_method, notes.
- Sync jobs may overwrite WPE truth fields: wp_version, php_version, wpe_status, primary_domain, etc.