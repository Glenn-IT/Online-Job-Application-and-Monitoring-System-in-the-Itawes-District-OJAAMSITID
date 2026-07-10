-- Adds the temporary 'staff' role and admin-approval flag.
-- Staff accounts register with is_approved = 0 and cannot log in
-- until an admin approves them in User Management.
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','staff','user') NOT NULL DEFAULT 'user',
  ADD COLUMN `is_approved` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`;
