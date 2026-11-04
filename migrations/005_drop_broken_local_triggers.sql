-- 005_drop_broken_local_triggers.sql
-- LOCAL (XAMPP) DATABASE ONLY. The live InfinityFree database has no triggers, so
-- it does not need this; running it there does nothing.
--
-- These triggers make the local site behave differently from the live one:
--   after_booking_update              writes a NULL admin_id into system_logs, so every
--                                     walk-in status change fails with
--                                     "Column 'admin_id' cannot be null"
--   after_booking_complete_inventory  writes to inventory_logs, which does not exist, so
--                                     marking a walk-in Completed always fails
--   after_online_booking_insert       adds an empty Pending GCash payment to every new
--                                     booking, which blocks the customer's real payment
--                                     with "You already have a pending GCash payment"
--
-- HOW TO RUN: phpMyAdmin -> select laundry_db -> SQL tab -> paste this file -> Go.

DROP TRIGGER IF EXISTS after_booking_update;
DROP TRIGGER IF EXISTS after_booking_complete_inventory;
DROP TRIGGER IF EXISTS after_online_booking_insert;
