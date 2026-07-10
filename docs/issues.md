1. ✅ DONE — Contact Number must not accept letters, limit to 11 characters.
   Implemented: contact inputs (register, profile settings, job application) strip non-digits as you type, cap at 11, and both client and server now require exactly 11 digits.

2. ✅ DONE — The Password must not be copyable into Confirm Password.
   Implemented: copy, cut, paste, and drag-drop are blocked on the password and confirm-password fields (register, reset password, and profile settings), so the confirmation must be retyped.

3. ✅ DONE — Forgot Password via Security Question.
   Implemented: registration now requires picking a security question and answer. Forgot Password is a 2-step flow — enter email, then pick the correct question from the list and answer it (5 wrong attempts = 60s lockout). On success the user is taken straight to the set-new-password page. The security question can also be changed in Profile Settings (user and admin) — requires current password.
   DB: `users` table gained `security_question` and `security_answer_hash` columns (migration: `database/migrations/2026-07-10-add-security-question.sql`, already applied locally). Existing accounts have no question set — they must add one in Profile Settings before they can use Forgot Password.
4. ✅ DONE — Register with a Role picker (Applicant or Staff), staff needs admin approval before login.
   Implemented: register page now has a "Register as" choice (Applicant / Staff). Staff accounts are created unapproved (`users.is_approved = 0`) and see "awaiting administrator approval" if they try to log in. Admin → User Management shows a "Pending Approval" badge with an Approve button, plus a Staff role filter. Approved staff temporarily use the applicant pages since the staff module doesn't exist yet — the full staff function is drafted in `docs/STAFF-ROLE-DRAFT.md` for later.
   DB: `role` enum gained 'staff' and `is_approved` column added (migration: `database/migrations/2026-07-10-add-staff-role.sql`, already applied locally).