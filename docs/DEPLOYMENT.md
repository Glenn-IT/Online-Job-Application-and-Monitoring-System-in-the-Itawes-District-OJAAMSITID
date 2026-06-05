# OJAMS — Deployment Guide

## Requirements

| Component | Minimum version |
|---|---|
| PHP | 8.0+ |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Apache | 2.4+ with `mod_rewrite` enabled |
| XAMPP (local dev) | 8.x |

---

## 1. Database Setup

1. Open phpMyAdmin or the MySQL CLI.
2. Run `config/database.sql` once to create the `ojams_db` database, all tables, and seed data:
   ```bash
   mysql -u root -p < config/database.sql
   ```
3. If upgrading an existing install, run only the ALTER TABLE statements in the comment block at the top of `database.sql` (they are all commented out by default — un-comment and run the ones that apply to your version).

---

## 2. Environment Configuration

1. Copy `.env.example` to `.env` (if it doesn't exist, create `.env` in the project root):
   ```ini
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=ojams_db
   DB_USER=root
   DB_PASS=your_password
   BASE_URL=https://yourdomain.com/OJAMS
   APP_NAME=OJAMS
   ```
2. `config/config.php` reads this file automatically. Never commit `.env` to version control.

---

## 3. Apache Virtual Host (Production)

Create a vhost entry in `httpd-vhosts.conf`:

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot "/var/www/html/OJAMS"

    <Directory "/var/www/html/OJAMS">
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/yourdomain.crt
    SSLCertificateKeyFile /etc/ssl/private/yourdomain.key
</VirtualHost>

# Redirect HTTP → HTTPS
<VirtualHost *:80>
    ServerName yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

The `.htaccess` in the project root handles internal redirects (HTTPS enforcement and custom 404).

---

## 4. Directory Permissions

```bash
# Writable by the web server
chmod 755 uploads/
chmod 755 uploads/resumes/
chmod 755 uploads/avatars/
chmod 755 logs/

# Protect sensitive files
chmod 640 .env
chmod 640 config/config.php
```

---

## 5. HTTPS Enforcement

The `.htaccess` at the project root already contains:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```
This forces all traffic to HTTPS. Ensure `mod_rewrite` is enabled:
```bash
a2enmod rewrite && systemctl restart apache2
```

---

## 6. Email (Forgot Password)

OJAMS sends password-reset emails via PHP `mail()`. For production:

1. Configure your server's MTA (Postfix, sendmail) **or** use an SMTP relay.
2. Recommended: replace the `mail()` call in `handlers/forgot-password.php` with PHPMailer + SMTP credentials stored in `.env`.

---

## 7. Session Security (Production Hardening)

Add to `config/config.php` or a `php.ini` override:
```ini
session.cookie_httponly = 1
session.cookie_secure   = 1   ; requires HTTPS
session.cookie_samesite = Strict
```

---

## 8. Cron — Rate Limit Cleanup

Old rate-limit rows accumulate in the `rate_limits` table. Add a daily cron to purge them:
```cron
0 3 * * * mysql -u ojams_user -p'password' ojams_db -e "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 DAY);"
```

---

## 9. Local Development (XAMPP)

1. Clone or copy the project to `C:\xampp\htdocs\OJAMS\`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Import `config/database.sql` via phpMyAdmin.
4. Create `.env` with `BASE_URL=http://localhost/OJAMS`.
5. Visit `http://localhost/OJAMS` in your browser.

Default admin credentials (from seed data):
- Email: `admin@ojams.com`
- Password: `admin123`

**Change the default admin password immediately after first login.**

---

## 10. File Upload Storage

Uploaded files are stored in:
- `uploads/resumes/` — application résumés
- `uploads/avatars/` — profile photos

These directories are created automatically on first upload. Ensure they are **not publicly listed** (the `.htaccess` blocks directory browsing) and are **backed up** regularly.
