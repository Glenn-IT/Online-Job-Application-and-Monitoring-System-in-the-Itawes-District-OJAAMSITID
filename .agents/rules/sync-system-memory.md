---
trigger: always_on
description: Enforce cross-file synchronization across all connected files based on SYSTEM_MEMORY.md
---

# Cross-File Synchronization Rule

When modifying or refactoring any feature or function in the OJAMS project:
1. Refer to [SYSTEM_MEMORY.md](file:///C:/xampp/htdocs/OJAMS/SYSTEM_MEMORY.md) for full architectural dependencies.
2. Run search queries to trace all related files (database, config, handlers, pages, modals, assets/js).
3. Update all connected files synchronously in the same turn/task.
4. Ensure CSRF protection, input validation, and standard JSON response schemas `{ "success": true|false, "message": "..." }` remain consistent.
