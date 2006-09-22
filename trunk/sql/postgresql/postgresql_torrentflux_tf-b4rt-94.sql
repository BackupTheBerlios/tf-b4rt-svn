-- -----------------------------------------------------------------------------
-- $Id$
-- -----------------------------------------------------------------------------
--
-- PostgreSQL-File for 'Torrentflux-2.1-b4rt-94'
--
-- This Stuff is provided 'as-is'. In no way will the author be held
-- liable for any damages to your soft- or hardware from this.
-- -----------------------------------------------------------------------------

--
-- begin transaction
--
BEGIN;

--
-- Sequences for table tf_cookies
--
CREATE SEQUENCE tf_cookies_cid_seq;

--
-- tf_cookies
--
CREATE TABLE tf_cookies (
  cid INT4 DEFAULT nextval('tf_cookies_cid_seq'),
  uid INT4 NOT NULL DEFAULT '0',
  host VARCHAR(255) DEFAULT NULL,
  data VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (cid)
);

--
-- Sequences for table tf_links
--
CREATE SEQUENCE tf_links_lid_seq;

--
-- tf_links
--
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
-- Sequences for table tf_log
--
CREATE SEQUENCE tf_log_cid_seq;

--
-- tf_log
--
CREATE TABLE tf_log (
  cid INT4 DEFAULT nextval('tf_log_cid_seq'),
  user_id VARCHAR(32) NOT NULL DEFAULT '',
  file VARCHAR(200) NOT NULL DEFAULT '',
  action VARCHAR(200) NOT NULL DEFAULT '',
  ip VARCHAR(15) NOT NULL DEFAULT '',
  ip_resolved VARCHAR(200) NOT NULL DEFAULT '',
  user_agent VARCHAR(200) NOT NULL DEFAULT '',
  time VARCHAR(14) NOT NULL DEFAULT '0',
  PRIMARY KEY (cid)
);

--
-- Sequences for table tf_messages
--
CREATE SEQUENCE tf_messages_mid_seq;

--
-- tf_messages
--
CREATE TABLE tf_messages (
  mid INT4 DEFAULT nextval('tf_messages_mid_seq'),
  to_user VARCHAR(32) NOT NULL DEFAULT '',
  from_user VARCHAR(32) NOT NULL DEFAULT '',
  message TEXT,
  IsNew INT4 DEFAULT NULL,
  ip VARCHAR(15) NOT NULL DEFAULT '',
  time VARCHAR(14) NOT NULL DEFAULT '0',
  force_read INT2 DEFAULT '0',
  PRIMARY KEY (mid)
);

--
-- Sequences for table tf_rss
--
CREATE SEQUENCE tf_rss_rid_seq;

--
-- tf_rss
--
CREATE TABLE tf_rss (
  rid INT4 DEFAULT nextval('tf_rss_rid_seq'),
  url VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (rid)
);

--
-- tf_settings
--
CREATE TABLE tf_settings (
  tf_key VARCHAR(255) NOT NULL DEFAULT '',
  tf_value TEXT DEFAULT '' NOT NULL,
  PRIMARY KEY (tf_key)
);

INSERT INTO tf_settings VALUES ('path','/usr/local/torrent/');
INSERT INTO tf_settings VALUES ('btphpbin','/var/www/TF_BitTornado/btphptornado.py');
INSERT INTO tf_settings VALUES ('btshowmetainfo','/var/www/TF_BitTornado/btshowmetainfo.py');
INSERT INTO tf_settings VALUES ('advanced_start','1');
INSERT INTO tf_settings VALUES ('max_upload_rate','10');
INSERT INTO tf_settings VALUES ('max_download_rate','0');
INSERT INTO tf_settings VALUES ('max_uploads','4');
INSERT INTO tf_settings VALUES ('minport','49160');
INSERT INTO tf_settings VALUES ('maxport','49300');
INSERT INTO tf_settings VALUES ('rerequest_interval','1800');
INSERT INTO tf_settings VALUES ('cmd_options','');
INSERT INTO tf_settings VALUES ('enable_search','1');
INSERT INTO tf_settings VALUES ('enable_file_download','1');
INSERT INTO tf_settings VALUES ('enable_view_nfo','1');
INSERT INTO tf_settings VALUES ('package_type','zip');
INSERT INTO tf_settings VALUES ('show_server_load','1');
INSERT INTO tf_settings VALUES ('loadavg_path','/proc/loadavg');
INSERT INTO tf_settings VALUES ('days_to_keep','30');
INSERT INTO tf_settings VALUES ('minutes_to_keep','3');
INSERT INTO tf_settings VALUES ('rss_cache_min','20');
INSERT INTO tf_settings VALUES ('page_refresh','60');
INSERT INTO tf_settings VALUES ('default_theme','matrix');
INSERT INTO tf_settings VALUES ('default_language','lang-english.php');
INSERT INTO tf_settings VALUES ('debug_sql','1');
INSERT INTO tf_settings VALUES ('torrent_dies_when_done','False');
INSERT INTO tf_settings VALUES ('sharekill','0');
INSERT INTO tf_settings VALUES ('tfQManager','/var/www/TF_BitTornado/tfQManager.py');
INSERT INTO tf_settings VALUES ('AllowQueing','0');
INSERT INTO tf_settings VALUES ('maxServerThreads','5');
INSERT INTO tf_settings VALUES ('maxUserThreads','2');
INSERT INTO tf_settings VALUES ('sleepInterval','10');
INSERT INTO tf_settings VALUES ('debugTorrents','0');
INSERT INTO tf_settings VALUES ('pythonCmd','/usr/bin/python');
INSERT INTO tf_settings VALUES ('searchEngine','TorrentSpy');
INSERT INTO tf_settings VALUES ('TorrentSpyGenreFilter','a:1:{i:0;s:0:\"\";}');
INSERT INTO tf_settings VALUES ('TorrentBoxGenreFilter','a:1:{i:0;s:0:\"\";}');
INSERT INTO tf_settings VALUES ('TorrentPortalGenreFilter','a:1:{i:0;s:0:\"\";}');
INSERT INTO tf_settings VALUES ('enable_maketorrent','1');
INSERT INTO tf_settings VALUES ('btmakemetafile','/var/www/TF_BitTornado/btmakemetafile.py');
INSERT INTO tf_settings VALUES ('enable_torrent_download','1');
INSERT INTO tf_settings VALUES ('enable_file_priority','1');
INSERT INTO tf_settings VALUES ('continue','configSettings');
INSERT INTO tf_settings VALUES ('searchEngineLinks','a:5:{s:7:\"isoHunt\";s:11:\"isohunt.com\";s:7:\"NewNova\";s:11:\"newnova.org\";s:10:\"TorrentBox\";s:14:\"torrentbox.com\";s:13:\"TorrentPortal\";s:17:\"torrentportal.com\";s:10:\"TorrentSpy\";s:14:\"torrentspy.com\";}');
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
-- Sequences for table tf_users
--
CREATE SEQUENCE tf_users_uid_seq;

--
-- tf_users
--
CREATE TABLE tf_users (
  uid INT4 DEFAULT nextval('tf_users_uid_seq'),
  user_id VARCHAR(32) NOT NULL DEFAULT '',
  password VARCHAR(34) NOT NULL DEFAULT '',
  hits INT4 NOT NULL DEFAULT '0',
  last_visit VARCHAR(14) NOT NULL DEFAULT '0',
  time_created VARCHAR(14) NOT NULL DEFAULT '0',
  user_level INT2 NOT NULL DEFAULT '0',
  hide_offline INT2 NOT NULL DEFAULT '0',
  theme VARCHAR(100) NOT NULL DEFAULT 'mint',
  language_file VARCHAR(60) DEFAULT 'lang-english.php',
  PRIMARY KEY (uid)
);

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
  CHECK (rate>=0),
  CHECK (drate>=0),
  CHECK (maxuploads>=0),
  CHECK (superseeder>=0),
  CHECK (sharekill>=0),
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
  user VARCHAR(32) NOT NULL DEFAULT '',
  DATE DATE NOT NULL DEFAULT '0001-01-01',
  download INT8 NOT NULL DEFAULT '0',
  upload INT8 NOT NULL DEFAULT '0',
  PRIMARY KEY (user,date)
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
-- Sequences for table tf_users
--
SELECT SETVAL('tf_users_uid_seq',(select case when max(uid)>0 then max(uid)+1 else 1 end from tf_users));

--
-- Sequences for table tf_messages
--
SELECT SETVAL('tf_messages_mid_seq',(select case when max(mid)>0 then max(mid)+1 else 1 end from tf_messages));

--
-- Sequences for table tf_cookies
--
SELECT SETVAL('tf_cookies_cid_seq',(select case when max(cid)>0 then max(cid)+1 else 1 end from tf_cookies));

--
-- Sequences for table tf_rss
--
SELECT SETVAL('tf_rss_rid_seq',(select case when max(rid)>0 then max(rid)+1 else 1 end from tf_rss));

--
-- Sequences for table tf_links
--
SELECT SETVAL('tf_links_lid_seq',(select case when max(lid)>0 then max(lid)+1 else 1 end from tf_links));

--
-- Sequences for table tf_log
--
SELECT SETVAL('tf_log_cid_seq',(select case when max(cid)>0 then max(cid)+1 else 1 end from tf_log));

--
-- commit
--
COMMIT;
