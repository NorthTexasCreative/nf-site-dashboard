# Phase 1 – Inventory Sync

Project: nf-site-dashboard

Goal:
Sync WP Engine inventory automatically and display it in Filament.

Terminology:
- WPE Account = Server (UI term)
- WPE Install = Environment (UI term)
- WPE Site = Site

Data Model:
- Servers (wpe_account_id, name, nickname)
- Sites (wpe_site_id, wpe_account_id, name, group_name, tags, created_at_wpe, sandbox, transferable, lifecycle_status, notes, last_synced_at)
- Environments (wpe_install_id, wpe_site_id, name, environment, cname, php_version, is_multisite, status, primary_domain, wp_version, stable_ips, last_synced_at, last_detail_sync_at)

API Endpoints:
- GET /accounts
- GET /sites (paginated)
- GET /installs/{install_id}

Requirements:
- Never hard delete records; mark missing as lifecycle_status=unknown
- Default environment policies not implemented yet (Phase 2)
- Create Filament resources for Servers, Sites, Environments
- Create Needs Attention widget