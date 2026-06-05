# OJAMS — Entity-Relationship Diagram

```
┌──────────────┐         ┌──────────────────────────┐
│    users     │         │          jobs            │
├──────────────┤         ├──────────────────────────┤
│ PK id        │◄──┐     │ PK id                    │
│    role      │   │     │    title                 │
│    full_name │   │     │    company               │
│    email     │   │     │    description           │
│    password  │   │     │    qualification         │
│    contact   │   │     │    location              │
│    address   │   │     │    job_type              │
│    birthdate │   │     │    salary_range          │
│    is_active │   │     │    date_posted           │
│    photo     │   │     │    status                │
│    created_at│   │     │    deadline              │
│    updated_at│   │  FK │ FK created_by ──────────►│ users.id
└──────────────┘   │     │    created_at            │
       ▲           │     │    updated_at            │
       │           │     └──────────────────────────┘
       │           │                ▲ ▲
  FK performed_by  │                │ │ FK job_id
       │           │    ┌───────────┘ │
┌──────────────────┴─┐  │             │
│   activity_logs    │  │  ┌──────────┴──────────────┐
├────────────────────┤  │  │      applications        │
│ PK id              │  │  ├──────────────────────────┤
│    action          │  │  │ PK id                    │
│    status          │  │  │ FK user_id ─────────────►│ users.id
│ FK performed_by    │  │  │ FK job_id  ─────────────►│ jobs.id
│ FK job_id ─────────┼──┘  │    full_name (snapshot)  │
│ FK application_id  │◄──┐ │    email    (snapshot)   │
│    created_at      │   │ │    contact, address      │
└────────────────────┘   │ │    birthdate, age        │
                         │ │    elementary, jhs       │
                         │ │    shs, college          │
                         │ │    skills, experience    │
                         │ │    status                │
                         │ │    date_applied          │
                         │ │    updated_at            │
                         │ └──────────────────────────┘
                         │         ▲   ▲
                    FK application_id  │ FK application_id
                         │             │
┌────────────────────────┴──┐  ┌───────┴──────────────────┐
│ application_status_history│  │         resumes           │
├───────────────────────────┤  ├───────────────────────────┤
│ PK id                     │  │ PK id                     │
│ FK application_id         │  │ FK application_id         │
│    from_status            │  │ FK user_id ──────────────►│ users.id
│    to_status              │  │    original_name          │
│ FK changed_by ───────────►│  │    stored_name           │
│    changed_at             │  │    file_size              │
└───────────────────────────┘  │    mime_type              │
                               │    uploaded_at            │
                               └───────────────────────────┘

┌──────────────────────┐   ┌──────────────────┐
│      saved_jobs      │   │  login_attempts  │
├──────────────────────┤   ├──────────────────┤
│ PK id                │   │ PK ip            │
│ FK user_id ─────────►│   │    attempts      │
│ FK job_id  ─────────►│   │    last_attempt  │
│    saved_at          │   └──────────────────┘
└──────────────────────┘

┌──────────────────────┐   ┌──────────────────────┐
│     rate_limits      │   │   password_resets    │
├──────────────────────┤   ├──────────────────────┤
│ PK key (ip:endpoint) │   │ PK id                │
│    hits              │   │    email             │
│    window_start      │   │ UQ token             │
└──────────────────────┘   │    expires_at        │
                           │    created_at        │
                           └──────────────────────┘
```

## Cardinality Summary

| Relationship | Type |
|---|---|
| users → jobs (created_by) | 1 : N |
| users → applications (user_id) | 1 : N |
| jobs  → applications (job_id) | 1 : N |
| applications → resumes | 1 : 0..1 |
| applications → application_status_history | 1 : N |
| users → saved_jobs | 1 : N |
| jobs  → saved_jobs | 1 : N |
| users → activity_logs (performed_by) | 1 : N |
| jobs  → activity_logs (job_id) | 1 : N |
| applications → activity_logs (application_id) | 1 : N |

## Key Constraints

- `applications(user_id, job_id)` — UNIQUE (one application per user per job)
- `saved_jobs(user_id, job_id)` — UNIQUE (one bookmark per user per job)
- `users.email` — UNIQUE (case-insensitive enforced at application layer)
- `password_resets.token` — UNIQUE
- Cascades: deleting a user cascades to their applications, saved_jobs, resumes. Deleting a job cascades to its applications and saved_jobs.
