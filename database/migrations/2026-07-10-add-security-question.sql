-- Adds security question + hashed answer to users
-- (used by the new forgot-password flow)
ALTER TABLE `users`
  ADD COLUMN `security_question`    VARCHAR(255) DEFAULT NULL AFTER `profile_photo`,
  ADD COLUMN `security_answer_hash` VARCHAR(255) DEFAULT NULL AFTER `security_question`;
