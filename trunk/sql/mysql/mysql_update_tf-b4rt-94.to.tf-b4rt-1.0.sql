-- -----------------------------------------------------------------------------
-- $Id$
-- -----------------------------------------------------------------------------
--
-- This Stuff is provided 'as-is'. In no way will the authors be held
-- liable for any damages to your soft- or hardware from this.
-- -----------------------------------------------------------------------------

--
-- tf_trprofiles
--
CREATE TABLE tf_trprofiles (
  id MEDIUMINT(8) NOT NULL auto_increment,
  name VARCHAR(255) NOT NULL default '',
  owner INT(10) NOT NULL default '0',
  public ENUM('0','1') NOT NULL default '0',
  rate SMALLINT(4) unsigned NOT NULL default '0',
  drate SMALLINT(4) unsigned NOT NULL default '0',
  maxuploads TINYINT(3) unsigned NOT NULL default '0',
  superseeder ENUM('0','1') NOT NULL default '0',
  runtime ENUM('True','False') NOT NULL default 'False',
  sharekill SMALLINT(4) unsigned NOT NULL default '0',
  minport SMALLINT(5) unsigned NOT NULL default '0',
  maxport SMALLINT(5) unsigned NOT NULL default '0',
  maxcons SMALLINT(4) unsigned NOT NULL default '0',
  rerequest MEDIUMINT(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

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
INSERT INTO tf_settings VALUES ('transfer_customize_settings','1');
