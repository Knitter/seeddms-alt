BEGIN;

ALTER TABLE tblSessions ADD COLUMN `splashmsg` TEXT DEFAULT '';

UPDATE tblVersion set major=4, minor=3, subminor=0;

COMMIT;

