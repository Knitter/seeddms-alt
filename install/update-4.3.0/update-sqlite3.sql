BEGIN;

ALTER TABLE tblSessions ADD COLUMN `flashmsg` TEXT DEFAULT '';

UPDATE tblVersion set major=4, minor=3, subminor=0;

COMMIT;

