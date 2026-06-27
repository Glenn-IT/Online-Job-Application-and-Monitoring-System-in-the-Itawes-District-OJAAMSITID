# OJAMS — Known Issues Log

---

## Issue #1 — Stuck on Under Construction page after login (v1.01)

**Status:** Fixed  
**Affected version:** v1.01  
**Reported:** 2026-06-27

### What happened

After logging in successfully, the user was permanently stuck on the Under Construction page. Clicking the "Log Out" button redirected to a 404 error page showing a "Go Back" button — which looped back to the same under construction page.

### Root cause

`components/under-construction.php` uses `<?= BASE_URL ?>` to build the logout link href. However, the pages that include this component (e.g. `dashboard.php`, `browse-jobs.php`) loaded it **before** `config/auth.php`, which is what defines `BASE_URL`.

Because `BASE_URL` was undefined at the time `under-construction.php` ran, the logout link rendered as `/logout.php` instead of `http://localhost/OJAMS/logout.php`. That path resolved to the server root, which returned a 404 — and the 404 page has a `javascript:history.back()` "Go Back" button, trapping the user in a loop.

### Fix applied

Added a guard in `components/under-construction.php` to self-load the config if `BASE_URL` is not yet defined:

```php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
```

This ensures the logout link is always fully qualified regardless of include order.

---
