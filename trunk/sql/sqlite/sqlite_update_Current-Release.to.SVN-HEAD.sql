-- -----------------------------------------------------------------------------
-- $Id$
-- -----------------------------------------------------------------------------

--
-- begin transaction
--
BEGIN TRANSACTION;

--
-- tf_trprofiles
--
CREATE TABLE tf_trprofiles (
  id INTEGER PRIMARY KEY,
  name VARCHAR(255) NOT NULL default '',
  owner INTEGER(10) NOT NULL default '0',
  public INTEGER(1) NOT NULL default '0',
  rate INTEGER(4) NOT NULL default '0',
  drate INTEGER(4) NOT NULL default '0',
  maxuploads INTEGER(3) NOT NULL default '0',
  superseeder INTEGER(1) NOT NULL default '0',
  runtime VARCHAR(5) NOT NULL default 'False',
  sharekill INTEGER(4) NOT NULL default '0',
  minport INTEGER(5) NOT NULL default '0',
  maxport INTEGER(5) NOT NULL default '0',
  maxcons INTEGER(4) NOT NULL default '0',
  rerequest INTEGER(8) NOT NULL default '0'
) ;

--
-- inserts
--
INSERT INTO tf_settings VALUES ('tfqmgr_loglevel','0');
INSERT INTO tf_settings VALUES ('Qmgr_loglevel','0');


--
-- commit
--
COMMIT;
