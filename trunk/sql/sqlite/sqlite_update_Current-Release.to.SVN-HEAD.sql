-- -----------------------------------------------------------------------------
-- $Id$
-- -----------------------------------------------------------------------------

--
-- begin transaction
--
BEGIN TRANSACTION;

--
-- inserts
--
INSERT INTO tf_settings VALUES ('tfqmgr_loglevel','0');
INSERT INTO tf_settings VALUES ('Qmgr_loglevel','0');


--
-- commit
--
COMMIT;
