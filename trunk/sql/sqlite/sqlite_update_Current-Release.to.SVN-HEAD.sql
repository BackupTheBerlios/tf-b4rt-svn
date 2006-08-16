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

CREATE TABLE tf_dlprofiles (
  id INTEGER PRIMARY KEY,
  user_id VARCHAR(32) NOT NULL,
  title VARCHAR(32) NOT NULL,
  minport INT( 11 ) NOT NULL,
  maxport INT( 11 ) NOT NULL,
  maxcons INT( 11 ) NOT NULL,
  rerequest_interval INT( 11 ) NOT NULL,
  max_upload_rate INT( 11 ) NOT NULL,
  max_uploads INT( 11 ) NOT NULL,
  max_download_rate INT( 11 ) NOT NULL,
  dont_stop INT( 11 ) NOT NULL,
  sharekill INT( 11 ) NOT NULL,
  btclient VARCHAR(32) NOT NULL
) ;

--
-- updates
--
UPDATE tf_settings SET tf_value = 'old_style_themes/matrix' WHERE tf_key = 'default_theme';

--
-- inserts
--
INSERT INTO tf_settings VALUES ('enable_btclient_chooser','1');
INSERT INTO tf_settings VALUES ('enable_transfer_profile','0');
INSERT INTO tf_settings VALUES ('transfer_profile_level','2');
INSERT INTO tf_settings VALUES ('tfqmgr_loglevel','0');
INSERT INTO tf_settings VALUES ('Qmgr_loglevel','0');
INSERT INTO tf_settings VALUES ('downloadhosts','0');
INSERT INTO tf_settings VALUES ('pagetitle','torrentflux-b4rt');
INSERT INTO tf_settings VALUES ('fluxd_loglevel','0');
INSERT INTO tf_settings VALUES ('fluxd_path', '/var/www/fluxd');
INSERT INTO tf_settings VALUES ('fluxd_path_fluxcli', '/var/www');
INSERT INTO tf_settings VALUES ('fluxd_Qmgr_enabled','0');
INSERT INTO tf_settings VALUES ('fluxd_Fluxinet_enabled','0');
INSERT INTO tf_settings VALUES ('fluxd_Watch_enabled','0');
INSERT INTO tf_settings VALUES ('fluxd_Clientmaint_enabled','0');
INSERT INTO tf_settings VALUES ('fluxd_Trigger_enabled','0');
INSERT INTO tf_settings VALUES ('fluxd_Qmgr_maxTotalTorrents','5');
INSERT INTO tf_settings VALUES ('fluxd_Qmgr_maxUserTorrents','2');
INSERT INTO tf_settings VALUES ('fluxd_Fluxinet_port','3150');
INSERT INTO tf_settings VALUES ('fluxd_Watch_jobs','admin:/usr/local/torrent/.watch/admin;fluxuser:/usr/local/torrent/.watch/fluxuser');
INSERT INTO tf_settings VALUES ('fluxd_Clientmaint_interval','600');
INSERT INTO tf_settings VALUES ('with_profiles','1');

--
-- commit
--
COMMIT;
