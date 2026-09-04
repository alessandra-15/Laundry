-- Migration to simplify status column
-- Change status enum from ('Pending','Processing','Ready','Completed','Cancelled')
-- to ('Active','Inactive')

ALTER TABLE booking_online MODIFY COLUMN status ENUM('Active','Inactive') DEFAULT 'Active';

-- This will automatically keep existing 'Completed' records as they are, 
-- but from now on only Active/Inactive will be used
-- If you want to clean up old data:
-- UPDATE booking_online SET status = 'Inactive' WHERE status IN ('Pending','Processing','Ready','Completed','Cancelled');
