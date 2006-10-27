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

// defines
define('_NAME', 'torrentflux-b4rt');
define('_REVISION', array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$'))))));
define('_VERSION_LOCAL', '.version');
define('_VERSION_THIS', trim(getDataFromFile(_VERSION_LOCAL)));
define('_TITLE', _NAME.' '._VERSION_THIS.' - Setup');
define('_DIR', dirname($_SERVER["SCRIPT_FILENAME"])."/");
define('_FILE_DBCONF', 'inc/config/config.db.php');
define('_FILE_THIS', $_SERVER['SCRIPT_NAME']);
define('_FILENAME_THIS', substr(_FILE_THIS, 1));

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
// tf_settings
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('path','/usr/local/torrentflux/')");
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
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_wget','1')");
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
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('ui_dim_main_w','780')");
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
array_push($queries[$cqt][$cdb], "INSERT INTO tf_settings VALUES ('enable_transfer_profile','0')");
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
// tf_cookies
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_cookies (
  cid int(5) NOT NULL auto_increment,
  uid int(10) NOT NULL default '0',
  host VARCHAR(255) default NULL,
  data VARCHAR(255) default NULL,
  PRIMARY KEY  (cid)
) TYPE=MyISAM");
// tf_links
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_links (
  lid int(10) NOT NULL auto_increment,
  url VARCHAR(255) NOT NULL default '',
  sitename VARCHAR(255) NOT NULL default 'Old Link',
  sort_order TINYINT(3) UNSIGNED default '0',
  PRIMARY KEY  (lid)
) TYPE=MyISAM");
// tf_log
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_log (
  cid int(14) NOT NULL auto_increment,
  user_id VARCHAR(32) NOT NULL default '',
  file VARCHAR(200) NOT NULL default '',
  action VARCHAR(200) NOT NULL default '',
  ip VARCHAR(15) NOT NULL default '',
  ip_resolved VARCHAR(200) NOT NULL default '',
  user_agent VARCHAR(200) NOT NULL default '',
  time VARCHAR(14) NOT NULL default '0',
  PRIMARY KEY  (cid)
) TYPE=MyISAM");
// tf_messages
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_messages (
  mid int(10) NOT NULL auto_increment,
  to_user VARCHAR(32) NOT NULL default '',
  from_user VARCHAR(32) NOT NULL default '',
  message TEXT,
  IsNew int(11) default NULL,
  ip VARCHAR(15) NOT NULL default '',
  time VARCHAR(14) NOT NULL default '0',
  force_read TINYINT(1) default '0',
  PRIMARY KEY  (mid)
) TYPE=MyISAM");
// tf_rss
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_rss (
  rid int(10) NOT NULL auto_increment,
  url VARCHAR(255) NOT NULL default '',
  PRIMARY KEY  (rid)
) TYPE=MyISAM");
// tf_users
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_users (
  uid int(10) NOT NULL auto_increment,
  user_id VARCHAR(32) NOT NULL default '',
  password VARCHAR(34) NOT NULL default '',
  hits int(10) NOT NULL default '0',
  last_visit VARCHAR(14) NOT NULL default '0',
  time_created VARCHAR(14) NOT NULL default '0',
  user_level TINYINT(1) NOT NULL default '0',
  hide_offline TINYINT(1) NOT NULL default '0',
  theme VARCHAR(100) NOT NULL default 'default',
  language_file VARCHAR(60) default 'lang-english.php',
  state TINYINT(1) NOT NULL default '1',
  PRIMARY KEY  (uid)
) TYPE=MyISAM");
// tf_torrents
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_torrents (
  torrent VARCHAR(255) NOT NULL default '',
  running ENUM('0','1') NOT NULL default '0',
  rate SMALLINT(4) unsigned NOT NULL default '0',
  drate SMALLINT(4) unsigned NOT NULL default '0',
  maxuploads TINYINT(3) unsigned NOT NULL default '0',
  superseeder ENUM('0','1') NOT NULL default '0',
  runtime ENUM('True','False') NOT NULL default 'False',
  sharekill SMALLINT(4) unsigned NOT NULL default '0',
  minport SMALLINT(5) unsigned NOT NULL default '0',
  maxport SMALLINT(5) unsigned NOT NULL default '0',
  maxcons SMALLINT(4) unsigned NOT NULL default '0',
  savepath VARCHAR(255) NOT NULL default '',
  btclient VARCHAR(32) NOT NULL default 'tornado',
  hash VARCHAR(40) DEFAULT '' NOT NULL,
  datapath VARCHAR(255) NOT NULL default '',
  PRIMARY KEY  (torrent)
) TYPE=MyISAM");
// tf_trprofiles
array_push($queries[$cqt][$cdb], "
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
) TYPE=MyISAM");
// tf_torrent_totals
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_torrent_totals (
  tid VARCHAR(40) NOT NULL default '',
  uptotal BIGINT(80) NOT NULL default '0',
  downtotal BIGINT(80) NOT NULL default '0',
  PRIMARY KEY  (tid)
) TYPE=MyISAM");
// tf_xfer
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_xfer (
  user_id VARCHAR(32) NOT NULL default '',
  date DATE NOT NULL default '0000-00-00',
  download BIGINT(80) NOT NULL default '0',
  upload BIGINT(80) NOT NULL default '0',
  PRIMARY KEY  (user_id,date)
) TYPE=MyISAM");
// tf_settings_user
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_user (
  uid INT(10) NOT NULL,
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL
) TYPE=MyISAM");
// tf_settings
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings (
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL,
  PRIMARY KEY  (tf_key)
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

// sql-queries : Data
$cqt = 'data';
$queries[$cqt][$cdb] = array();
foreach ($queries['data']['common'] as $dataQuery)
	array_push($queries[$cqt][$cdb], $dataQuery);
// tf_links
array_push($queries[$cqt][$cdb], "INSERT INTO tf_links VALUES (NULL,'http://tf-b4rt.berlios.de/','Home','0')");

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
// tf_cookies
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_cookies (
  cid INTEGER PRIMARY KEY,
  uid INTEGER NOT NULL default '0',
  host TEXT default NULL,
  data TEXT default NULL
)");
// tf_links
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_links (
  lid INTEGER PRIMARY KEY,
  url VARCHAR(255) NOT NULL default '',
  sitename VARCHAR(255) NOT NULL default 'Old Link',
  sort_order INTEGER(3) default '0'
)");
// tf_log
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_log (
  cid INTEGER PRIMARY KEY,
  user_id VARCHAR(32) NOT NULL default '',
  file VARCHAR(200) NOT NULL default '',
  action VARCHAR(200) NOT NULL default '',
  ip VARCHAR(15) NOT NULL default '',
  ip_resolved VARCHAR(200) NOT NULL default '',
  user_agent VARCHAR(200) NOT NULL default '',
  time VARCHAR(14) NOT NULL default '0'
)");
// tf_messages
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_messages (
  mid INTEGER PRIMARY KEY,
  to_user VARCHAR(32) NOT NULL default '',
  from_user VARCHAR(32) NOT NULL default '',
  message TEXT,
  IsNew INT(11) default NULL,
  ip VARCHAR(15) NOT NULL default '',
  time VARCHAR(14) NOT NULL default '0',
  force_read INTEGER default '0'
)");
// tf_rss
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_rss (
  rid INTEGER PRIMARY KEY,
  url VARCHAR(255) NOT NULL default ''
)");
// tf_users
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_users (
  uid INTEGER PRIMARY KEY,
  user_id VARCHAR(32) NOT NULL default '',
  password VARCHAR(34) NOT NULL default '',
  hits INT(10) NOT NULL default '0',
  last_visit VARCHAR(14) NOT NULL default '0',
  time_created VARCHAR(14) NOT NULL default '0',
  user_level TINYINT(1) NOT NULL default '0',
  hide_offline TINYINT(1) NOT NULL default '0',
  theme VARCHAR(100) NOT NULL default 'default',
  language_file VARCHAR(60) default 'lang-english.php',
  state TINYINT(1) NOT NULL default '1',
)");
// tf_torrents
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_torrents (
  torrent VARCHAR(255) NOT NULL default '',
  running INTEGER(1) NOT NULL default '0',
  rate INTEGER(4) NOT NULL default '0',
  drate INTEGER(4) NOT NULL default '0',
  maxuploads INTEGER(3) NOT NULL default '0',
  superseeder INTEGER(1) NOT NULL default '0',
  runtime VARCHAR(5) NOT NULL default 'False',
  sharekill INTEGER(4) NOT NULL default '0',
  minport INTEGER(5) NOT NULL default '0',
  maxport INTEGER(5) NOT NULL default '0',
  maxcons INTEGER(4) NOT NULL default '0',
  savepath VARCHAR(255) NOT NULL default '',
  btclient VARCHAR(32) NOT NULL default 'tornado',
  hash VARCHAR(40) DEFAULT '' NOT NULL,
  datapath VARCHAR(255) NOT NULL default '',
  PRIMARY KEY  (torrent)
)");
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
// tf_torrent_totals
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_torrent_totals (
  tid VARCHAR(40) NOT NULL default '',
  uptotal BIGINT(80) NOT NULL default '0',
  downtotal BIGINT(80) NOT NULL default '0',
  PRIMARY KEY  (tid)
)");
// tf_xfer
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_xfer (
  user_id VARCHAR(32) NOT NULL default '',
  date DATE NOT NULL default '0000-00-00',
  download BIGINT(80) NOT NULL default '0',
  upload BIGINT(80) NOT NULL default '0',
  PRIMARY KEY  (user_id,date)
)");
// tf_settings_user
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_user (
  uid INTEGER NOT NULL,
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL
)");
// tf_settings
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings (
  tf_key VARCHAR(255) NOT NULL default '',
  tf_value TEXT NOT NULL,
  PRIMARY KEY  (tf_key)
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

// sql-queries : Data
$cqt = 'data';
$queries[$cqt][$cdb] = array();
foreach ($queries['data']['common'] as $dataQuery)
	array_push($queries[$cqt][$cdb], $dataQuery);
// tf_links
array_push($queries[$cqt][$cdb], "INSERT INTO tf_links VALUES (NULL,'http://tf-b4rt.berlios.de/','Home','0')");

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
// tf_cookies
array_push($queries[$cqt][$cdb], "CREATE SEQUENCE tf_cookies_cid_seq");
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_cookies (
  cid INT4 DEFAULT nextval('tf_cookies_cid_seq'),
  uid INT4 NOT NULL DEFAULT '0',
  host VARCHAR(255) DEFAULT NULL,
  data VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (cid)
)");
// tf_links
array_push($queries[$cqt][$cdb], "CREATE SEQUENCE tf_links_lid_seq");
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_links (
  lid INT4 DEFAULT nextval('tf_links_lid_seq'),
  url VARCHAR(255) NOT NULL DEFAULT '',
  sitename VARCHAR(255) NOT NULL DEFAULT 'Old Link',
  sort_order INT2  DEFAULT '0',
  PRIMARY KEY (lid),
  CHECK (sort_order>=0)
)");
// tf_log
array_push($queries[$cqt][$cdb], "CREATE SEQUENCE tf_log_cid_seq");
array_push($queries[$cqt][$cdb], "
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
)");
// tf_messages
array_push($queries[$cqt][$cdb], "CREATE SEQUENCE tf_messages_mid_seq");
array_push($queries[$cqt][$cdb], "
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
)");
// tf_rss
array_push($queries[$cqt][$cdb], "CREATE SEQUENCE tf_rss_rid_seq");
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_rss (
  rid INT4 DEFAULT nextval('tf_rss_rid_seq'),
  url VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (rid)
)");
// tf_users
array_push($queries[$cqt][$cdb], "CREATE SEQUENCE tf_users_uid_seq");
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_users (
  uid INT4 DEFAULT nextval('tf_users_uid_seq'),
  user_id VARCHAR(32) NOT NULL DEFAULT '',
  password VARCHAR(34) NOT NULL DEFAULT '',
  hits INT4 NOT NULL DEFAULT '0',
  last_visit VARCHAR(14) NOT NULL DEFAULT '0',
  time_created VARCHAR(14) NOT NULL DEFAULT '0',
  user_level INT2 NOT NULL DEFAULT '0',
  hide_offline INT2 NOT NULL DEFAULT '0',
  theme VARCHAR(100) NOT NULL DEFAULT 'default',
  language_file VARCHAR(60) DEFAULT 'lang-english.php',
  state INT2 NOT NULL DEFAULT '1',
  PRIMARY KEY (uid)
)");
// tf_torrents
array_push($queries[$cqt][$cdb], "
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
  datapath VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (torrent),
  CHECK (running>=0),
  CHECK (maxuploads>=0),
  CHECK (minport>=0),
  CHECK (maxport>=0),
  CHECK (maxcons>=0)
)");
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
// tf_torrent_totals
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_torrent_totals (
  tid VARCHAR(40) NOT NULL DEFAULT '',
  uptotal INT8 NOT NULL DEFAULT '0',
  downtotal INT8 NOT NULL DEFAULT '0',
  PRIMARY KEY (tid)
)");
// tf_xfer
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_xfer (
  user_id VARCHAR(32) NOT NULL DEFAULT '',
  date DATE NOT NULL DEFAULT '0001-01-01',
  download INT8 NOT NULL DEFAULT '0',
  upload INT8 NOT NULL DEFAULT '0'
)");
// tf_settings_user
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings_user (
  uid INT4 NOT NULL,
  tf_key VARCHAR(255) NOT NULL DEFAULT '',
  tf_value TEXT DEFAULT '' NOT NULL
)");
// tf_settings
array_push($queries[$cqt][$cdb], "
CREATE TABLE tf_settings (
  tf_key VARCHAR(255) NOT NULL DEFAULT '',
  tf_value TEXT DEFAULT '' NOT NULL,
  PRIMARY KEY (tf_key)
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

// sql-queries : Data
$cqt = 'data';
$queries[$cqt][$cdb] = array();
foreach ($queries['data']['common'] as $dataQuery)
	array_push($queries[$cqt][$cdb], $dataQuery);
// tf_links
array_push($queries[$cqt][$cdb], "INSERT INTO tf_links VALUES ('0','http://tf-b4rt.berlios.de/','Home','0')");
// sequences
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_users_uid_seq',(select case when max(uid)>0 then max(uid)+1 else 1 end from tf_users))");
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_messages_mid_seq',(select case when max(mid)>0 then max(mid)+1 else 1 end from tf_messages))");
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_cookies_cid_seq',(select case when max(cid)>0 then max(cid)+1 else 1 end from tf_cookies))");
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_rss_rid_seq',(select case when max(rid)>0 then max(rid)+1 else 1 end from tf_rss))");
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_links_lid_seq',(select case when max(lid)>0 then max(lid)+1 else 1 end from tf_links))");
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_trprofiles_id_seq',(select case when max(id)>0 then max(id)+1 else 1 end from tf_trprofiles))");
array_push($queries[$cqt][$cdb], "SELECT SETVAL('tf_log_cid_seq',(select case when max(cid)>0 then max(cid)+1 else 1 end from tf_log))");

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
	// create
	$line = '<tr><td colspan="2">Create Database :';
	$line .= '<input name="db_create" type="Checkbox" value="true" checked>';
	$line .= '</td></tr>';
	send($line);
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
} elseif (isset($_REQUEST["14"])) {                                             // 14 - Database - creation + test
	$type = $_REQUEST["db_type"];
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Creation + Test - ".$type."</h2>");
	$paramsOk = true;
	if (isset($_REQUEST["db_host"]))
		$host = $_REQUEST["db_host"];
	else
		$paramsOk = false;
	if (isset($_REQUEST["db_create"]))
		$create = true;
	else
		$create = false;
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
	// create + test
	if ($paramsOk) {
		$databaseExists = true;
		if (($create) && ($type != "sqlite")) {
			$dbCon = getAdoConnection($type, $host, $user, $pass);
			if (!$dbCon) {
				$databaseExists = false;
				$databaseTestOk = false;
				$databaseError = "cannot connect to database.";
			} else {
				$sqlState = "CREATE DATABASE ".$name;
				$dbCon->Execute($sqlState);
				if ($dbCon->ErrorNo() == 0) {
					send("created database <em>".$name."</em>.<br>");
					$databaseExists = true;
				} else { // damn there was an error
					$databaseExists = false;
					$databaseTestOk = false;
					$databaseError = "cannot create database <em>".$name."</em>.";
				}
				// close ado-connection
				$dbCon->Close();
			}
			unset($dbCon);
		}
		if ($databaseExists) {
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
					// close ado-connection
					$dbCon->Close();
					$databaseTestOk = true;
				} else {
					$databaseTestOk = false;
				}
				send('</ul>');
			}
		}
	} else {
		$databaseTestOk = false;
		$databaseError = "config error.";
	}
	// output
	if ($databaseTestOk) {
		send('<font color="green"><strong>Ok</strong></font><br>');
		send("<h2>Next : Write Config File</h2>");
		send("Please ensure this script can write to the dir <em>"._DIR."inc/config/</em><p>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
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
		if (isset($_REQUEST["db_create"]))
			send('<input type="Hidden" name="db_create" value="'.$_REQUEST["db_create"].'">');
		send('<input type="submit" value="Back">');
	}
	send('</form>');
} elseif (isset($_REQUEST["15"])) {                                             // 15 - Database - config-file
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Config-File</h2>");
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
	send("<h2>Next : Create Tables</h2>");
	sendButton(16);
} elseif (isset($_REQUEST["16"])) {                                             // 16 - Database - table-creation
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Create Tables</h2>");
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
			send($databaseTableCreationCount.' tables created.');
			send("<h2>Next : Data</h2>");
			sendButton(17);
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
				// path
				$line = '<tr><td>path : </td>';
				$line .= '<td><input name="path" type="Text" maxlength="254" size="40" value="';
				if (isset($_REQUEST["path"]))
					$line .= $_REQUEST["path"];
				else
					$line .= $tf_settings["path"];
				$line .= '"></td></tr>';
				send($line);
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
	$path = $_REQUEST["path"];
	if (((strlen($path) > 0)) && (substr($path, -1 ) != "/"))
		$path .= "/";
	$docroot = $_REQUEST["docroot"];
	if (((strlen($docroot) > 0)) && (substr($docroot, -1 ) != "/"))
		$docroot .= "/";
	$serverSettingsTestCtr = 0;
	$serverSettingsTestError = "";
	$pathExists = false;
	// path
	if (!(@is_dir($path) === true)) {
		// dir doesnt exist, try to create
		if (!((@mkdir($path, 0777)) === true))
			$serverSettingsTestError .= "path <em>".$path."</em> does not exist and cant be created.<br>";
		else
			$pathExists = true;
	} else {
		$pathExists = true;
	}
	if ($pathExists) {
		if (!(@is_writable($path) === true))
			$serverSettingsTestError .= "path <em>".$path."</em> is not writable.<br>";
		else
			$serverSettingsTestCtr++;
	}
	// docroot
	if (is_file($docroot.".version"))
		$serverSettingsTestCtr++;
	else
		$serverSettingsTestError .= "docroot <em>".$docroot."</em> is not valid.";
	// output
	if ($serverSettingsTestCtr == 2) {
		send('<font color="green"><strong>Ok</strong></font><br>');
		send("path : <em>".$path."</em><br>");
		send("docroot : <em>".$docroot."</em><br>");
		send("<h2>Next : Save Server Settings</h2>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<input type="Hidden" name="path" value="'.$path.'">');
		send('<input type="Hidden" name="docroot" value="'.$docroot.'">');
		send('<input type="Hidden" name="23" value="">');
		send('<input type="submit" value="Continue">');
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send($serverSettingsTestError."<p>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<input type="Hidden" name="path" value="'.$path.'">');
		send('<input type="Hidden" name="docroot" value="'.$docroot.'">');
		send('<input type="Hidden" name="21" value="">');
		send('<input type="submit" value="Back">');
	}
	send('</form>');
} elseif (isset($_REQUEST["23"])) {                                             // 23 - Configuration - Server Settings	save
	sendHead(" - Configuration");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Configuration - Server Settings Save</h2>");
	$path = $_REQUEST["path"];
	$docroot = $_REQUEST["docroot"];
	if (is_file(_FILE_DBCONF)) {
		require_once(_FILE_DBCONF);
		$dbCon = getAdoConnection($cfg["db_type"], $cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		if (!$dbCon) {
			send('<font color="red"><strong>Error</strong></font><br>');
			send("cannot connect to database.<p>");
		} else {
			$settingsSaveCtr = 0;
			if (updateSetting("tf_settings", "path", $path) === true)
				$settingsSaveCtr++;
			if (updateSetting("tf_settings", "docroot", $docroot) === true)
				$settingsSaveCtr++;
			if ($settingsSaveCtr == 2) {
				send('<font color="green"><strong>Ok</strong></font><br>');
				send('Server Settings saved.');
				send("<h2>Next : End</h2>");
				sendButton(3);
			} else {
				send('<font color="red"><strong>Error</strong></font><br>');
				send('could not save Server Settings.');
				send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
				send('<input type="Hidden" name="path" value="'.$path.'">');
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
} elseif (isset($_REQUEST["3"])) {                                              // 3 - End
	sendHead(" - End");
	send("<h1>"._TITLE."</h1>");
	send("<h2>End</h2>");
	send("<p>Setup completed.</p>");
	if ((substr(_VERSION_THIS, 0, 3)) != "svn") {
		$result = @unlink(_FILENAME_THIS);
		if ($result !== true)
			send('<p><font color="red">Could not delete '._FILENAME_THIS.'</font><br>Please delete the file manual.</p>');
		else
			send('<p><font color="green">Deleted '._FILENAME_THIS.'</font></p>');
	} else {
		send('<p><font color="blue">This is a svn-version. '._FILENAME_THIS.' is untouched.</font></p>');
	}
	send("<h2>Next : Login</h2>");
	send('<form name="setup" action="login.php" method="post">');
	send('<input type="submit" value="Continue">');
	send('</form>');
} else {                                                                        // default
	sendHead();
	if (is_file(_FILE_DBCONF))
		send('<p><br><font color="red"><h1>db-config already exists '._FILE_DBCONF.'</h1></font>Delete setup.php if you came here after finishing setup to proceed to login.</p><hr>');
	send("<h1>"._TITLE."</h1>");
	send("<p>This script will setup "._NAME."</p>");
	send("<h2>Next : Database</h2>");
	sendButton(1);
}

// foot
sendFoot();

// ob-end + exit
@ob_end_flush();
exit();

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * load Settings
 *
 * @param $dbTable
 * @return array
 */
function loadSettings($dbTable) {
    global $dbCon;
    // pull the config params out of the db
    $sql = "SELECT tf_key, tf_value FROM ".$dbTable;
    $recordset = $dbCon->Execute($sql);
	if ($dbCon->ErrorNo() != 0)
		return false;
    $retVal = array();
    while (list($key, $value) = $recordset->FetchRow()) {
        $tmpValue = '';
		if (strpos($key,"Filter") > 0) {
		  $tmpValue = unserialize($value);
		} elseif ($key == 'searchEngineLinks') {
            $tmpValue = unserialize($value);
    	}
    	if(is_array($tmpValue))
            $value = $tmpValue;
        $retVal[$key] = $value;
    }
    return $retVal;
}

/**
 * update Setting
 *
 * @param $dbTable
 * @param $key
 * @param $value
 * @return boolean
 */
function updateSetting($dbTable, $key, $value) {
    global $dbCon;
	if (is_array($value))
        $update_value = serialize($value);
    else
    	$update_value = $value;
    $sql = "UPDATE ".$dbTable." SET tf_value = '".$update_value."' WHERE tf_key = '".$key."'";
    $dbCon->Execute($sql);
    if ($dbCon->ErrorNo() != 0)
		return false;
	return true;
}

/**
 * write the db-conf file.
 *
 * @param $type
 * @param $host
 * @param $user
 * @param $pass
 * @param $name
 * @param $pcon
 * @return boolean
 */
function writeDatabaseConfig($type, $host, $user, $pass, $name, $pcon) {
	global $databaseConfWriteOk, $databaseConfWriteError, $databaseConfContent;
	$databaseConfContent = '<?php

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

/******************************************************************************/
// YOUR DATABASE CONNECTION INFORMATION
/******************************************************************************/
$cfg["db_type"] = "'.$type.'"; // Database-Type : mysql/sqlite/postgres
$cfg["db_host"] = "'.$host.'"; // Database host computer name or IP
$cfg["db_name"] = "'.$name.'"; // Name of the Database
$cfg["db_user"] = "'.$user.'"; // Username for Database
$cfg["db_pass"] = "'.$pass.'"; // Password for Database
$cfg["db_pcon"] = '.$pcon.'; // Persistent Connection enabled : true/false
/******************************************************************************/

?>';
	$configFile = false;
	$configFile = @fopen(_DIR._FILE_DBCONF, "w");
	if (!$configFile) {
		$databaseConfWriteOk = false;
		$databaseConfWriteError = "cannot open config-file <em>"._DIR._FILE_DBCONF."</em> for writing.";
		return false;
	}
	$result = @fwrite($configFile, $databaseConfContent);
	@fclose($configFile);
	if ($result === false) {
		$databaseConfWriteOk = false;
		$databaseConfWriteError = "cannot write content to config-file <em>"._DIR._FILE_DBCONF."</em>.";
		return false;
	}
	$databaseConfWriteOk = true;
	return true;
}

/**
 * get a ado-connection to our database.
 *
 * @param $type
 * @param $host
 * @param $user
 * @param $pass
 * @param $name
 * @return database-connection or false on error
 */
function getAdoConnection($type, $host, $user, $pass, $name = "") {
	require_once('inc/lib/adodb/adodb.inc.php');
	// create ado-object
    $db = &ADONewConnection($type);
    // connect
    @ $db->Connect($host, $user, $pass, $name);
    // check for error
    if ($db->ErrorNo() != 0)
    	return false;
    // return db-connection
	return $db;
}

/**
 * load data of file
 *
 * @param $file the file
 * @return data
 */
function getDataFromFile($file) {
	if ($fileHandle = @fopen($file, 'r')) {
		$data = null;
		while (!@feof($fileHandle))
			$data .= @fgets($fileHandle, 8192);
		@fclose ($fileHandle);
		return $data;
	} else {
		return false;
	}
}

/**
 * send button
 */
function sendButton($name = "", $value = "") {
	send('<form name="setup" action="' . _FILE_THIS . '" method="post"><input type="Hidden" name="'.$name.'" value="'.$value.'"><input type="submit" value="Continue"></form><br>');
}

/**
 * send head
 */
function sendHead($title = "") {
	send('<html>');
	send('<head>');
	send('<title>'._TITLE.$title.'</title>');
	send('<style type="text/css">');
	send('font {font-family: Verdana,Helvetica; font-size: 12px}');
	send('body {font-family: Verdana,Helvetica; font-size: 12px}');
	send('p,td {font-family: Verdana,Helvetica; font-size: 12px}');
	send('h1 {font-family: Verdana,Helvetica; font-size: 15px}');
	send('h2 {font-family: Verdana,Helvetica; font-size: 14px}');
	send('h3 {font-family: Verdana,Helvetica; font-size: 13px}');
	send('</style>');
	send('</head>');
	send('<body topmargin="8" leftmargin="5" bgcolor="#FFFFFF">');
}

/**
 * send foot
 */
function sendFoot() {
	send('</body>');
	send('</html>');
}

/**
 * send - sends a string to the client
 */
function send($string = "") {
	echo $string;
	echo str_pad('', 4096)."\n";
	@ob_flush();
	@flush();
}

?>