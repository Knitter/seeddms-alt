START TRANSACTION;

ALTER TABLE tblUsers ADD COLUMN `homefolder` INTEGER DEFAULT 0;

UPDATE tblVersion set major=5, minor=0, subminor=0;

COMMIT;

