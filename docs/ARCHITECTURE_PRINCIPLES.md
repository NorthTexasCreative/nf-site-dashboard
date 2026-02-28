You are building an internal operational dashboard.

Priorities:
1) Inventory from WP Engine API is source of truth.
2) Never overwrite human metadata during sync.
3) Environment is the unit of work (not site).
4) Favor clarity and operational visibility over clever UI.
5) Optimize for scanning and exception management.
6) Avoid premature complexity.
7) Use services for external API logic.
8) Never hard delete synced records; mark lifecycle state instead.