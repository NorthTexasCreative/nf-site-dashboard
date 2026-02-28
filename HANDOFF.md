# NF Site Dashboard - Cursor Handoff

## Goal
Internal dashboard for New Frontier to manage WP Engine sites/environments across multiple WPE servers.

## Terminology
- WPE “Account” == **Server** in UI (display server.nickname, never UUID)
- WPE “Install” == **Environment** in UI (production/staging/development)
- WPE “Site” remains “Site”

## Source of truth
WP Engine API inventory (servers/sites/environments) is authoritative and must be auto-synced. No manual add/remove of sites.

## Required API endpoints
- GET /accounts  -> servers
- GET /sites (paginated) -> sites and install stubs
- GET /installs/{install_id} -> environment details (wp_version, primary_domain, status, etc.)

## Policies
Default update policy per environment:
- production -> smart_updates
- staging/dev -> manual_script
Overrideable per environment.

## Script integration
Existing script accepts a list of environment slugs (install.name) and runs WP-CLI + Elementor CLI updates.
Dashboard must be able to run it for:
- single environment
- bulk selected environments
Store results in runs table.