<?php

/* $Id$ */

/*******************************************************************************

 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html

*******************************************************************************/

// install-functions
if (is_file('inc/functions/functions.install.php'))
	require_once('inc/functions/functions.install.php');
else
	die("Fatal Error. inc/functions/functions.install.php is missing.");

// defines
define('_NAME', 'torrentflux-b4rt');
define('_UPGRADE_FROM', 'v95');
define('_UPGRADE_TO', '1.0');
define('_DEFAULT_PATH', '/usr/local/torrent/');
define('_REVISION', array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$'))))));
define('_VERSION_LOCAL', '.version');
define('_VERSION_THIS', trim(getDataFromFile(_VERSION_LOCAL)));
define('_TITLE', _NAME.' '._VERSION_THIS.' - Upgrade '._UPGRADE_FROM.' to '._UPGRADE_TO);
define('_DIR', dirname($_SERVER["SCRIPT_FILENAME"])."/");
define('_FILE_DBCONF', 'inc/config/config.db.php');
define('_FILE_THIS', $_SERVER['SCRIPT_NAME']);

// Database-Types
$databaseTypes = array();
$databaseTypes['mysql'] = 'mysql_connect';
$databaseTypes['sqlite'] = 'sqlite_open';
$databaseTypes['postgres'] = 'pg_connect';

// sql-queries
$queries = array();

// -----------------------------------------------------------------------------
// SQL : common
// -----------------------------------------------------------------------------
$cdb = 'common';

// sql-queries : Data
$cqt = 'data';
$queries[$cqt][$cdb] = array();
// updates + deletes
array_push($queries[$cqt][$cdb], "UPDATE tf_users SET theme = 'default'");
array_push($queries[$cqt][$cdb], "DELETE FROM tf_settings_user");
// tf_settings
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('advanced_start','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('max_upload_rate','10')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('max_download_rate','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('max_uploads','4')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('minport','49160')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('maxport','49300')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('rerequest_interval','1800')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_search','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('show_server_load','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('loadavg_path','/proc/loadavg')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('days_to_keep','30')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('minutes_to_keep','3')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('rss_cache_min','20')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('page_refresh','60')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('default_theme','default')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('default_language','lang-english.php')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('debug_sql','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('torrent_dies_when_done','False')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('sharekill','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('pythonCmd','/usr/bin/python')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('searchEngine','TorrentSpy')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('TorrentSpyGenreFilter','a:1:{i:0;s:0:\"\";}')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('TorrentBoxGenreFilter','a:1:{i:0;s:0:\"\";}')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('TorrentPortalGenreFilter','a:1:{i:0;s:0:\"\";}')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_torrent_download','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_file_priority','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('searchEngineLinks','a:5:{s:7:\"isoHunt\";s:11:\"isohunt.com\";s:7:\"NewNova\";s:11:\"newnova.org\";s:10:\"TorrentBox\";s:14:\"torrentbox.com\";s:13:\"TorrentPortal\";s:17:\"torrentportal.com\";s:10:\"TorrentSpy\";s:14:\"torrentspy.com\";}')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('maxcons','40')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_mrtg','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('showdirtree','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('maxdepth','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_multiops','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_wget','2')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_multiupload','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_xfer','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_public_xfer','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_grep','/bin/grep')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_netstat','/bin/netstat')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_php','/usr/bin/php')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_awk','/usr/bin/awk')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_du','/usr/bin/du')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_wget','/usr/bin/wget')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_unrar','/usr/bin/unrar')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_unzip','/usr/bin/unzip')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_cksfv','/usr/bin/cksfv')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('btclient','tornado')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('btclient_tornado_options','')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('btclient_transmission_bin','/usr/local/bin/transmissioncli')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('btclient_transmission_options','')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('metainfoclient','btshowmetainfo.py')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_restrictivetview','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('perlCmd','/usr/bin/perl')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('ui_displayfluxlink','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('ui_dim_main_w','900')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_bigboldwarning','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_goodlookstats','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('ui_displaylinks','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('ui_displayusers','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('xfer_total','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('xfer_month','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('xfer_week','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('xfer_day','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_bulkops','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('week_start','Monday')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('month_start','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('hack_multiupload_rows','6')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('hack_goodlookstats_settings','63')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_dereferrer','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('auth_type','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('index_page_connections','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('index_page_stats','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('index_page_sortorder','dd')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('index_page_settings','1266')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bin_sockstat','/usr/bin/sockstat')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('nice_adjust','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('xfer_realtime','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('skiphashcheck','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_umask','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_sorttable','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('drivespacebar','tf')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('docroot','/var/www/')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_index_ajax_update_silent','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_index_ajax_update_users','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('wget_ftp_pasv','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('wget_limit_retries','3')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('wget_limit_rate','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_index_ajax_update_title','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_index_ajax_update_list','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_index_meta_refresh','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_index_ajax_update','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('index_ajax_update','10')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('details_type','ajax')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('details_update','5')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('auth_basic_realm','torrentflux-b4rt')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('servermon_update','5')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_home_dirs','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('path_incoming','incoming')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_tmpl_cache','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('btclient_mainline_options','')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bandwidthbar','tf')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('display_seeding_time','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('ui_displaybandwidthbars','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bandwidth_down','10240')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('bandwidth_up','10240')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('webapp_locked','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_btclient_chooser','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_transfer_profile','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('transfer_profile_level','2')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('transfer_customize_settings','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('downloadhosts','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('pagetitle','torrentflux-b4rt')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_dbmode','php')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_loglevel','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Fluxinet_enabled','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Qmgr_enabled','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Rssad_enabled','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Watch_enabled','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Trigger_enabled','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Clientmaint_enabled','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Fluxinet_port','3150')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Qmgr_interval','15')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Qmgr_maxTotalTorrents','5')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Qmgr_maxUserTorrents','2')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Rssad_interval','1800')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Rssad_jobs','')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Watch_interval','120')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Watch_jobs','')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Trigger_interval','600')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('fluxd_Clientmaint_interval','600')");
// tf_settings_dir
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('dir_public_read','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('dir_public_write','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('dir_enable_chmod','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('enable_dirstats','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('enable_maketorrent','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('dir_maketorrent_default','tornado')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('enable_file_download','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('enable_view_nfo','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('package_type','tar')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('enable_sfvcheck','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('enable_rar','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('enable_move','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('enable_rename','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('move_paths','')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_dir VALUES ('dir_restricted','lost+found:CVS:Temporary Items:Network Trash Folder:TheVolumeSettingsFolder')");
// tf_settings_stats
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_enable_public','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_show_usage','1')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_deflate_level','9')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_txt_delim',';')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_default_header','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_default_type','all')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_default_format','xml')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_default_attach','0')");
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings_stats VALUES ('stats_default_compress','0')");
// tf_links
array_push($queries[$cqt][$cdb], "INSERT INTO tf_links VALUES (NULL,'http://tf-b4rt.berlios.de/','tf-b4rt','0')");

// -----------------------------------------------------------------------------
// SQL : mysql
// -----------------------------------------------------------------------------
$cdb = 'mysql';

// sql-queries : Test
$cqt = 'test';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_test (
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL,
  PRIMARY KEY (tf_key)
) TYPE=MyISAM");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

// sql-queries : Create
$cqt = 'create';
$queries[$cqt][$cdb] = array();
// tf_trprofiles
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_trprofiles (
  id MEDIUMINT(8) NOT NULL auto_increment,
  name VARCHAR(255) NOT NULL default '',
  owner INT(10) NOT NULL default '0',
  public ENUM('0','1') NOT NULL default '0',
  rate SMALLINT(4) NOT NULL default '0',
  drate SMALLINT(4) NOT NULL default '0',
  maxuploads TINYINT(3) unsigned NOT NULL default '0',
  superseeder ENUM('0','1') NOT NULL default '0',
  runtime ENUM('True','False') NOT NULL default 'False',
  sharekill SMALLINT(4) unsigned NOT NULL default '0',
  minport SMALLINT(5) unsigned NOT NULL default '0',
  maxport SMALLINT(5) unsigned NOT NULL default '0',
  maxcons SMALLINT(4) unsigned NOT NULL default '0',
  rerequest MEDIUMINT(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM");
// tf_settings_dir
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_dir (
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL,
  PRIMARY KEY  (tf_key)
) TYPE=MyISAM");
// tf_settings_stats
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_stats (
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL,
  PRIMARY KEY  (tf_key)
) TYPE=MyISAM");
// ALTER
array_push($queries[$cqt][$cdb], "ALTER TABLE tf_torrents CHANGE rate rate SMALLINT(4) DEFAULT '0' NOT NULL");
array_push($queries[$cqt][$cdb], "ALTER TABLE tf_torrents CHANGE drate drate SMALLINT(4) DEFAULT '0' NOT NULL");
array_push($queries[$cqt][$cdb], "ALTER TABLE tf_torrents ADD datapath VARCHAR(255) DEFAULT '' NOT NULL");
array_push($queries[$cqt][$cdb], "ALTER TABLE tf_users ADD state TINYINT(1) DEFAULT '1' NOT NULL");

// sql-queries : Data
$cqt = 'data';
$queries[$cqt][$cdb] = array();
foreach ($queries['data']['common'] as $dataQuery)
	array_push($queries[$cqt][$cdb], $dataQuery);

// -----------------------------------------------------------------------------
// SQL : sqlite
// -----------------------------------------------------------------------------
$cdb = 'sqlite';

// sql-queries : Test
$cqt = 'test';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_test (
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL,
  PRIMARY KEY (tf_key) )");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

// sql-queries : Create
$cqt = 'create';
$queries[$cqt][$cdb] = array();
// tf_trprofiles
array_push($queries[$cqt][$cdb], "
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
)");
// tf_settings_dir
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_dir (
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL,
  PRIMARY KEY  (tf_key)
)");
// tf_settings_stats
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_stats (
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL,
  PRIMARY KEY  (tf_key)
)");
// ALTER
array_push($queries[$cqt][$cdb], "ALTER TABLE tf_torrents ADD datapath VARCHAR(255) DEFAULT '' NOT NULL");
array_push($queries[$cqt][$cdb], "ALTER TABLE tf_users ADD state TINYINT(1) DEFAULT '1' NOT NULL");

// sql-queries : Data
$cqt = 'data';
$queries[$cqt][$cdb] = array();
foreach ($queries['data']['common'] as $dataQuery)
	array_push($queries[$cqt][$cdb], $dataQuery);

// -----------------------------------------------------------------------------
// SQL : postgres
// -----------------------------------------------------------------------------
$cdb = 'postgres';

// sql-queries : Test
$cqt = 'test';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_test (
  tf_key VARCHAR(255) NOT NULL DEFAULT '',
  tf_value TEXT DEFAULT '' NOT NULL,
  PRIMARY KEY (tf_key) )");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

// sql-queries : Create
$cqt = 'create';
$queries[$cqt][$cdb] = array();
// tf_trprofiles
array_push($queries[$cqt][$cdb], "CREATE SEQUENCE tf_trprofiles_id_seq");
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_trprofiles (
  id INT4 DEFAULT nextval('tf_trprofiles_id_seq'),
  name VARCHAR(255) NOT NULL DEFAULT '',
  owner INT4 NOT NULL DEFAULT '0',
  public INT2 NOT NULL DEFAULT '0',
  rate INT2  NOT NULL DEFAULT '0',
  drate INT2  NOT NULL DEFAULT '0',
  maxuploads INT2  NOT NULL DEFAULT '0',
  superseeder INT2 NOT NULL DEFAULT '0',
  runtime VARCHAR(5) NOT NULL DEFAULT 'False',
  sharekill INT2  NOT NULL DEFAULT '0',
  minport INT2 NOT NULL DEFAULT '0',
  maxport INT2 NOT NULL DEFAULT '0',
  maxcons INT2 NOT NULL DEFAULT '0',
  rerequest INT4 NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  CHECK (public>=0),
  CHECK (maxuploads>=0),
  CHECK (minport>=0),
  CHECK (maxport>=0),
  CHECK (maxcons>=0),
  CHECK (rerequest>=0)
)");
// tf_settings_dir
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_dir (
  tf_key VARCHAR(255) NOT NULL DEFAULT '',
  tf_value TEXT DEFAULT '' NOT NULL,
  PRIMARY KEY (tf_key)
)");
// tf_settings_stats
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_stats (
  tf_key VARCHAR(255) NOT NULL DEFAULT '',
  tf_value TEXT DEFAULT '' NOT NULL,
  PRIMARY KEY (tf_key)
)");
// ALTER
array_push($queries[$cqt][$cdb], "ALTER TABLE tf_torrents ADD datapath VARCHAR(255) NOT NULL DEFAULT ''");
array_push($queries[$cqt][$cdb], "ALTER TABLE tf_users ADD state INT2 NOT NULL DEFAULT '1'");

// sql-queries : Data
$cqt = 'data';
$queries[$cqt][$cdb] = array();
foreach ($queries['data']['common'] as $dataQuery)
	array_push($queries[$cqt][$cdb], $dataQuery);
// tf_links
array_push($queries[$cqt][$cdb], "INSERT INTO tf_links VALUES ('0','http://tf-b4rt.berlios.de/','tf-b4rt','0')");
// sequences
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_links_lid_seq',(select case when max(lid)>0 then max(lid)+1 else 1 end from tf_links))");
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_trprofiles_id_seq',(select case when max(id)>0 then max(id)+1 else 1 end from tf_trprofiles))");


// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// ob-start
if (@ob_get_level() == 0)
	@ob_start();

if (isset($_REQUEST["1"])) {                                                    // 1 - Database
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database</h2>");
	sendButton(11);
} elseif (isset($_REQUEST["11"])) {                                             // 11 - Database - type
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Type</h2>");
	send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
	send('<select name="db_type">');
	foreach ($databaseTypes as $databaseTypeName => $databaseTypeFunction) {
		$option = '<option value="'.$databaseTypeName.'"';
		if ((isset($_REQUEST["db_type"])) && ($_REQUEST["db_type"] == $databaseTypeName))
			$option .= ' selected';
		$option .= '>'.$databaseTypeName.'</option>';
		$option .= '</option>';
		send($option);
	}
	send('</select>');
	send('<input type="Hidden" name="12" value="">');
	send('<input type="submit" value="Continue">');
	send('</form>');
} elseif (isset($_REQUEST["12"])) {                                             // 12 - Database - type check
	if ((isset($_REQUEST["db_type"])) && ($databaseTypes[$_REQUEST["db_type"]] != "")) {
		$type = $_REQUEST["db_type"];
		sendHead(" - Database");
		send("<h1>"._TITLE."</h1>");
		send("<h2>Database - Type Check</h2>");
		if (function_exists($databaseTypes[$type])) {
			send('<font color="green"><strong>Ok</strong></font><br>');
			send('This PHP does support <em>'.$type.'</em>.<p>');
			send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
			send('<input type="Hidden" name="db_type" value="'.$type.'">');
			send('<input type="Hidden" name="13" value="">');
			send('<input type="submit" value="Continue">');
			send('</form>');
		} else {
			send('<font color="red"><strong>Error</strong></font><br>');
			send('This PHP does not support <em>'.$type.'</em>.<p>');
			send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
			send('<input type="Hidden" name="11" value="">');
			send('<input type="submit" value="Back">');
			send('</form>');
		}
	} else {
		header("location: setup.php?11");
		exit();
	}
} elseif (isset($_REQUEST["13"])) {                                             // 13 - Database - config
	$type = $_REQUEST["db_type"];
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Config - ".$type."</h2>");
	send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
	send('<table border="0">');
	// settings
	send('<tr><td colspan="2"><strong>Database Settings : </strong></td></tr>');
	switch ($type) {
		case "mysql":
		case "postgres":
			// host
			$line = '<tr><td>Host : </td>';
			$line .= '<td><input name="db_host" type="Text" maxlength="254" size="40" value="';
			if (isset($_REQUEST["db_host"]))
				$line .= $_REQUEST["db_host"];
			else
				$line .= 'localhost';
			$line .= '"></td></tr>';
			send($line);
			// name
			$line = '<tr><td>Name : </td>';
			$line .= '<td><input name="db_name" type="Text" maxlength="254" size="40" value="';
			if (isset($_REQUEST["db_name"]))
				$line .= $_REQUEST["db_name"];
			else
				$line .= 'torrentflux';
			$line .= '"></td></tr>';
			send($line);
			// user
			$line = '<tr><td>Username : </td>';
			$line .= '<td><input name="db_user" type="Text" maxlength="254" size="40" value="';
			if (isset($_REQUEST["db_user"]))
				$line .= $_REQUEST["db_user"];
			else
				$line .= 'root';
			$line .= '"></td></tr>';
			send($line);
			// pass
			$line = '<tr><td>Password : </td>';
			$line .= '<td><input name="db_pass" type="Password" maxlength="254" size="40"';
			if (isset($_REQUEST["db_pass"]))
				$line .= ' value="'.$_REQUEST["db_pass"].'">';
			else
				$line .= '>';
			$line .= '</td></tr>';
			send($line);
			//
			break;
		case "sqlite":
			// file
			$line = '<tr><td>Database-File : </td>';
			$line .= '<td><input name="db_host" type="Text" maxlength="254" size="40" value="';
			if (isset($_REQUEST["db_host"]))
				$line .= $_REQUEST["db_host"];
			$line .= '"></td></tr>';
			send($line);
	}
	// pcon
	$line = '<tr><td colspan="2">Persistent Connection :';
	$line .= '<input name="db_pcon" type="Checkbox" value="true"';
	if (isset($_REQUEST["db_pcon"]))
		$line .= ' checked">';
	else
		$line .= '>';
	$line .= '</td></tr>';
	send($line);
	send('</table>');
	send('<input type="Hidden" name="db_type" value="'.$type.'">');
	send('<input type="Hidden" name="14" value="">');
	send('<input type="submit" value="Continue">');
	send('</form>');
} elseif (isset($_REQUEST["14"])) {                                             // 14 - Database - test
	$type = $_REQUEST["db_type"];
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Test - ".$type."</h2>");
	$paramsOk = true;
	if (isset($_REQUEST["db_host"]))
		$host = $_REQUEST["db_host"];
	else
		$paramsOk = false;
	if (isset($_REQUEST["db_pcon"]))
		$pcon = "true";
	else
		$pcon = "false";
	switch ($type) {
		case "mysql":
		case "postgres":
			if (isset($_REQUEST["db_name"]))
				$name = $_REQUEST["db_name"];
			else
				$paramsOk = false;
			if (isset($_REQUEST["db_user"]))
				$user = $_REQUEST["db_user"];
			else
				$paramsOk = false;
			if (isset($_REQUEST["db_pass"]))
				$pass = $_REQUEST["db_pass"];
			else
				$paramsOk = false;
			break;
		case "sqlite":
			$name = "";
			$user = "";
			$pass = "";
	}
	$databaseTestOk = false;
	$databaseError = "";
	// test
	if ($paramsOk) {
		$dbCon = getAdoConnection($type, $host, $user, $pass, $name);
		if (!$dbCon) {
			$databaseTestOk = false;
			$databaseError = "cannot connect to database.";
		} else {
			send('<ul>');
			$databaseTestCount = 0;
			foreach ($queries['test'][$type] as $databaseTypeName => $databaseQuery) {
				send('<li><em>'.$databaseQuery.'</em> : ');
				$dbCon->Execute($databaseQuery);
				if ($dbCon->ErrorNo() == 0) {
					send('<font color="green">Ok</font></li>');
					$databaseTestCount++;
				} else { // damn there was an error
					send('<font color="red">Error</font></li>');
					// close ado-connection
					$dbCon->Close();
					break;
				}
			}
			if ($databaseTestCount == count($queries['test'][$type])) {
				$databaseTestOk = true;
			} else {
				$databaseTestOk = false;
			}
			send('</ul>');
		}
	} else {
		$databaseTestOk = false;
		$databaseError = "config error.";
	}
	// output
	if ($databaseTestOk) {
		// load path
		$tf_settings = loadSettings("tf_settings");
		if ($tf_settings !== false) {
			$oldpath = $tf_settings["path"];
			if (((strlen($oldpath) > 0)) && (substr($oldpath, -1 ) != "/"))
				$oldpath .= "/";
		} else {
			$oldpath = _DEFAULT_PATH;
		}
		// close ado-connection
		$dbCon->Close();
		send('<font color="green"><strong>Ok</strong></font><br>');
		send("<h2>Next : Write Config File</h2>");
		send("Please ensure this script can write to the dir <em>"._DIR."inc/config/</em><p>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<input type="Hidden" name="oldpath" value="'.$oldpath.'">');
		send('<input type="Hidden" name="db_type" value="'.$type.'">');
		send('<input type="Hidden" name="db_host" value="'.$host.'">');
		send('<input type="Hidden" name="db_name" value="'.$name.'">');
		send('<input type="Hidden" name="db_user" value="'.$user.'">');
		send('<input type="Hidden" name="db_pass" value="'.$pass.'">');
		send('<input type="Hidden" name="db_pcon" value="'.$pcon.'">');
		send('<input type="Hidden" name="15" value="">');
		send('<input type="submit" value="Continue">');
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send($databaseError."<p>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<input type="Hidden" name="db_type" value="'.$type.'">');
		send('<input type="Hidden" name="13" value="">');
		if (isset($_REQUEST["db_name"]))
			send('<input type="Hidden" name="db_host" value="'.$_REQUEST["db_host"].'">');
		if (isset($_REQUEST["db_name"]))
			send('<input type="Hidden" name="db_name" value="'.$_REQUEST["db_name"].'">');
		if (isset($_REQUEST["db_user"]))
			send('<input type="Hidden" name="db_user" value="'.$_REQUEST["db_user"].'">');
		if (isset($_REQUEST["db_pass"]))
			send('<input type="Hidden" name="db_pass" value="'.$_REQUEST["db_pass"].'">');
		if (isset($_REQUEST["db_pcon"]))
			send('<input type="Hidden" name="db_pcon" value="'.$_REQUEST["db_pcon"].'">');
		send('<input type="submit" value="Back">');
	}
	send('</form>');
} elseif (isset($_REQUEST["15"])) {                                             // 15 - Database - config-file
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Config-File</h2>");
	$oldpath = $_REQUEST["oldpath"];
	$type = $_REQUEST["db_type"];
	$host = $_REQUEST["db_host"];
	$name = $_REQUEST["db_name"];
	$user = $_REQUEST["db_user"];
	$pass = $_REQUEST["db_pass"];
	$pcon = $_REQUEST["db_pcon"];
	// write file
	$databaseConfWriteOk = false;
	$databaseConfWriteError = "";
	$databaseConfContent = "";
	writeDatabaseConfig($type, $host, $user, $pass, $name, $pcon);
	// output
	if ($databaseConfWriteOk) {
		send('<font color="green"><strong>Ok</strong></font><br>');
		send('database-config-file <em>'._DIR._FILE_DBCONF.'</em> written.');
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send($databaseConfWriteError."<p>");
		send('to perform this step manual paste the following content to the database-config-file <em>'._DIR._FILE_DBCONF.'</em> : <p>');
		send('<textarea cols="81" rows="33">'.$databaseConfContent.'</textarea>');
		send("<p>Note : You must write this file before you can continue !");
	}
	send("<h2>Next : Create/Alter Tables</h2>");
	send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
	send('<input type="Hidden" name="oldpath" value="'.$oldpath.'">');
	send('<input type="Hidden" name="16" value="">');
	send('<input type="submit" value="Continue">');
	send('</form>');
} elseif (isset($_REQUEST["16"])) {                                             // 16 - Database - table-creation
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Create/Alter Tables</h2>");
	$oldpath = $_REQUEST["oldpath"];
	if (is_file(_FILE_DBCONF)) {
		require_once(_FILE_DBCONF);
		$databaseTableCreationCount = 0;
		$databaseTableCreation = false;
		$databaseError = "";
		$dbCon = getAdoConnection($cfg["db_type"], $cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		if (!$dbCon) {
			$databaseTableCreation = false;
			$databaseError = "cannot connect to database.";
		} else {
			send('<ul>');
			foreach ($queries['create'][$cfg["db_type"]] as $databaseTypeName => $databaseQuery) {
				send('<li><em>'.$databaseQuery.'</em> : ');
				$dbCon->Execute($databaseQuery);
				if ($dbCon->ErrorNo() == 0) {
					send('<font color="green">Ok</font></li>');
					$databaseTableCreationCount++;
				} else { // damn there was an error
					send('<font color="red">Error</font></li>');
					$databaseError = "error creating tables.";
					// close ado-connection
					$dbCon->Close();
					break;
				}
			}
			if ($databaseTableCreationCount == count($queries['create'][$cfg["db_type"]])) {
				// close ado-connection
				$dbCon->Close();
				$databaseTableCreation = true;
			} else {
				$databaseTableCreation = false;
			}
			send('</ul>');
		}
		if ($databaseTableCreation) {
			send('<font color="green"><strong>Ok</strong></font><br>');
			send($databaseTableCreationCount.' queries executed.');
			send("<h2>Next : Data</h2>");
			send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
			send('<input type="Hidden" name="oldpath" value="'.$oldpath.'">');
			send('<input type="Hidden" name="17" value="">');
			send('<input type="submit" value="Continue">');
			send('</form>');
		} else {
			send('<font color="red"><strong>Error</strong></font><br>');
			send($databaseError."<p>");
		}
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send('database-config-file <em>'._DIR._FILE_DBCONF.'</em> missing. setup cannot continue.');
	}
} elseif (isset($_REQUEST["17"])) {                                             // 17 - Database - data
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Data</h2>");
	$oldpath = $_REQUEST["oldpath"];
	if (is_file(_FILE_DBCONF)) {
		require_once(_FILE_DBCONF);
		$databaseDataCount = 0;
		$databaseData = false;
		$databaseError = "";
		$dbCon = getAdoConnection($cfg["db_type"], $cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		if (!$dbCon) {
			$databaseData = false;
			$databaseError = "cannot connect to database.";
		} else {
			send('<ul>');
			// add path
			array_unshift($queries['data'][$cfg["db_type"]], "INSERT INTO tf_settings VALUES ('path','".$oldpath."')");
			// add delete-state
			array_unshift($queries['data'][$cfg["db_type"]], "DELETE FROM tf_settings");
			// exec
			foreach ($queries['data'][$cfg["db_type"]] as $databaseTypeName => $databaseQuery) {
				send('<li><em>'.$databaseQuery.'</em> : ');
				$dbCon->Execute($databaseQuery);
				if ($dbCon->ErrorNo() == 0) {
					send('<font color="green">Ok</font></li>');
					$databaseDataCount++;
				} else { // damn there was an error
					send('<font color="red">Error</font></li>');
					$databaseError = "error importing data.";
					// close ado-connection
					$dbCon->Close();
					break;
				}
			}
			if ($databaseDataCount == count($queries['data'][$cfg["db_type"]])) {
				// close ado-connection
				$dbCon->Close();
				$databaseData = true;
			} else {
				$databaseData = false;
			}
			send('</ul>');
		}
		if ($databaseData) {
			send('<font color="green"><strong>Ok</strong></font><br>');
			send($databaseDataCount.' queries executed.');
			send("<h2>Next : Configuration</h2>");
			sendButton(2);
		} else {
			send('<font color="red"><strong>Error</strong></font><br>');
			send($databaseError."<p>");
		}
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send('database-config-file <em>'._DIR._FILE_DBCONF.'</em> missing. setup cannot continue.');
	}
} elseif (isset($_REQUEST["2"])) {                                              // 2 - Configuration
	sendHead(" - Configuration");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Configuration</h2>");
	send("<h2>Next : Server Settings</h2>");
	sendButton(21);
	send('</form>');
} elseif (isset($_REQUEST["21"])) {                                             // 21 - Configuration - Server Settings input
	sendHead(" - Configuration");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Configuration - Server Settings</h2>");
	if (is_file(_FILE_DBCONF)) {
		require_once(_FILE_DBCONF);
		$dbCon = getAdoConnection($cfg["db_type"], $cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		if (!$dbCon) {
			send('<font color="red"><strong>Error</strong></font><br>');
			send("cannot connect to database.<p>");
		} else {
			$tf_settings = loadSettings("tf_settings");
			// close ado-connection
			$dbCon->Close();
			if ($tf_settings !== false) {
				send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
				send('<table border="0">');
				// docroot
				$line = '<tr><td>docroot : </td>';
				$line .= '<td><input name="docroot" type="Text" maxlength="254" size="40" value="';
				if (isset($_REQUEST["docroot"]))
					$line .= $_REQUEST["docroot"];
				else
					$line .= _DIR;
				$line .= '"></td></tr>';
				send($line);
				send('</table>');
				send('<input type="Hidden" name="22" value="">');
				send('<input type="submit" value="Continue">');
				send('</form>');
			} else {
				send('<font color="red"><strong>Error</strong></font><br>');
				send("error loading settings.<p>");
			}
		}
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send('database-config-file <em>'._DIR._FILE_DBCONF.'</em> missing. setup cannot continue.');
	}
} elseif (isset($_REQUEST["22"])) {                                             // 22 - Configuration - Server Settings validate
	sendHead(" - Configuration");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Configuration - Server Settings Validation</h2>");
	$docroot = $_REQUEST["docroot"];
	if (((strlen($docroot) > 0)) && (substr($docroot, -1 ) != "/"))
		$docroot .= "/";
	$serverSettingsTestCtr = 0;
	$serverSettingsTestError = "";
	// docroot
	if (is_file($docroot.".version"))
		$serverSettingsTestCtr++;
	else
		$serverSettingsTestError .= "docroot <em>".$docroot."</em> is not valid.";
	// output
	if ($serverSettingsTestCtr == 1) {
		send('<font color="green"><strong>Ok</strong></font><br>');
		send("docroot : <em>".$docroot."</em><br>");
		send("<h2>Next : Save Server Settings</h2>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<input type="Hidden" name="docroot" value="'.$docroot.'">');
		send('<input type="Hidden" name="23" value="">');
		send('<input type="submit" value="Continue">');
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send($serverSettingsTestError."<p>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<input type="Hidden" name="docroot" value="'.$docroot.'">');
		send('<input type="Hidden" name="21" value="">');
		send('<input type="submit" value="Back">');
	}
	send('</form>');
} elseif (isset($_REQUEST["23"])) {                                             // 23 - Configuration - Server Settings	save
	sendHead(" - Configuration");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Configuration - Server Settings Save</h2>");
	$docroot = $_REQUEST["docroot"];
	if (is_file(_FILE_DBCONF)) {
		require_once(_FILE_DBCONF);
		$dbCon = getAdoConnection($cfg["db_type"], $cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		if (!$dbCon) {
			send('<font color="red"><strong>Error</strong></font><br>');
			send("cannot connect to database.<p>");
		} else {
			$settingsSaveCtr = 0;
			if (updateSetting("tf_settings", "docroot", $docroot) === true)
				$settingsSaveCtr++;
			if ($settingsSaveCtr == 1) {
				send('<font color="green"><strong>Ok</strong></font><br>');
				send('Server Settings saved.');
				send("<h2>Next : Rename Files and Dirs</h2>");
				sendButton(3);
			} else {
				send('<font color="red"><strong>Error</strong></font><br>');
				send('could not save Server Settings.');
				send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
				send('<input type="Hidden" name="docroot" value="'.$docroot.'">');
				send('<input type="Hidden" name="21" value="">');
				send('<input type="submit" value="Back">');
				send('</form>');
			}
			// close ado-connection
			$dbCon->Close();
		}
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send('database-config-file <em>'._DIR._FILE_DBCONF.'</em> missing. setup cannot continue.');
	}
} elseif (isset($_REQUEST["3"])) {                                             // 3 - rename files and dirs
	sendHead(" - Rename Files and Dirs");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Rename Files and Dirs</h2>");
	if (is_file(_FILE_DBCONF)) {
		require_once(_FILE_DBCONF);
		$dbCon = getAdoConnection($cfg["db_type"], $cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		if (!$dbCon) {
			send('<font color="red"><strong>Error</strong></font><br>');
			send("cannot connect to database.<p>");
		} else {
			$tf_settings = loadSettings("tf_settings");
			// close ado-connection
			$dbCon->Close();
			if ($tf_settings !== false) {
				$path = $tf_settings["path"];
				$pathExists = false;
				$renameOk = false;
				if ((@is_dir($path) === true) && (@is_dir($path.".torrents") === true)) {
					$pathExists = true;
					send('<ul>');
					send('<li><em>'.$path.".torrents -> ".$path.".transfers".'</em> : ');
					$renameOk = rename($path.".torrents", $path.".transfers");
					if ($renameOk === true)
						send('<font color="green">Ok</font></li>');
					else
						send('<font color="red">Error</font></li>');
					send('</ul>');
					if ($renameOk) {
						send('<font color="green"><strong>Ok</strong></font><br>');
						send('Files and Dirs renamed.');
						send("<h2>Next : End</h2>");
						sendButton(4);
					} else { // damn there was an error
						send('<font color="red">Error</font></li>');
						send("error renaming Files and Dirs. you have to re-inject all torrents.<p>");
					}
				} else {
					send('<font color="red">Error</font></li>');
					send("path <em>".$path.".torrents</em> does not exist. you have to re-inject all torrents.<p>");
				}
			} else {
				send('<font color="red"><strong>Error</strong></font><br>');
				send("error loading settings.<p>");
			}
		}
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send('database-config-file <em>'._DIR._FILE_DBCONF.'</em> missing. setup cannot continue.');
	}
} elseif (isset($_REQUEST["4"])) {                                              // 4 - End
	sendHead(" - End");
	send("<h1>"._TITLE."</h1>");
	send("<h2>End</h2>");
	send("<p>Upgrade completed.</p>");
	if ((substr(_VERSION_THIS, 0, 3)) != "svn") {
		$result = @unlink(__FILE__);
		if ($result !== true)
			send('<p><font color="red">Could not delete '.__FILE__.'</font><br>Please delete the file manual.</p>');
		else
			send('<p><font color="green">Deleted '.__FILE__.'</font></p>');
	} else {
		send('<p><font color="blue">This is a svn-version. '.__FILE__.' is untouched.</font></p>');
	}
	send("<h2>Next : Login</h2>");
	send('<form name="setup" action="login.php" method="post">');
	send('<input type="submit" value="Continue">');
	send('</form>');
} else {                                                                        // default
	sendHead();
	if (is_file(_FILE_DBCONF))
		send('<p><br><font color="red"><h1>db-config already exists '._FILE_DBCONF.'</h1></font>Delete upgrade.php if you came here after finishing upgrade to proceed to login.</p><hr>');
	send("<h1>"._TITLE."</h1>");
	send("<p>This script will upgrade "._NAME." from "._UPGRADE_FROM." to "._UPGRADE_TO."</p>");
	send("<h2>Next : Database</h2>");
	sendButton(1);
}

// foot
sendFoot();

// ob-end + exit
@ob_end_flush();
exit();

?>