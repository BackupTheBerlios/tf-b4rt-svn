<?php
error_reporting(E_ALL);

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
define('_FILE_VERSION_OLD', 'version-torrentflux_2.1-b4rt.txt');
define('_FILE_VERSION_NEW', 'version.txt');
define('_FILE_CHANGELOG', 'CHANGES');
define('_AUTHOR_FILE_URL', 'AUTHORS');

/* global fields */
$versions = array();
$page = "";

// functions
require_once('functions.php');

// default CSS to use:
$css="default";

// Temp feature to switch CSS sheets dynamically in testing.
// To change css sheet, call URL with vbl 'css=new' for the new sheet, setting will
// be kept for the current browser session.  To change back, use 'css=default'.
cssSwitcher();
function cssSwitcher(){
	global $css;

	// valid css sheets:
	$valid_css=array('default', 'new');

	session_start();

	// store css type in session if passed in request:
	isset($_REQUEST["css"]) && !empty($_REQUEST["css"]) && in_array($_REQUEST["css"], $valid_css) && $_SESSION["css"]=$_REQUEST["css"];

	// use css sheet type stored in session if there:
	isset($_SESSION["css"]) && !empty($_SESSION["css"]) && $css=$_SESSION["css"];
}

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// get current versions
$versions['old'] = @trim(getDataFromFile(_FILE_VERSION_OLD));
$versions['new'] = @trim(getDataFromFile(_FILE_VERSION_NEW));

// Array of 'page ids' => 'page titles'
$defaultTitle="Home Page - A BitTorrent and Internet Transfer Web Control Application";
$pages = array(
	"requirements"	=> "Installation and Configuration Requirements",
	"features"		=> "Features List",
	"news"			=> "News and Updates",
	"about"			=> "Authors, History and Contributers",
	"changelog"		=> "Changelog",
	"index"			=> $defaultTitle,
	"home"			=> $defaultTitle,
	"default"		=> $defaultTitle
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
	global $pages, $css, $versions;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Torrentflux-b4rt <?php print $pages[$page]; ?></title>
    <meta name="description" content="Torrentflux-b4rt is a web based control panel for managing BitTorrent, wget and Newzbin downloads.  Torrentflux-b4rt supports a number of transfer clients, including BitTorrent, BitTornado, Transmission, Azureus, wget and nzbperl.  Torrentflux-b4rt is based on the torrentflux download manager. Torrentflux-b4rt requires a web server, PHP and a database - MySQL, Postgresql or SQLite - to run."/>
	<meta name="keywords" content="torrentflux-b4rt, torrentflux, bittorrent, bittornado, transmission, azureus, nzbperl, wget, torrent, download, remote, control, bandwidth, controller, fluazu, fluxd, rss, feed, downloader, automate, automation, web, web-based, transfer, manager, management, php, mysql, postgresql, perl, python, free, freeware, open, opensource, oss, gui, frontend, b4rt, tfb4rt"/>
    <meta name="robots" content="index,follow" />
    <meta name="author" content="Design: DocTom; Rest: b4rt,munk" />
	<link rel="stylesheet" type="text/css" href="css/<?php print $css; ?>.css" />
    <link rel="alternate" title="News - RSS 0.91" href="https://developer.berlios.de/export/rss_bsnews.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Releases - RSS 0.91" href="https://developer.berlios.de/export/rss_bsnewreleases.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="News - RSS 2.0" href="https://developer.berlios.de/export/rss20_bsnews.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Releases - RSS 2.0" href="https://developer.berlios.de/export/rss20_bsnewreleases.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Forum - RSS 0.92" href="https://tf-b4rt.berlios.de/forum/index.php?type=rss;action=.xml" type="application/rss+xml" />
</head>
<body>
<div id="container">
	<div id="header">
		<p class="version">
			<span class="versionspan"><a href="/download-torrentflux-b4rt.html" title="<?php echo $versions['new'];?>">Download Current Version:<br/>Torrentflux-b4rt <?php echo $versions['new']; ?></a></span>
		</p>
	</div>
	<div id="navi">
		<ul>
			<li><a href="home.html" title="Home">Home</a></li>
			<li><a href="features.html" title="Features">Features</a></li>
			<li><a href="requirements.html" title="Requirements">Requirements</a></li>
			<li><a href="about.html" title="About">About</a></li>
			<li><a href="news.html" title="News">News</a></li>
			<li><a href="downloads" title="Downloads">Downloads</a></li>
			<li><a href="faq" title="Faq">Faq</a></li>
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
		<div id="footersection" align="right"><a href="#header" title="goto top">top</a></div>
		<div id="credits">
			<p>
				<a href="https://developer.berlios.de/projects/tf-b4rt/" title="BerliOS Developer Project" target="_blank"><!-- whilst testing <img src="https://developer.berlios.de/bslogo.php?group_id=7000" width="124px" height="32px" border="0" alt="BerliOS Developer Logo" /> --></a>
			</p>
			<p class="svnid">
				<a href="https://tf-b4rt.berlios.de/wsvn-website" title="WebSVN" target="_blank">$Id$</a>
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
 * Inserts Google Analytics javascript for tracking and analysis via:
 * https://www.google.com/analytics/
 */
function googleAnalytics(){
?>
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
	<script type="text/javascript">
_uacct = "UA-1343358-1";
urchinTracker();
</script>
<?php
}

/**
 * prints page "home"
 */
function printPageHome() {
?>
		<h1 id="home">Home</h1>
		<p>
			<a href="https://developer.berlios.de/dbimage.php?id=3023" title="torrentflux 2.1-b4rt-94: Index-Page" target="_blank"><img src="images/v94-index_mini.png" width="334px" height="165px" border="0" alt="torrentflux 2.1-b4rt-94: Index-Page" align="right" class="img_right" /></a>
		</p>
		<p><strong>Torrentflux-b4rt</strong> is a web based transfer control client.  Torrentflux-b4rt allows you to control your internet downloads / transfers from anywhere using a highly configurable web based front end.
		</p>

		<br clear="all"/>
		<p> Torrentflux-b4rt is very easy to install on a web server and includes a simple setup script which can be accessed from a web browser.  Just upload the files to your web server, run the setup script and your torrentflux-b4rt installation is ready to go.
		</p>
		<p>Torrentflux-b4rt was originally based on the <a href="http://www.torrentflux.com" title="www.torrentflux.com" target="_blank">TorrentFlux</a> BitTorrent controller written by Qrome, although has recently undergone a major rewrite to allow transparent integration with a number of transfer clients and protocols.  For a full list of features please see <a href="/features.html" title="Torrentflux-b4rt Features Page">the torrentflux-b4rt features page</a>.
		</p>
		<ul>
			<li><a href="/download-torrentflux-b4rt.html" title="Download Torrentflux-b4rt">Download Torrentflux-b4rt</a></li>
			<li><a href="/changelog.html" title="Torrentflux-b4rt Changelog">Torrentflux-b4rt Changelog</a></li>
		</ul>

<?php
}

/**
 * prints page "requirements"
 */
function printPageRequirements() {
?>
		<h1 id="requirements">Requirements</h1>
<div id="req-content">
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

		More details can be found in the file <a href="/README" title="Torrentflux-b4rt README file">README</a> file.
</div>
<?php
}

/**
 * prints page "features"
 */
function printPageFeatures() {
?>

	<h1 class="features">Features</h1>
<p>
	Some of the most popular features of torrentflux-b4rt are listed below.  This list is definitely NOT exhaustive, there are a massive number of features that can be configured via the torrentflux-b4rt admin panel!
</p>

<a name="toc"></a>
<div class="feature-title">Feature List:</div>
<div class="feature-content">
	<a href="#protocols">Supports multiple internet transfer protocols </a><br/>
	<a href="#xfer_control">Unified transfer control</a><br/>
	<a href="#xfer_stats">Transfer statistics and logging</a><br/>
	<a href="#metafiles">Uploading and injection of metafiles (.torrent, .wget, .nzb files)</a><br/>
	<a href="#fluxcli">fluxcli.php - a complete command-line version of torrentflux-b4rt</a><br/>
	<a href="#fluxd">Fluxd - background perl daemon toperform scheduled tasks</a><br/>
	<a href="#filemanager">Integrated Filemanager AJAX updates for maximum info with minimal bandwidth</a><br/>
	<a href="#ajax">AJAX updates for maximum info with minimal bandwidth</a><br/>
	<a href="#templating">Templating engine</a><br/>
</div>

<a name="protocols"></a>
<div class="feature-title"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Supports multiple internet transfer protocols</div>
<div class="feature-content">
	Torrentflux-b4rt supports other internet transfer protocols as well as just BitTorrent.  Integration of the various protocls is seamless, meaning you start, stop, restart transfers in the same manner regardless of the underlying transfer protocol.<br/>
	<br/>
	Supported protocols include:<br/><br/>
	<ul>
		<li>
			BitTorrent - supported client(s) include:
			<ul>
				<li><a href="http://www.bittorrent.com/" title="Open BitTorrent.com site in new window" target="_blank">Original BitTorrent/Mainline</a><br/>&mdash; supports trackerless torrents and encryption</li>
				<li><a href="http://www.bittornado.com/" title="Open BitTornado site in new window" target="_blank">BitTornado</a><br/>&mdash; uses slightly less resources than the original BT, allows file priority for downloading files selectively</li>
				<li><a href="http://transmission.m0k.org/" title="Open Transmission site in new window" target="_blank">Transmission</a><br/>&mdash; much smaller memory footprint without much loss in functionality</li>
				<li><a href="http://azureus.sourceforge.net/" title="Open Azureus site in new window" target="_blank">Azureus</a><br/>&mdash; control a number of transfers from a single control process, tighter control on total max bandwidth for all torrents</li>
			</ul>
		</li>

		<li>
			HTTP/FTP - supported client(s) include:
			<ul>
				<li><a href="http://www.gnu.org/software/wget/" title="Open wget site in new window" target="_blank">wget</a><br/>&mdash; standard lightweight file transfer utility on Linux, supported on many other platforms also</li>
			</ul>
		</li>

		<li>
			Usenet - supported client(s) include:
			<ul>
				<li><a href="http://noisybox.net/computers/nzbperl/" title="Open nzbperl site in new window" target="_blank">nzbperl</a><br/>&mdash; perl based application allowing multi-connection news server downloads from nzb files with functionality for bandwidth throttling.</li>
			</ul>
		</li>
	</ul>
</div>

<a name="xfer_control"></a>
<div class="feature-title">	<a href="#toc" title="Back To Feature List">^^</a>&nbsp;Unified transfer control</div>
<div class="feature-content">
	<a href="" title="Torrentflux-b4rt Multi-Ops" target="_blank"><img src="" width="" height="" border="0" alt="Torrentflux-b4rt Multi-Ops Screenshot" align="right" class="img_right" /></a>
	<br clear="all"/>
	Torrentflux-b4rt allows you to control all your transfers in one place easily:<br/><br/>
	<ul>
		<li>Perform stop/start/resume/kill/delete operations on individual transfers, all transfers or a selection of transfers</li>
        <li>Changes Settings of running transfers on the fly - down/up rates, what ratio to stop seeding at, how many connections to use at same time, ...</li>
	</ul>
</div>

<a name="xfer_stats"></a>
<div class="feature-title"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Transfer statistics and logging</div>
<div class="feature-content">
	<a href="" title="Transfer Stats" target="_blank"><img src="" width="" height="" border="0" alt="Transfer Stats Screenshot" align="right" class="img_right" /></a>
	<br clear="all"/>
	View detailed Transfer statistics and information, including:<br/><br/>
	<ul>
			<li>per transfer error logging for easy troubleshooting</li>
			<li>upload/download totals for each user, by day/month/year</li>
			<li>number of seeders/leechers for a torrent in a graphical display</li>
	</ul>
</div>

<a name="metafiles"></a>
<div class="feature-title"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Uploading and injection of metafiles (.torrent, .wget, .nzb files)</div>
<div class="feature-content">
	<a href="" title="Upload metafiles" target="_blank"><img src="" width="" height="" border="0" alt="Upload metafiles Screenshot" align="right" class="img_right" /></a>
	<br clear="all"/>
	Upload torrent/wget/nzb files one at a time or all at once:<br/><br/>
	<ul>
		<li>Upload single or multiple metafiles from your local machine to the web server</li>
		<li>Upload metafiles directly to your web server from another web server</li>
        <li>Multiple operations in "fluxcli.php" allow inject and more from command-line (cron, etc.)<br/>ie: "inject", "watch", "rss"</li>
	</ul>
</div>

<a name="fluxcli"></a>
<div class="feature-title"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;fluxcli.php - a complete command-line version of torrentflux-b4rt</div>
<div class="feature-content">
	fluxcli.php can perform all the tasks available in the torrentflux-b4rt frontend but from the commandline.  Makes it ideal for running scheduled tasks from a cron job:<br/><br/>
	<ul>
		<li>Schedule cron jobs to check RSS feeds on a regular basis and download them to a directory.</li>
		<li>Schedule cron jobs to watch folders for new torrent files and then autostart/inject them</li>
		<li>Check up on the status of transfers directly from a Unix shell</li>
	</ul>
</div>

<a name="fluxd"></a>
<div class="feature-title"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Fluxd - background perl daemon to perform scheduled tasks</div>
<div class="feature-content">
	<a href="" title="Fluxd: Qmgr" target="_blank"><img src="" width="" height="" border="0" alt="Fluxd: Qmgr Screenshot" align="right" class="img_right" /></a>
	<a href="" title="Fluxd: RSS Downloader" target="_blank"><img src="" width="" height="" border="0" alt="Fluxd: RSS Downloader Screenshot" align="right" class="img_right" /></a>
	<a href="" title="Fluxd: Watch directories" target="_blank"><img src="" width="" height="" border="0" alt="Fluxd: Watch directories Screenshot" align="right" class="img_right" /></a>
	<br clear="all"/>
	Fluxd is a powerful backend daemon that can run 24/7 to control various aspects of your file transfers:<br/><br/>
	<ul>
		<li>Qmgr module handles queueing of transfers with per-user and global limits.  Add transfers to the queue and Qmgr will automatically start one transfer after another finishes.</li>
		<li>Automate fetching of torrent files from RSS feeds</li>
		<li>Watch a list of directories for new upload of torrent files and automatically start those torrents running</li>
	</ul>
</div>

<a name="filemanager"></a>
<div class="feature-title"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Integrated Filemanager</div>
<div class="feature-content">
	<a href="" title="File Manager: Archive Extraction" target="_blank"><img src="" width="" height="" border="0" alt="File Manager: Archive Extraction Screenshot" align="right" class="img_right" /></a>
	<a href="" title="File Manager: VLC Streaming" target="_blank"><img src="" width="" height="" border="0" alt="File Manager: VLC Streaming Screenshot" align="right" class="img_right" /></a>
	<br clear="all"/>
	Support for a large number of additional third party utilities/functionality, including:<br/><br/>
	<ul>
			<li>archive file extraction from the browser (zip/rar)</li>
			<li>vlc streaming controllable from browser</li>
			<li>download of completed transfers directly from browser</li>
			<li>reading of .nfo files directly in the browser</li>
			<li>creation of torrent files directly in the browser</li>
	</ul>
</div>

<a name="ajax"></a>
<div class="feature-title"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;AJAX updates for maximum info with minimal bandwidth</div>
<div class="feature-content">
	AJAX cuts down on the amount of bandwidth used to display data from the torrentflux-b4rt webserver and creates an experience similar to a 'standalone' application:<br/><br/>
	<ul>
		<li>Display of transfer lists can be easily configured to use AJAX to update transfer stats in real time.  This saves on bandwidth since only the transfer list needs to be sent across the network, not the whole web page.</li>

		<li>Individual transfer windows can also use AJAX to update stats in real time.</li>
	</ul>
</div>

<a name="templating"></a>
<div class="feature-title"><a href="#toc" title="Back To Feature List">^^</a>&nbsp;Templating Engine</div>
<div class="feature-content">
	Torrentflux-b4rt uses a flexible templating engine to allow development of the frontend look and feel:<br/><br/>
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
		<h1 id="news">News</h1>
		<ul>
			<?php echo(rewriteNews(trim(getDataFromFile(_FILE_NEWS)))); ?>
		</ul>
		<p>More detailed <a href="https://tf-b4rt.berlios.de/forum/index.php/board,9.0.html" title="Announcements and News">Announcements and News</a> can be found in the <a href="https://tf-b4rt.berlios.de/forum" title="Forum">Forum</a>.</p>
		<h2 id="feeds">Feeds</h2>
		<ul>
			<li>News (<a href="https://developer.berlios.de/export/rss_bsnews.php?group_id=7000" title="News - RSS 0.91">RSS 0.91</a>/<a href="https://developer.berlios.de/export/rss20_bsnews.php?group_id=7000" title="News - RSS 2.0">RSS 2.0</a>)</li>
			<li>Downloads (<a href="https://developer.berlios.de/export/rss_bsnewreleases.php?group_id=7000" title="Downloads - RSS 0.91">RSS 0.91</a>/<a href="https://developer.berlios.de/export/rss20_bsnewreleases.php?group_id=7000" title="Downloads - RSS 2.0">RSS 2.0</a>)</li>
			<li>Forum (<a href="https://tf-b4rt.berlios.de/forum/index.php?type=rss;action=.xml" title="Forum - RSS 0.92">RSS 0.92</a>)</li>
		</ul>
<?php
}

/**
 * prints page "about"
 */
function printPageAbout() {
	/*
	 * Fetch the list of authors from the current svn webserver AUTHORS file.
	 * Parse the list and create an HTML list with links to each authors mail address
	 */
	$authors_html = "";
	$authors = array_slice(preg_split("/\n\n/", getDataFromFile(_AUTHOR_FILE_URL)),2);

	// $authors array size will be just one entry if the authors file couldn't be fetched for some reason:
	if(count($authors)>1){
		foreach($authors as $author){
			$authors_html.=preg_replace(
				"/^\*\s(.*)\s<(.*)>$/",
				"<li><a href=\"mailto:$2\" title=\"Email $1\">$1</a></li>\n",
				$author
			);
		}
		$authors_html="<ul>\n$authors_html</ul>\n";
	}
?>
		<h1 id="about-authors-title">Torrentflux-b4rt Authors</h1>
		<div id="about-authors">
			<p>
				The Torrentflux-b4rt codebase author list is as follows:
			</p>
<?php print $authors_html ?>
			<p>
			A great debt is owed to the original Torrentflux's author - Qrome - as well as all the authors of the original hacks who are too numerous to mention here.  Whilst every single 'hack' has been engineered and tweaked by b4rt to be added into torrentflux-b4rt, without Qrome and the authors of the hacks many of those cool features and ideas might never have made it into torrentflux-b4rt.  With this, many thanks go out to Qrome and the numerous authors of hacks and mods to the original torrentflux.
			</p>
			<p>
				Please note that if you feel you wish to be quoted as an originating author of a feature that exists in torrentflux-b4rt, please contact us providing details of your involvement and we will endeavour to add your name to this page in a contributors section.
			</p>
		</div>

		<h1 id="about-history-title">Torrentflux-b4rt History</h1>
		<div id="about-history">
			<p>
				<a href="https://developer.berlios.de/dbimage.php?id=3024" title="torrentflux 2.1-b4rt-94: Admin-Settings" target="_blank">
					<img src="images/v94-adminsettings_small.png" width="315px" height="300px" border="0" alt="torrentflux 2.1-b4rt-94: Admin-Settings" align="right" class="img_right" />
				</a>
			</p>
			<p>
				The <strong>Torrentflux-b4rt</strong> project started as an enhancement to the base <a href="http://www.torrentflux.com/" title="Torrentflux">TorrentFlux</a> 2.1 installation.  Users began to submit their own 'hacks' or modifications to the base torrentflux system on the Torrentflux Forum, each of which provided enhanced functionality to the core system which was 'a great thing' &trade;.
			</p>
			<br clear="all"/>
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
				Pretty soon installing additional hacks became overwhelming because every time you added a new hack, the base system would be changed to such an extent that following any instructions for installing further hacks became just unfeasible. With this in mind, torrentflux-b4rt was an attempt to sidestep the complicated mess of adding hacks and modifications to the base torrentflux system in a random way, as well as allowing users more choice in which BitTorrent clients they used with torrentflux.
			</p>
			<p>
				The first incarnation of torrentflux-b4rt was a branch from the original torrentflux codebase to include as many of the best user submitted hacks as possible and integration of other torrent clients other than torrentflux's default bittornado.  Importantly though, torrentflux-b4rt included administration settings to control most of the added modifications, something that few or none of the original hacks ever did.  With this it became a lot simpler, more secure and efficient to run a number of hacks together.
			</p>
		</div>

		<h1 id="about-current-title">Current Work On Torrentflux-b4rt</h1>
		<div id="about-current">
			<p>
				Around the start of 2007, the torrentflux-b4rt codebase was almost completely rewritten to address a number of issues:
			</p>
			<ul>
				<li>Allow easier integration of transfer clients - not necessarily just bittorrent.  <a href="/features.html#protocols" title="Transfer protocols and software supported by Torrentflux-b4rt">This effort has seen the inclusion of other transfer clients such as wget, nzbperl and azureus.</a></li>
				<li>Allow scheduled tasks to run in the background via the Fluxd daemon.  <a href="/features.html#fluxd" title="Use Fluxd to schedule rss downloads, queue management of torrents and more">Fluxd is a server that can be started from torrentflux-b4rt to run scheduled tasks on a server without the need to use cron.  Fluxd uses modules to perform each type of scheduled task.</a></li>
				<li>Allow the frontend to be redesigned more easily.  <a href="/features.html#templating" title="Torrentflux-b4rt uses vlib templating engine to allow easier redesign of frontend">Torrentflux-b4rt now uses a templating engine to allow developers to redesign the look and feel of the torrentflux-b4rt frontend more easily.</a></li>
			</ul>
			<p>
				Somewhat confusingly, this latest version of torrentflux-b4rt is named 'torrentflux-b4rt_1.0-alphaX' (to indicate this is the first release of the newly rewritten b4rt codebase and 'X' represents the minor versioning), whereas the older torrentflux-b4rt is named 'torrentflux_2.1-b4rt-vX (to indicate this is the b4rt codebase based on the original torrentflux 2.1, where 'X' represents the minor versioning.  Very confusing! 
			</p>
			<p>
				<strong>1.0-alpha is the currently stable release of torrentflux-b4rt!</strong>  Whilst this may not seem obvious given the 'alpha' tag, you can rest assured that the currently available tarball is tested enough for it to be stable to use without breaking anything.
			</p>
		</div>
<?php
}

/**
 * prints page "changelog"
 */
function printPageChangelog() {
?>
		<h1 id="changelog">Changelog</h1>
		<pre class="changelog"><?php echo trim(getDataFromFile(_FILE_CHANGELOG)); ?></pre>
<?php
}

/* EOF */ ?>