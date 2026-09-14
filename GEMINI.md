# MANDATORY AGENT WORKSPACE RULE: SYSTEM MEMORY & CROSS-FILE SYNCHRONIZATION

## Core Rule: "No Isolated Changes"
Whenever you update, refactor, add, or delete any function, method, parameter, database column/table, API handler action, session key, or UI component in this project:

1. **CONSULT SYSTEM MEMORY FIRST**:
   - Always read and follow [SYSTEM_MEMORY.md](file:///C:/xampp/htdocs/OJAMS/SYSTEM_MEMORY.md).
   - Use its **Feature-to-File Dependency Matrix** to identify all files connected to the feature you are working on.

2. **COMPREHENSIVE DEPENDENCY TRACING**:
   - Before writing or modifying code, search the codebase (using `grep_search`) for every reference, call site, and consumer of the target symbol/function/endpoint/column.
   - Trace across all layers:
     - Database (`config/database.sql`)
     - Core & Auth (`config/config.php`, `config/auth.php`, `config/db.php`, `config/mailer.php`, `config/sms.php`)
     - Handlers (`handlers/*.php`)
     - Pages (`pages/admin/*`, `pages/staff/*`, `pages/user/*`, public `.php` files)
     - Modals & Components (`modals/*`, `components/*`)
     - Assets & JS (`assets/js/script.js`, `assets/css/*`)

3. **SYNCHRONOUS MULTI-FILE UPDATES**:
   - Never update a backend handler or function in isolation while leaving the frontend modal, page view, or JavaScript out of sync.
   - Update ALL connected files in the same turn/task.
   - Keep CSRF tokens, parameter names, JSON response keys (`success`, `message`, `data`), and form input names aligned everywhere.

4. **MAINTAIN SYSTEM MEMORY**:
   - Whenever any architectural element, endpoint, schema, or convention changes, update [SYSTEM_MEMORY.md](file:///C:/xampp/htdocs/OJAMS/SYSTEM_MEMORY.md) to keep the project memory accurate and up-to-date.
