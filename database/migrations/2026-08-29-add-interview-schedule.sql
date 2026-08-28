-- Migration: Add interview_date and interview_notes to applications table
ALTER TABLE applications
ADD COLUMN interview_date DATETIME DEFAULT NULL AFTER status,
ADD COLUMN interview_notes TEXT DEFAULT NULL AFTER interview_date;
