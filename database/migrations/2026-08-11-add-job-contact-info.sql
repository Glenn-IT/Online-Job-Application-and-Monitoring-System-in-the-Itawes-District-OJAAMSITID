-- Migration: Add contact_person and contact_phone to jobs table
ALTER TABLE jobs
ADD COLUMN contact_person VARCHAR(150) DEFAULT NULL AFTER salary_range,
ADD COLUMN contact_phone VARCHAR(50) DEFAULT NULL AFTER contact_person;
