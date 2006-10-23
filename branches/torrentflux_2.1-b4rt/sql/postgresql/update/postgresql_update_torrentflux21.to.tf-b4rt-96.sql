-- -----------------------------------------------------------------------------
-- $Id$
-- -----------------------------------------------------------------------------
--
-- PostgreSQL-Update-File for 'Torrentflux-2.1-b4rt-96'.
-- Updates a 'Torrentflux 2.1 Final' Database to a 'Torrentflux 2.1-b4rt-96'.
--
-- This Stuff is provided 'as-is'. In no way will the author be held
-- liable for any damages to your soft- or hardware from this.
-- -----------------------------------------------------------------------------

--
-- begin transaction
--
BEGIN;

--
-- tf_links
--
DROP TABLE tf_links;

CREATE TABLE tf_links (
  lid INT4 DEFAULT nextval('tf_links_lid_seq'),
  url VARCHAR(255) NOT NULL DEFAULT '',
  sitename VARCHAR(255) NOT NULL DEFAULT 'Old Link',
  sort_order INT2  DEFAULT '0',
  PRIMARY KEY (lid),
  CHECK (sort_order>=0)
);

INSERT INTO tf_links VALUES ('0','http://tf-b4rt.berlios.de/','Home','0');

--
-- tf_torrents
--
CREATE TABLE tf_torrents (
  torrent VARCHAR(255) NOT NULL DEFAULT '',
  running INT2 NOT NULL DEFAULT '0',
  rate INT2 NOT NULL DEFAULT '0',
  drate INT2 NOT NULL DEFAULT '0',
  maxuploads INT2 NOT NULL DEFAULT '0',
  superseeder INT2 NOT NULL DEFAULT '0',
  runtime VARCHAR(5) NOT NULL DEFAULT 'False',
  sharekill INT2 NOT NULL DEFAULT '0',
  minport INT2 NOT NULL DEFAULT '0',
  maxport INT2 NOT NULL DEFAULT '0',
  maxcons INT2 NOT NULL DEFAULT '0',
  savepath VARCHAR(255) NOT NULL DEFAULT '',
  btclient VARCHAR(32) NOT NULL DEFAULT 'tornado',
  hash VARCHAR(40) DEFAULT '' NOT NULL,
  PRIMARY KEY (torrent),
  CHECK (running>=0),
  CHECK (maxuploads>=0),
  CHECK (minport>=0),
  CHECK (maxport>=0),
  CHECK (maxcons>=0)
);

--
-- tf_torrent_totals
--
CREATE TABLE tf_torrent_totals (
  tid VARCHAR(40) NOT NULL DEFAULT '',
  uptotal INT8 NOT NULL DEFAULT '0',
  downtotal INT8 NOT NULL DEFAULT '0',
  PRIMARY KEY (tid)
);

--
-- tf_xfer
--
CREATE TABLE tf_xfer (
  user_id VARCHAR(32) NOT NULL DEFAULT '',
  date DATE NOT NULL DEFAULT '0001-01-01',
  download INT8 NOT NULL DEFAULT '0',
  upload INT8 NOT NULL DEFAULT '0'
);

--
-- tf_settings_user
--
CREATE TABLE tf_settings_user (
  uid INT4 NOT NULL,
  tf_key VARCHAR(255) NOT NULL DEFAULT '',
  tf_value TEXT DEFAULT '' NOT NULL
);

--
-- extra inserts + updates
--
UPDATE tf_settings SET tf_value = '1' WHERE tf_key = 'advanced_start';
UPDATE tf_settings SET tf_value = '' WHERE tf_key = 'cmd_options';
INSERT INTO tf_settings VALUES ('maxcons','40');
INSERT INTO tf_settings VALUES ('enable_mrtg','1');
INSERT INTO tf_settings VALUES ('enable_rar','1');
INSERT INTO tf_settings VALUES ('showdirtree','1');
INSERT INTO tf_settings VALUES ('maxdepth','0');
INSERT INTO tf_settings VALUES ('enable_multiops','1');
INSERT INTO tf_settings VALUES ('enable_wget','1');
INSERT INTO tf_settings VALUES ('enable_dirstats','1');
INSERT INTO tf_settings VALUES ('enable_multiupload','1');
INSERT INTO tf_settings VALUES ('enable_xfer','1');
INSERT INTO tf_settings VALUES ('enable_public_xfer','1');
INSERT INTO tf_settings VALUES ('enable_sfvcheck','1');
INSERT INTO tf_settings VALUES ('bin_grep','/bin/grep');
INSERT INTO tf_settings VALUES ('bin_cat','/bin/cat');
INSERT INTO tf_settings VALUES ('bin_netstat','/bin/netstat');
INSERT INTO tf_settings VALUES ('bin_php','/usr/bin/php');
INSERT INTO tf_settings VALUES ('bin_awk','/usr/bin/awk');
INSERT INTO tf_settings VALUES ('bin_du','/usr/bin/du');
INSERT INTO tf_settings VALUES ('bin_wget','/usr/bin/wget');
INSERT INTO tf_settings VALUES ('bin_unrar','/usr/bin/unrar');
INSERT INTO tf_settings VALUES ('bin_unzip','/usr/bin/unzip');
INSERT INTO tf_settings VALUES ('bin_cksfv','/usr/bin/cksfv');
INSERT INTO tf_settings VALUES ('btclient','tornado');
INSERT INTO tf_settings VALUES ('btclient_tornado_bin','/var/www/TF_BitTornado/btphptornado.py');
INSERT INTO tf_settings VALUES ('btclient_tornado_options','--upnp_nat_access 0');
INSERT INTO tf_settings VALUES ('btclient_transmission_bin','/usr/local/bin/transmissioncli');
INSERT INTO tf_settings VALUES ('btclient_transmission_options','');
INSERT INTO tf_settings VALUES ('metainfoclient','btshowmetainfo.py');
INSERT INTO tf_settings VALUES ('enable_restrictivetview','1');
INSERT INTO tf_settings VALUES ('queuemanager','tfqmgr');
INSERT INTO tf_settings VALUES ('perlCmd','/usr/bin/perl');
INSERT INTO tf_settings VALUES ('tfqmgr_path','/var/www/tfqmgr');
INSERT INTO tf_settings VALUES ('tfqmgr_path_fluxcli','/var/www');
INSERT INTO tf_settings VALUES ('tfqmgr_limit_global','5');
INSERT INTO tf_settings VALUES ('tfqmgr_limit_user','2');
INSERT INTO tf_settings VALUES ('ui_displayfluxlink','1');
INSERT INTO tf_settings VALUES ('ui_dim_main_w','780');
INSERT INTO tf_settings VALUES ('ui_dim_details_w','450');
INSERT INTO tf_settings VALUES ('ui_dim_details_h','290');
INSERT INTO tf_settings VALUES ('ui_dim_superadmin_w','800');
INSERT INTO tf_settings VALUES ('ui_dim_superadmin_h','600');
INSERT INTO tf_settings VALUES ('enable_bigboldwarning','1');
INSERT INTO tf_settings VALUES ('enable_goodlookstats','1');
INSERT INTO tf_settings VALUES ('ui_displaylinks','1');
INSERT INTO tf_settings VALUES ('ui_displayusers','1');
INSERT INTO tf_settings VALUES ('xfer_total','0');
INSERT INTO tf_settings VALUES ('xfer_month','0');
INSERT INTO tf_settings VALUES ('xfer_week','0');
INSERT INTO tf_settings VALUES ('xfer_day','0');
INSERT INTO tf_settings VALUES ('enable_bulkops','1');
INSERT INTO tf_settings VALUES ('week_start','Monday');
INSERT INTO tf_settings VALUES ('month_start','1');
INSERT INTO tf_settings VALUES ('hack_multiupload_rows','6');
INSERT INTO tf_settings VALUES ('hack_goodlookstats_settings','63');
INSERT INTO tf_settings VALUES ('ui_indexrefresh','1');
INSERT INTO tf_settings VALUES ('bin_fstat','/usr/bin/fstat');
INSERT INTO tf_settings VALUES ('enable_dereferrer','1');
INSERT INTO tf_settings VALUES ('Qmgr_path','/var/www/Qmgr');
INSERT INTO tf_settings VALUES ('Qmgr_maxUserTorrents','2');
INSERT INTO tf_settings VALUES ('Qmgr_maxTotalTorrents','5');
INSERT INTO tf_settings VALUES ('Qmgr_perl','/usr/bin/perl');
INSERT INTO tf_settings VALUES ('Qmgr_fluxcli','/var/www');
INSERT INTO tf_settings VALUES ('Qmgr_host','localhost');
INSERT INTO tf_settings VALUES ('Qmgr_port','2606');
INSERT INTO tf_settings VALUES ('auth_type','0');
INSERT INTO tf_settings VALUES ('index_page_connections','1');
INSERT INTO tf_settings VALUES ('index_page_stats','1');
INSERT INTO tf_settings VALUES ('index_page_sortorder','dd');
INSERT INTO tf_settings VALUES ('index_page','b4rt');
INSERT INTO tf_settings VALUES ('index_page_settings','1266');
INSERT INTO tf_settings VALUES ('enable_move','0');
INSERT INTO tf_settings VALUES ('enable_rename','1');
INSERT INTO tf_settings VALUES ('move_paths','');
INSERT INTO tf_settings VALUES ('bin_sockstat','/usr/bin/sockstat');
INSERT INTO tf_settings VALUES ('nice_adjust','0');
INSERT INTO tf_settings VALUES ('xfer_realtime','1');
INSERT INTO tf_settings VALUES ('skiphashcheck','0');
INSERT INTO tf_settings VALUES ('enable_umask','0');
INSERT INTO tf_settings VALUES ('enable_sorttable','1');
INSERT INTO tf_settings VALUES ('drivespacebar','xfer');

--
-- Sequences for table tf_links
--
SELECT SETVAL('tf_links_lid_seq',(select case when max(lid)>0 then max(lid)+1 else 1 end from tf_links));

--
-- commit
--
COMMIT;
