# Phase 2 – Environments UX + Update Fields (Excel-style)

Context:
- Inventory comes from WP Engine API (already synced in Phase 1).
- We want to add update-related fields from the spreadsheet and make the Environments screen feel like Excel.
- No “Quick Setup” button/modals. Inline editing only.
- No yellow anywhere. Red = needs attention, Green = good, Gray = inactive/archived.
- Default sort must be stable and predictable: Environment Name A→Z only.

## 1) DB Changes (wpe_environments)
Add these columns to wpe_environments (or your environments table synced from WPE installs):

- update_method (string, nullable) allowed values:
  - wpe_managed
  - script
  - manual
  - none
  Default: null (or "none" if you prefer explicit). IMPORTANT: Do not auto-fill; leave blank/none until user sets it.

- update_schedule (string, nullable) allowed values (exact text):
  - Tuesday AM (A-F)
  - Wednesday AM (G-L)
  - Thursday AM (M-R)
  - Friday AM (S-Z)
  - Daily
  - Manual
  Default: null (blank)

- lifecycle_status (string, default "active") allowed:
  - active
  - to_be_deleted
  - deleted
  Default: active. Sync must NEVER overwrite this.

Also ensure you store WPE status from API (read-only), e.g. wpe_status/status field already exists or add it if missing. Keep it synced from /installs/{id}.

## 2) Derived Field (Updates Setup)
Do NOT store update_schedule_set anymore (if it exists, remove later or stop using it).
“Updates Setup” is derived:
- Yes if update_schedule is NOT null
- No if update_schedule is null
Display only; not editable directly.

Update Schedule cell should be allowed to remain blank.

## 3) Environments Table UI (Filament)
Environments resource/table must be optimized for scanning and inline edits.

### Layout + scrolling
- Make the table use a wider/full-width layout to reduce horizontal scroll.
- Header row must be sticky while scrolling vertically.
- Environment Name column must be sticky/frozen while scrolling horizontally (always visible).
- Fixed layout is fine; no column drag/drop needed.

### Columns (fixed order)
1) Environment Name (sticky left)
   - Clickable link to a 2nd screen for Notes/Tasks (see section 4).
2) Lifecycle Status (inline editable select)
   - Active (green solid badge)
   - To Be Deleted (red solid badge)
   - Deleted (gray badge + row visually muted)
3) WPE Status (read-only badge)
   - If value is "active" => green solid badge
   - Else => red solid badge
4) Updates Setup (derived Yes/No)
   - Yes => green
   - No => red
5) Update Schedule (inline editable select)
   - Allowed values listed above
   - Blank allowed
   - No placeholder text; blank is fine
6) Update Method (inline editable select)
   - wpe_managed / script / manual / none
   - Default blank/none until set
7) Env Type badge (Production/Staging/Development) with colors:
   - Production: green outline badge
   - Staging: blue outline badge
   - Development: gray outline badge
8) Site name
9) Server nickname (WPE account nickname; UI label “Server”)
10) WP Version (read-only)
11) PHP Version (read-only)

Remove/hide columns:
- cname not necessary
- is_multisite not necessary
- any UUID columns hidden by default

### Sorting
- Default sort must be Environment Name ascending (A→Z) ONLY.

### Filters
Add quick filters:
- Lifecycle Status (active / to_be_deleted / deleted)
- Needs Updates Setup (derived: update_schedule is null)
- Update Schedule (dropdown)
- Update Method (dropdown)
- Env Type (prod/staging/dev)
- Server nickname

### Styling rules
- No yellow anywhere.
- Deleted rows should be visually muted (subtle opacity or light gray background), but still editable.
- Red only for attention: Updates Setup = No, Lifecycle = To Be Deleted, WPE status != active.

## 4) 2nd Screen (Notes + Tasks placeholder)
You do not need a View screen for full details.
Instead:
- Clicking Environment Name opens a dedicated page/screen that currently includes:
  - Notes field (editable)
  - Placeholder section for Tasks (can be simple now; full task system later)
This replaces “Quick Setup” as the only “second click” page, and it’s for notes/tasks only.

## 5) CSV Backfill Import (for Update Schedule fields)
Add an import action (admin-only) that reads the existing websites.csv format and updates ONLY:
- update_schedule from CSV column "Update Schedule"
Match primarily by environment name/slug:
- CSV["Environment Name"] -> wpe_environments.name
Optionally also allow matching by install id if present later.
Provide import summary:
- matched / updated / skipped / unmatched rows (downloadable).

Do not overwrite non-empty update_schedule unless user selects “overwrite existing” toggle.

## 6) Important constraints
- Sync jobs must never overwrite: lifecycle_status, update_schedule, update_method, notes.
- Sync jobs may overwrite WPE truth fields: wp_version, php_version, wpe_status, primary_domain, etc.
- Keep the UI Excel-like: inline edit, minimal clicks, stable sorting.

## 7) Sticky Column Fallback
If sticky/frozen columns are not possible in Filament tables:
- Implement the closest acceptable fallback:
  - Sticky header
  - Environment Name as first column
  - Reduce total visible columns
  - Use full-width layout
  - Wrap long text instead of forcing horizontal scroll
We will refine further if needed.