-- Migration: add `service` column to booking_online
-- Run this once (via phpMyAdmin, MySQL CLI, or your migration tool)

ALTER TABLE booking_online
ADD COLUMN service VARCHAR(255) NULL AFTER special_instructions;
