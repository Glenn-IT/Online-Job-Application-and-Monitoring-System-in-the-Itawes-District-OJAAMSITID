# OJAMS Handler API Reference

All handlers live in `handlers/`. They accept `POST` only, return JSON, and require a valid `csrf_token` in every request body (except file-upload multipart requests, where CSRF is a form field).

---

## Common Response Shape

```json
{ "success": true|false, "message": "Human-readable string" }
```

---

## handlers/applications.php

| Action             | Role  | Description                                                           |
| ------------------ | ----- | --------------------------------------------------------------------- |
| `apply`            | user  | Submit a job application (multipart, includes optional `resume` file) |
| `cancel`           | user  | Cancel a pending application (`id`)                                   |
| `updateStatus`     | admin | Set application status to `Approved` or `Rejected` (`id`, `status`)   |
| `getDetails`       | admin | Return full application data + status history + resume info (`id`)    |
| `bulkUpdateStatus` | admin | Bulk approve/reject (`ids[]`, `status`)                               |
| `bulkDelete`       | admin | Bulk delete applications (`ids[]`)                                    |

### apply (multipart/form-data)

```
action, csrf_token, job_id, full_name, email, contact, address,
birthdate, age, elementary, jhs, shs, college, skills, experience,
resume (optional file — PDF/DOC/DOCX, max 5 MB)
```

### cancel

```json
{ "action": "cancel", "id": 42, "csrf_token": "..." }
```

### updateStatus

```json
{
  "action": "updateStatus",
  "id": 42,
  "status": "Approved",
  "csrf_token": "..."
}
```

### getDetails

```json
{ "action": "getDetails", "id": 42, "csrf_token": "..." }
```

Returns: `{ "success": true, "data": {...}, "history": [...], "resume": {...}|null }`

### bulkUpdateStatus

```json
{
  "action": "bulkUpdateStatus",
  "ids": [1, 2, 3],
  "status": "Rejected",
  "csrf_token": "..."
}
```

### bulkDelete

```json
{ "action": "bulkDelete", "ids": [1, 2, 3], "csrf_token": "..." }
```

---

## handlers/jobs.php

Admin only.

| Action       | Description                            |
| ------------ | -------------------------------------- |
| `add`        | Create a new job posting               |
| `edit`       | Update an existing job (`id` required) |
| `delete`     | Delete a job (`id` required)           |
| `bulkDelete` | Delete multiple jobs (`ids[]`)         |

### add / edit (JSON)

```json
{
  "action": "add",
  "title": "Web Developer",
  "company": "ABC Corp",
  "description": "...",
  "qualification": "...",
  "location": "Tuguegarao City",
  "job_type": "Full-time",
  "salary_range": "₱25,000–₱35,000",
  "date_posted": "2026-06-01",
  "status": "Open",
  "deadline": "2026-07-01",
  "csrf_token": "..."
}
```

---

## handlers/profile.php

Authenticated users and admins.

| Action           | Description                                                                  |
| ---------------- | ---------------------------------------------------------------------------- |
| `updateInfo`     | Update name, email, contact, address, birthdate (JSON)                       |
| `changePassword` | Change password — throttled to 3 attempts / 30 s lockout (JSON)              |
| `uploadAvatar`   | Upload profile photo (multipart, `avatar` file — JPG/PNG/GIF/WebP, max 2 MB) |

### updateInfo

```json
{
  "action": "updateInfo",
  "full_name": "Juan Dela Cruz",
  "email": "juan@example.com",
  "contact_number": "09171234567",
  "address": "123 Main St",
  "birthdate": "1998-05-15",
  "csrf_token": "..."
}
```

### changePassword

```json
{
  "action": "changePassword",
  "current_password": "...",
  "new_password": "...",
  "confirm_password": "...",
  "csrf_token": "..."
}
```

Returns `locked: true` and `retry_after: N` when throttled.

### uploadAvatar (multipart/form-data)

```
action=uploadAvatar, csrf_token=..., avatar=<image file>
```

Returns: `{ "success": true, "url": "http://..." }`

---

## handlers/saved-jobs.php

Users only.

| Action   | Description                                                   |
| -------- | ------------------------------------------------------------- |
| `toggle` | Save or unsave a job (`job_id`) — returns `saved: true/false` |

```json
{ "action": "toggle", "job_id": 3, "csrf_token": "..." }
```

Returns: `{ "success": true, "saved": true, "message": "..." }`

---

## handlers/admin.php

Admin only.

| Action           | Description                                          |
| ---------------- | ---------------------------------------------------- |
| `deactivateUser` | Deactivate a user account (`id`)                     |
| `reactivateUser` | Reactivate a user account (`id`)                     |
| `changeRole`     | Change a user's role (`id`, `role`: `admin`\|`user`) |
| `clearLogs`      | Delete activity logs older than N days (`days`)      |

---

## Rate Limits

| Endpoint key    | Limit                                        |
| --------------- | -------------------------------------------- |
| `applications`  | 30 req / 60 s per IP                         |
| `jobs`          | 30 req / 60 s per IP                         |
| `profile`       | 20 req / 60 s per IP                         |
| `saved_jobs`    | 30 req / 60 s per IP                         |
| `admin`         | 20 req / 60 s per IP                         |
| Login page      | 5 attempts / 30 s lockout per IP (DB-backed) |
| Password change | 3 attempts / 30 s lockout per session        |
