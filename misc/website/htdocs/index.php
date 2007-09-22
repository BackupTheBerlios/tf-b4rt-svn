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

/* defines */
define('_FILE_NEWS', 'newshtml.txt');
define('_FILE_VERSION', 'version.txt');
define('_FILE_CHANGELOG', 'CHANGES');
define('_FILE_AUTHORS', 'AUTHORS');

/* global fields */
$page = "";

// functions
require_once('functions.php');

/*
 * Temp feature to switch CSS sheets dynamically in testing.
 * To change css sheet, call URL with vbl 'css=new' for the new sheet, setting 
 * will be kept for the current browser session.  To change back, use 
 * 'css=default'.
session_start();

// default CSS to use:
// current available: default, new
$css = (isset($_SESSION["css"]) && !empty($_SESSION["css"]))
	? $_SESSION["css"] : "default";
 */
$css="default";

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// get current versions
define('_VERSION', @trim(getDataFromFile(_FILE_VERSION)));

// Array of 'page ids' => 'page titles'
$defaultTitle="Home Page - A BitTorrent and Internet Transfer Web Control Application";
$pages = array(
	"requirements"	=> "Installation and Configuration Requirements",
	"features"		=> "Features List",
	"news"			=> "News and Updates",
	"about"			=> "Authors, History and Contributors",
	"changelog"		=> "Changelog",
	"screenshots"	=> "Screenshots",
	"home"			=> $defaultTitle,
);

// init page-var
if ( isset($_REQUEST["s"]) && !empty($_REQUEST["s"])){
	$page = $_REQUEST["s"];
	// Check we have the requested page:
	if(!array_key_exists($page, $pages)){
		$page="home";
	}
} else {
	$page = "home";
}

printPageHead($page);
// Evaluate the page function to be exec'ed - ie 'printPageHome();' etc:
eval("printPage".ucfirst($page)."();");
printPageFoot();

// exit
exit();

// -----------------------------------------------------------------------------
// content
// -----------------------------------------------------------------------------

/**
 * prints page-head
 * @var $page - current page requested
 */
function printPageHead($page) {
	global $pages, $css;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Torrentflux-b4rt <?php echo $pages[$page]; ?></title>
    <meta name="description" content="Torrentflux-b4rt is a web based control panel for managing BitTorrent, wget and Newzbin downloads.  Torrentflux-b4rt supports a number of transfer clients, including BitTorrent, BitTornado, Transmission, Azureus, wget and nzbperl.  Torrentflux-b4rt is based on the torrentflux download manager. Torrentflux-b4rt requires a web server, PHP and a database - MySQL, Postgresql or SQLite - to run."/>
	<meta name="keywords" content="torrentflux-b4rt, torrentflux, bittorrent, bittornado, transmission, azureus, nzbperl, wget, torrent, download, remote, control, bandwidth, controller, fluazu, fluxd, rss, feed, downloader, automate, automation, web, web-based, transfer, manager, management, php, mysql, postgresql, perl, python, free, freeware, open, opensource, oss, gui, frontend, b4rt, tfb4rt"/>
    <meta name="robots" content="index,follow" />
    <meta name="author" content="Design: DocTom; Rest: b4rt,munk" />
	<link rel="stylesheet" type="text/css" href="css/<?php echo $css; ?>.css" />
    <link rel="alternate" title="News - RSS 0.91" href="https://developer.berlios.de/export/rss_bsnews.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Releases - RSS 0.91" href="https://developer.berlios.de/export/rss_bsnewreleases.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="News - RSS 2.0" href="https://developer.berlios.de/export/rss20_bsnews.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Releases - RSS 2.0" href="https://developer.berlios.de/export/rss20_bsnewreleases.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Forum - RSS 0.92" href="https://tf-b4rt.berlios.de/forum/index.php?type=rss;action=.xml" type="application/rss+xml" />
    <script language="javascript" src="js/default.js" type="text/javascript"></script>
</head>
<body>
<div id="container">
	<div id="header">
		<p class="version">
			<span class="versionspan"><a href="download-torrentflux-b4rt.html" title="Download Torrentflux-b4rt <?php echo _VERSION;?>">Download Current Version:<br/>Torrentflux-b4rt <?php echo _VERSION; ?></a></span>
		</p>
	</div>
	<div id="navi">
		<ul>
			<li><a href="home.html" title="Home">Home</a></li>
			<li><a href="features.html" title="Features">Features</a></li>
			<li><a href="requirements.html" title="Requirements">Requirements</a></li>
			<li><a href="screenshots.html" title="Screenshots">Screenshots</a></li>
			<li><a href="about.html" title="About">About</a></li>
			<li><a href="news.html" title="News">News</a></li>
			<li><a href="downloads" title="Downloads">Downloads</a></li>
			<li><a href="forum" title="Forum">Forum</a></li>
		</ul>
	</div>
	<div id="content">
<?php
}

/**
 * prints page-foot
 */
function printPageFoot() {
?>
		<div id="footer" align="right"><a href="#header" title="goto top">top</a></div>
		<div id="credits">
			<p>
				<a href="https://developer.berlios.de/projects/tf-b4rt/" title="BerliOS Developer Project" target="_blank"><img src="https://developer.berlios.de/bslogo.php?group_id=7000" width="124" height="32" border="0" alt="BerliOS Developer Logo" /></a>
			</p>
			<p class="svnid">
				<a href="wsvn-website" title="WebSVN" target="_blank">$Id$</a>
			</p>
		</div>
	</div>
</div>
<?php googleAnalytics(); ?>
</body>
</html>
<?php
}

/**
 * prints page "home"
 */
function printPageHome() {
?>
		<h1 id="home">Home</h1>
		<p>
			<a href="images/screenshots/1.0-alpha7/index.png" title="torrentflux-b4rt 1.0 alpha7: Index-Page" target="_blank"><img src="images/10a7_index_small.png" width="252" height="184" border="0" alt="torrentflux-b4rt 1.0 alpha7: Index-Page" align="right" class="img_right" /></a>
		</p>
		<p><strong>Torrentflux-b4rt</strong> is a web based transfer control client.
		</p>
		<p>
			Torrentflux-b4rt allows you to control your internet downloads / transfers from anywhere using a highly configurable web based front end.
		</p>

		<br clear="all"/>
		<br/>
		<p> Torrentflux-b4rt is very easy to install on a web server and includes a simple setup script which can be accessed from a web browser.  Just upload the files to your web server, run the setup script and your torrentflux-b4rt installation is ready to go.
		</p>
		<p>Torrentflux-b4rt was originally based on the <a href="http://www.torrentflux.com" title="www.torrentflux.com" target="_blank">TorrentFlux</a> BitTorrent controller written by Qrome, although has recently undergone a major rewrite to allow transparent integration with a number of transfer clients and protocols.  For a full list of features please see <a href="features.html" title="Torrentflux-b4rt Features Page">the torrentflux-b4rt features page</a>.
		</p>
		<ul>
			<li><a href="download-torrentflux-b4rt.html" title="Download Torrentflux-b4rt">Download Torrentflux-b4rt</a></li>
			<li><a href="changelog.html" title="Torrentflux-b4rt Changelog">Torrentflux-b4rt Changelog</a></li>
		</ul>

<?php
}

/**
 * prints page "requirements"
 */
function printPageRequirements() {
?>
		<h1 id="requirements">Requirements</h1>
<div id="req">
		<ul>
			<li>A Unix like OS (Win32 not supported) - current tested OSs include:
				<ul>
					<li>Linux: Debian, Ubuntu, Gentoo, RedHat, Fedora, NSLU2, ClarkConnect - amongst others!</li>
					<li>BSD: FreeBSD, OpenBSD, NetBSD</li>
					<li>Apple: Mac OS X</li>
				</ul>
			</li>

			<li>A Web Server - any Unix like webserver that supports PHP should work.  Current supported/tested:
				<ul>
					<li><a href="http://httpd.apache.org" title="Apache HTTP Server" target="_blank">Apache</a></li>
					<li><a href="http://www.lighttpd.net" title="LightTPD" target="_blank">LightTPD</a></li>
				</ul>
			</li>

			<li>An SQL-Database - current supported db types are:
				<ul>
					<li><a href="http://www.mysql.com" title="MySQL" target="_blank">MySQL</a></li>
					<li><a href="http://www.sqlite.org" title="SQLite" target="_blank">SQLite</a></li>
					<li><a href="http://www.postgresql.org" title="PostgreSQL" target="_blank">PostgreSQL</a></li>
				</ul>
			</li>

			<li><a href="http://www.php.net" title="PHP" target="_blank">PHP</a> 4.3.x or higher.</li>
			<li><a href="http://www.python.org" title="Python" target="_blank">Python</a> 2.2 or higher.</li>
			<li><a href="http://www.perl.org" title="Perl" target="_blank">Perl</a> 5.6 or higher (for the optional fluxd daemon).</li>
		</ul>

		More details can be found in the file <a href="README" title="Torrentflux-b4rt README file">README</a> file.
</div>
<?php
}

/**
 * prints page "features"
 */
function printPageFeatures() {
?>

	<h1 id="features">Features</h1>
	<p>
		Some of the most popular features of torrentflux-b4rt are listed below.  This list is definitely NOT exhaustive - there are a massive number of features that can be configured via the torrentflux-b4rt administration panel!
	</p>

	<a name="toc"></a>
	<p>
	<strong>Feature List:</strong>
	</p>
	<div class="featurelist">
		<ul>
			<li><a href="#protocols">Supports multiple internet transfer protocols </a></li>
			<li><a href="#xfer_control">Unified transfer control</a></li>
			<li><a href="#xfer_stats">Transfer statistics and logging</a></li>
			<li><a href="#metafiles">Uploading and injection of metafiles (.torrent, .wget, .nzb files)</a></li>
			<li><a href="#fluxcli">fluxcli.php - a complete command-line version of torrentflux-b4rt</a></li>
			<li><a href="#fluxd">Fluxd - background perl daemon to perform scheduled tasks</a></li>
			<li><a href="#filemanager">Integrated Filemanager - explore the filesystem and carry out common tasks such as decompressing archives, copying/moving files/directories, streaming media using VLC and more</a></li>
			<li><a href="#ajax">AJAX updates for maximum info with minimal bandwidth</a></li>
			<li><a href="#templating">Templating engine</a></li>
		</ul>
	</div>
	<br/>

	<div class="subcontent">
		<h2 id="protocols"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Supports multiple internet transfer protocols</h2>
		Torrentflux-b4rt supports other internet transfer protocols as well as just BitTorrent.  Integration of the various protocols is seamless, meaning you start, stop, restart transfers in the same manner regardless of the underlying transfer protocol.<br/>
		<br/>
		Supported transfer protocols include:<br/>
		<ul class="subcontent">
			<li>
				BitTorrent - supported clients include:
				<ul>
					<li><a href="http://www.bittorrent.com/" title="Open BitTorrent.com site in new window" target="_blank">Original BitTorrent/Mainline</a><br/>&mdash; supports trackerless torrents and encryption <a href="images/screenshots/1.0-alpha7/transferControl_mainline.png" target="_blank" title="Torrentflux-b4rt BitTorrent Mainline Transfer Settings Screenshot">(screenshot)</a></li>
					<li><a href="http://www.bittornado.com/" title="Open BitTornado site in new window" target="_blank">BitTornado</a><br/>&mdash; uses slightly less resources than the original BT, allows file priority for downloading files selectively <a href="images/screenshots/1.0-alpha7/transferControl_tornado.png" target="_blank" title="Torrentflux-b4rt BitTornado Transfer Control Screenshot">(screenshot)</a></li>
					<li><a href="http://transmission.m0k.org/" title="Open Transmission site in new window" target="_blank">Transmission</a><br/>&mdash; much smaller memory footprint without much loss in functionality <a href="images/screenshots/1.0-alpha7/transferControl_transmission.png" target="_blank" title="Torrentflux-b4rt Transmission Transfer Settings Screenshot">(screenshot)</a></li>
					<li><a href="http://azureus.sourceforge.net/" title="Open Azureus site in new window" target="_blank">Azureus</a><br/>&mdash; control a number of transfers from a single control process, apply global bandwidth limits on all torrents <a href="images/screenshots/1.0-alpha7/transferControl_azureus.png" target="_blank" title="Torrentflux-b4rt Azureus Transfer Settings Screenshot">(screenshot)</a></li>
				</ul>
			</li>

			<li>
				HTTP/FTP - supported client:
				<ul>
					<li><a href="http://www.gnu.org/software/wget/" title="Open wget site in new window" target="_blank">wget</a><br/>&mdash; standard lightweight file transfer utility on Linux, supported on many platforms <a href="images/screenshots/1.0-alpha7/transferControl_wget.png" target="_blank" title="Torrentflux-b4rt wget Transfer Control Screenshot">(screenshot)</a></li>
				</ul>
			</li>

			<li>
				Usenet - supported client:
				<ul>
					<li><a href="http://noisybox.net/computers/nzbperl/" title="Open nzbperl site in new window" target="_blank">nzbperl</a><br/>&mdash; perl based application allowing multi-connection news server downloads from nzb files with functionality for bandwidth throttling <a href="images/screenshots/1.0-alpha7/transferControl_nzbperl.png" target="_blank" title="Torrentflux-b4rt nzbperl Screenshot">(screenshot)</a></li>
				</ul>
			</li>
		</ul>
	</div>

	<div class="subcontent">
		<h2 id="xfer_control"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Unified transfer control</h2>
		Torrentflux-b4rt allows you to control all your transfers in one place easily:<br/>
		<ul>
			<li>Perform stop/start/resume/kill/delete operations on individual transfers, all transfers or a selection of transfers</li>
			<li>Change settings of running transfers on the fly - down/up rates, what ratio to stop seeding at, how many connections to use at same time, etc <a href="images/screenshots/1.0-alpha7/transferSettings_runtime.png" target="_blank" title="Torrentflux-b4rt Dynamic Settings Update Screenshot">(screenshot)</a></li>
		</ul>
	</div>

	<div class="subcontent">
		<h2 id="xfer_stats"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Transfer statistics and logging</h2>
		View detailed transfer statistics and information <a href="images/screenshots/1.0-alpha7/transferStats_torrent.png" target="_blank" title="Torrentflux-b4rt Transfer Stats Screenshot">(screenshot)</a>, including:<br/>
		<ul>
				<li>Per transfer error logging for easier troubleshooting</li>
				<li>Upload/download totals for each user, by day/month/year</li>
				<li>Number of seeders/leechers/etc for a torrent in a graphical display</li>
		</ul>
	</div>

	<div class="subcontent">
		<h2 id="metafiles"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Uploading and injection of metafiles (.torrent, .wget, .nzb files)</h2>
		Upload torrent/wget/nzb files one at a time or all at once:<br/>
		<ul>
			<li>Upload single or multiple metafiles from your local machine to the web server</li>
			<li>Upload metafiles directly to your web server from another web server</li>
			<li>Multiple operations in "fluxcli.php" allow inject and more from command-line (cron, etc.)<br/>ie: "inject", "watch", "rss"</li>
		</ul>
	</div>

	<div class="subcontent">
		<h2 id="fluxcli"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;fluxcli.php - a complete command-line version of torrentflux-b4rt</h2>
		fluxcli.php can perform all the tasks available in the torrentflux-b4rt frontend but from the commandline.  Makes it ideal for running scheduled tasks from a cron job:<br/>
		<ul>
			<li>Schedule cron jobs to check RSS feeds on a regular basis and download them to a directory.</li>
			<li>Schedule cron jobs to watch folders for new torrent files and then autostart/inject them</li>
			<li>Check up on the status of transfers directly from a Unix shell</li>
		</ul>
	</div>

	<div class="subcontent">
		<h2 id="fluxd"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Fluxd - background perl daemon to perform scheduled tasks</h2>
		Fluxd is a powerful backend daemon that can run 24/7 to control various aspects of your file transfers <a href="images/screenshots/1.0-alpha7/admin_fluxdSettings.png" target="_blank" title="Torrentflux-b4rt Fluxd Screenshot">(screenshot)</a>:<br/>
		<ul>
			<li>Qmgr module handles queueing of transfers with per-user and global limits.  Add transfers to the queue and Qmgr will automatically start one transfer after another finishes.</li>
			<li>Automate fetching of torrent files from RSS feeds</li>
			<li>Watch a list of directories for new upload of torrent files and automatically start those torrents running</li>
		</ul>
	</div>

	<div class="subcontent">
		<h2 id="filemanager"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Integrated Filemanager</h2>
		Support for a large number of additional third party utilities/functionality <a href="images/screenshots/1.0-alpha7/admin_dirSettings.png" target="_blank" title="Torrentflux-b4rt Dir Settings Screenshot">(screenshot)</a> <a href="images/screenshots/1.0-alpha7/dir.png" target="_blank" title="Torrentflux-b4rt File Manager Screenshot">(screenshot)</a>, including:<br/>
		<ul>
				<li>Archive file extraction from the browser (zip/rar)</li>
				<li>VLC streaming controllable from browser <a href="images/screenshots/1.0-alpha7/vlc.png" target="_blank" title="Torrentflux-b4rt VLC Streaming Screenshot">(screenshot)</a></li>
				<li>Download of completed transfers directly from browser</li>
				<li>Creation of torrent files directly in the browser <a href="images/screenshots/1.0-alpha7/maketorrent.png" target="_blank" title="Torrentflux-b4rt Torrent Creation Screenshot">(screenshot)</a></li>
				<li>Reading of .nfo files directly in the browser</li>
		</ul>
	</div>

	<div class="subcontent">
		<h2 id="ajax"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;AJAX updates for maximum info with minimal bandwidth</h2>
		AJAX cuts down on the amount of bandwidth used to display data and creates an experience similar to a 'standalone' application:<br/>
		<ul>
			<li>Transfer lists update stats in real time - saves on bandwidth since only the transfer list needs to be sent across the network, not the whole web page</li>
			<li>Individual transfer windows can also use AJAX to update stats in real time</li>
		</ul>
	</div>

	<div class="subcontent">
		<h2 id="templating"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Templating Engine</h2>
		Torrentflux-b4rt uses a flexible templating engine to allow development of the frontend look and feel:<br/>
		<ul>
			<li>The torrentflux-b4rt GUI is template driven using <a href="http://vlib.clausvb.de/vlibtemplate.php" target="_blank">the vLib template engine</a>.  This allows developers to completely redesign the look and feel of torrentflux-b4rt without having to worry about the underlying PHP codebase.</li>
			<li>Torrentflux-b4rt also incorporates template caching to speed up the load time of pages.  This feature can be enabled via the Administration control panel.</li>
		</ul>
	</div>
<?php
}

/**
 * prints page "news"
 */
function printPageNews() {
?>
	<div class="subcontent">
		<h1 id="news">News</h1>
		<ul>
			<?php echo(rewriteNews(trim(getDataFromFile(_FILE_NEWS)))); ?>
		</ul>
		<p>More detailed <a href="https://tf-b4rt.berlios.de/forum/index.php/board,9.0.html" title="Announcements and News">Announcements and News</a> can be found in the <a href="forum" title="Forum">Forum</a>.</p>
	</div>

	<div class="subcontent">
		<h1 id="feeds">Feeds</h1>
		<ul>
			<li>News (<a href="https://developer.berlios.de/export/rss_bsnews.php?group_id=7000" title="News - RSS 0.91">RSS 0.91</a>/<a href="https://developer.berlios.de/export/rss20_bsnews.php?group_id=7000" title="News - RSS 2.0">RSS 2.0</a>)</li>
			<li>Downloads (<a href="https://developer.berlios.de/export/rss_bsnewreleases.php?group_id=7000" title="Downloads - RSS 0.91">RSS 0.91</a>/<a href="https://developer.berlios.de/export/rss20_bsnewreleases.php?group_id=7000" title="Downloads - RSS 2.0">RSS 2.0</a>)</li>
			<li>Forum (<a href="https://tf-b4rt.berlios.de/forum/index.php?type=rss;action=.xml" title="Forum - RSS 0.92">RSS 0.92</a>)</li>
		</ul>
</div>
<?php
}

/**
 * prints page "about"
 */
function printPageAbout() {
?>
	<div class="subcontent">
		<h1 id="contact">Contact Details</h1>
		<p>
			Please use <a href="forum" title="Torrentflux-b4rt Forum">the torrentflux-b4rt forum</a> for any support related queries.<br/>
			For all other <strong>non-support</strong> related queries:<br/>
		</p>
		<ul><li><script language="javascript" type="text/javascript">printMailLink('tfb4rt[AT]gmail[DOT]com');</script><noscript>tfb4rt[AT]gmail[DOT]com</noscript></li></ul>
	</div>
<?php
		$authors = getAuthors();
		if (strlen($authors) > 0) {
			?>
				<div class="subcontent">
					<h1 id="authors">Torrentflux-b4rt Authors</h1>
					<p>
						Torrentflux-b4rt is written and maintained by:
					</p>
					<?php echo $authors; ?>
					<p>
					A great debt is owed to the original Torrentflux's author - Qrome - as well as all the authors of the original hacks who are too numerous to mention here.  Whilst every single 'hack' has been engineered and tweaked by b4rt to be added into torrentflux-b4rt, without Qrome and the authors of the hacks many of those cool features and ideas might never have made it into torrentflux-b4rt.  With this, many thanks go out to Qrome and the numerous authors of hacks and mods to the original torrentflux.
					</p>
					<p>
						Please note that if you feel you wish to be quoted as an originating author of a feature that exists in torrentflux-b4rt, please contact us providing details of your involvement and we will endeavour to add your name to this page.
					</p>
				</div>
			<?php
		}
	?>
	<div class="subcontent">
		<h1 id="history">Torrentflux-b4rt History</h1>
		<p>
			<a href="images/screenshots/1.0-alpha7/profile.png" title="torrentflux-b4rt 1.0 alpha7: Profile-Page" target="_blank"><img src="images/10a7_profile_small.png" width="341" height="365" border="0" alt="torrentflux-b4rt 1.0 alpha7: Profile-Page" align="right" class="img_right" /></a>
		</p>
		<p>
			The <strong>Torrentflux-b4rt</strong> project started as an enhancement to the base <a href="http://www.torrentflux.com/" title="Torrentflux">TorrentFlux</a> 2.1 installation.
		</p>
		<p>
			Users began to submit their own 'hacks' or modifications to the base torrentflux system on the torrentflux forum, each of which provided enhanced functionality to the core system which was 'a great thing' &trade;.
		</p>
<br clear="all"/>
<br/>
		<p>
			However, for all the goodwill in the world, the method for modifying the base torrentflux system to 'install' these hacks was very unstructured by nature - at best a list of instructions:
		</p>
		<ol>
			<li>add this code here at line 12.</li>
			<li>change this code after line 33.</li>
			<li>rewrite this line 44 to read ...</li>
			<li>etc</li>
		</ol>
		<p>
			Pretty soon installing additional hacks became overwhelming because every time you added a new hack, the base system would be changed to such an extent that following any instructions for installing further hacks became just infeasible. With this in mind, torrentflux-b4rt was an attempt to sidestep the complicated mess of adding hacks and modifications to the base torrentflux system in a random way, as well as allowing users more choice in which BitTorrent clients they used with torrentflux.
		</p>
		<p>
			The first incarnation of torrentflux-b4rt was a branch from the original torrentflux codebase to include as many of the best user submitted hacks as possible and integration of other torrent clients other than torrentflux's default bittornado.  Importantly though, torrentflux-b4rt included administration settings to control most of the added modifications, something that few or none of the original hacks ever did.  With this it became a lot simpler, more secure and efficient to run a number of hacks together.
		</p>
	</div>

	<div class="subcontent">
		<h1 id="currentwork">Current Work On Torrentflux-b4rt</h1>
		<p>
			Around the start of 2007, the torrentflux-b4rt codebase was almost completely rewritten to address a number of issues:
		</p>
		<ul>
			<li>Allow easier integration of transfer clients - not necessarily just bittorrent.  <a href="features.html#protocols" title="Transfer protocols and software supported by Torrentflux-b4rt">This effort has seen the inclusion of other transfer clients such as wget, nzbperl and azureus.</a></li>
			<li>Allow scheduled tasks to run in the background via the Fluxd daemon.  <a href="features.html#fluxd" title="Use Fluxd to schedule rss downloads, queue management of torrents and more">Fluxd is a server that can be started from torrentflux-b4rt to run scheduled tasks on a server without the need to use cron.  Fluxd uses modules to perform each type of scheduled task.</a></li>
			<li>Allow the frontend to be redesigned more easily.  <a href="features.html#templating" title="Torrentflux-b4rt uses vlib templating engine to allow easier redesign of frontend">Torrentflux-b4rt now uses a templating engine to allow developers to redesign the look and feel of the torrentflux-b4rt frontend more easily.</a></li>
		</ul>
		<p>
			Somewhat confusingly, this latest incarnation of torrentflux-b4rt is named 'torrentflux-b4rt_1.0' (to indicate this is the first release of the newly rewritten b4rt codebase, whereas the older torrentflux-b4rt is named 'torrentflux_2.1-b4rt' (to indicate this is the b4rt codebase based on the original torrentflux 2.1).  Very confusing!
		</p>
		<p>
			Current work on torrentflux-b4rt is aimed at a 'final' or so-called 'production' release for the project - making the codebase as stable as possible, ironing out as many bugs as possible, making the user interface as easy to use as possible and getting a set of documentation together that will complement the project and make using torrentflux-b4rt as easy as possible.  Work is also under way to make the HTML in the frontend <a href="http://validator.w3.org/" title="W3C HTML Validation">XHTML compliant</a> which will make the interface easier to use, less cluttered and compatible on as many browsers and platforms as possible.
		</p>
	</div>

	<div class="subcontent">
		<h1 id="irc">Torrentflux-b4rt IRC Chat</h1>
		<p>
			The official Internet Relay Chat (IRC) server and channel for Torrentflux-b4rt are:<br/>
			<br/>
			Server: <b>chat.freenode.net</b><br/>
			Channel: <b>#tfb4rt</b><br/>
			<br/>
			<b>Notes:</b><br/>
			The Freenode IRC network has a large number of servers located around the world - you can use the domain name 'chat.freenode.net', but you will be connected to a random server which might not be geographically close to you.  To find one closest to you, see this page:<br/>
			<br/>
			<a href="http://freenode.net/irc_servers.shtml">Freenode IRC Server List</a><br/>
			<br/>
			Also, please consider registering a nickname on the Freenode network if you intend to spend a lot of time (idling!) there - for the benefits of and help with registering a nick, see these pages:<br/>
			<br/>
			<a href="http://freenode.net/faq.shtml#registering">Why should I register a nick on Freenode</a><br/>
			<a href="http://freenode.net/faq.shtml#nicksetup">Registering a nickname on Freenode</a><br/>
		</p>
	</div>
<?php
}

/**
 * prints page "changelog"
 */
function printPageChangelog() {
?>
	<div class="subcontent">
		<h1 id="changelog">Changelog</h1>
		<pre class="changelog"><?php echo trim(getDataFromFile(_FILE_CHANGELOG)); ?></pre>
	</div>
<?php
}

/**
 * prints page "screenshots"
 */
function printPageScreenshots() {
?>
		<h1 id="screenshots">Screenshots</h1>
		<h2 id="screenshots-1.0-alpha7">1.0 alpha7</h2>
		<?php echo getScreenshotList('images/screenshots/1.0-alpha7/'); ?>
<?php
}

/* EOF */ ?>
