<?php

/**
 * <p> $Id$ </p>
 * @version $Revision$
 */

/* -------------------------------------------------------------------------- */

/* defines */
define('_FILE_NEWS','newshtml.txt');
define('_FILE_VERSION','version.txt');
define('_FILE_CHANGELOG','changelog.txt');

/* global fields */
$version = "";
$site = "";

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// get current version
$version = trim(getDataFromFile(_FILE_VERSION));

// init site-var
if (isset($_REQUEST["s"]))
	$site = $_REQUEST["s"];
else
	$site = "index";

// print page
printPageHead();
switch($site) {
	case "features":
		printPageFeatures();
	break;
	case "news":
		printPageNews();
	break;
	case "changelog":
		printPageChangelog();
	break;
	case "index":
	case "about":
	default:
		printPageAbout();
	break;
}
printPageFoot();

// exit
exit();

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * load data of file
 *
 * @param $file the file
 * @return data
 */
function getDataFromFile($file) {
    // read content
    if($fileHandle = @fopen($file,'r')) {
        $data = null;
        while (!@feof($fileHandle))
            $data .= @fgets($fileHandle, 4096);
        @fclose ($fileHandle);
    }
    return $data;
}

/**
 * rewrite berliOS-news-export-HTML to fitting xhtml
 *
 * @param $string string with berliOS-news-export
 * @return string with news
 */
function rewriteNews($string) {
	// remove <hr>-tags
	$retVal = eregi_replace("<hr[[:space:]]*([^>]*)[[:space:]]*>", '', $string);
	// create list-elements from news-entries
	$retVal = eregi_replace("<a[[:space:]]*", '<li><a ', $retVal);
	$retVal = eregi_replace("<b>", '', $retVal);
	$retVal = eregi_replace("</b>", '', $retVal);
	$retVal = eregi_replace("<i>", '<em class="newsauthor">', $retVal);
	$retVal = eregi_replace("</i>", '</em></li>', $retVal);
	// spacer
	$retVal = eregi_replace("&nbsp;&nbsp;&nbsp;", '&nbsp;&nbsp;', $retVal);
	// remove news-archive-link
	$retVal = eregi_replace("<div.*</div>", '', $retVal);
	// return
	return $retVal;
}

// -----------------------------------------------------------------------------
// content
// -----------------------------------------------------------------------------

/**
 * prints page-head
 *
 */
function printPageHead() {
	global $version;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>torrentflux-b4rt</title>
    <meta name="description" content="Frontend to use various Transfer-Clients. Supports BitTornado, Transmission and wget." />
    <meta name="keywords" content="PHP, free, open, source, frontend, torrent, torrentflux, bittornado, transmission, b4rt" />
    <meta name="robots" content="index,follow" />
    <meta name="author" content="Design : DocTom ; Rest : b4rt" />
    <link rel="stylesheet" type="text/css" href="css/default.css" />
    <link rel="alternate" title="News - RSS 0.91" href="http://developer.berlios.de/export/rss_bsnews.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Releases - RSS 0.91" href="http://developer.berlios.de/export/rss_bsnewreleases.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="News - RSS 2.0" href="http://developer.berlios.de/export/rss20_bsnews.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Releases - RSS 2.0" href="http://developer.berlios.de/export/rss20_bsnewreleases.php?group_id=7000" type="application/rss+xml" />
    <link rel="alternate" title="Forum - RSS 0.92" href="http://tf-b4rt.berlios.de/forum/index.php?type=rss;action=.xml" type="application/rss+xml" />
</head>
<body>
<div id="container">
	<div id="header">
		<p class="version">
			<span class="versionspan">Current Version:</span>
			<a href="http://tf-b4rt.berlios.de/current" title="Current Version"><?php echo $version; ?></a>
		</p>
	</div>
	<div id="navi">
		<ul>
			<li><a href="about.html" title="About">About</a></li>
			<li><a href="features.html" title="Features">Features</a></li>
			<li><a href="news.html" title="News">News</a></li>
			<li><a href="http://tf-b4rt.berlios.de/downloads" title="Downloads">Downloads</a></li>
			<li><a href="http://tf-b4rt.berlios.de/faq" title="Faq">Faq</a></li>
			<li><a href="http://tf-b4rt.berlios.de/forum" title="Forum">Forum</a></li>
		</ul>
	</div>
	<div id="content">
<?php
}

/**
 * prints page-foot
 *
 */
function printPageFoot() {
?>
		<div id="footersection" class="section" align="right">
			<p><a href="#header" title="goto top" class="sectionlink">top</a></p>
		</div>
		<div id="credits">
			<p>
				<a href="http://developer.berlios.de/projects/tf-b4rt/" title="BerliOS Developer Project" target="_blank">
					<img src="http://developer.berlios.de/bslogo.php?group_id=7000" width="124px" height="32px" border="0" alt="BerliOS Developer Logo" />
				</a>
			</p>
			<p class="svnid">
				<a href="http://tf-b4rt.berlios.de/wsvn-website" title="WebSVN" target="_blank">$Id$</a>
			</p>
		</div>
	</div>
</div>
</body>
</html>
<?php
}

/**
 * prints page "news"
 *
 */
function printPageNews() {
?>
		<h1 id="news">News</h1>
		<ul>
			<?php echo(rewriteNews(trim(getDataFromFile(_FILE_NEWS)))); ?>
		</ul>
		<p>More detailed <a href="http://tf-b4rt.berlios.de/forum/index.php/board,9.0.html" title="Announcements and News">Announcements and News</a> can be found in the <a href="http://tf-b4rt.berlios.de/forum" title="Forum">Forum</a>.</p>
		<h2 id="feeds">Feeds</h2>
		<ul>
			<li>News (<a href="http://developer.berlios.de/export/rss_bsnews.php?group_id=7000" title="News - RSS 0.91">RSS 0.91</a>/<a href="http://developer.berlios.de/export/rss20_bsnews.php?group_id=7000" title="News - RSS 2.0">RSS 2.0</a>)</li>
			<li>Downloads (<a href="http://developer.berlios.de/export/rss_bsnewreleases.php?group_id=7000" title="Downloads - RSS 0.91">RSS 0.91</a>/<a href="http://developer.berlios.de/export/rss20_bsnewreleases.php?group_id=7000" title="Downloads - RSS 2.0">RSS 2.0</a>)</li>
			<li>Forum (<a href="http://tf-b4rt.berlios.de/forum/index.php?type=rss;action=.xml" title="Forum - RSS 0.92">RSS 0.92</a>)</li>
		</ul>
<?php
}

/**
 * prints page "about"
 *
 */
function printPageAbout() {
?>
		<h1 id="about">About torrentflux-b4rt</h1>
		<p>
			<a href="http://developer.berlios.de/dbimage.php?id=3024" title="torrentflux 2.1-b4rt-94 : Admin-Settings" target="_blank">
				<img src="images/v94-adminsettings_small.png" width="315px" height="300px" border="0" alt="torrentflux 2.1-b4rt-94 : Admin-Settings" align="right" class="img_right" />
			</a>
		</p>
		<p>
			<strong>torrentflux-b4rt</strong> is a frontend for various transfer-clients. It is based on TorrentFlux
			2.1 written by Qrome which can be found at <a href="http://www.torrentflux.com" title="www.torrentflux.com" target="_blank">www.torrentflux.com</a>.
			"based on" because it started with enhancing my installation with things i felt that were missing and rewriting
			some things that annoyed me... this resulted in major redesigns and rewrites in much parts and quite big
			enhancements over the last months. "major" and "big" compared relative to "small" size and complexity of
			whole project. v94 has about twice the lines of 2.1 final.
			Some of the user-submitted hacks/mods for 2.1 that were posted in the official TorrentFlux-forum i
			considered nice are also included, all modified at least in parts to be configurable in admin-page.
			The problems/bugs some hacks had were fixed and there are also some rewrites to have hacks "work together".
			Some hacks i rewrote simply because i wanted them to behave/work different or because it was needed
			for some of my new designs.
			Full list can be found in the <a href="http://tf-b4rt.berlios.de/changelog" title="Changelog">Changelog</a>
			and it is not a bad idea to read it at least once so you know what you get, what it can do and what it cant.
		</p>
<?php
}

/**
 * prints page "features"
 *
 */
function printPageFeatures() {
?>
		<h1 id="features">Features</h1>
		<ul>
			<li>Multi-Client-Support via "ClientHandler-API" which is basically some class-design and implementations
			to operate transparent on different transfer-clients at the same time. Atm supported are BitTornado,
			Transmission and wget (wget still hacked in without handler and is in a very messy state).</li>
			<li>Multi-QueueManager-Support. Class-Design and implementations to transparently choose and use a
			QueueManager for queueing of transfers. Included are 3 different implementations of a QueueManager-daemon.
			For details about differences in the daemons read <a href="http://tf-b4rt.berlios.de/changelog" title="Changelog">Changelog</a>.</li>
			<li>Persistent settings for torrent-clients. Settings (savepath, rates and limits...) for clients are stored in
			the database and persistent over client-restarts.</li>
			<li>Persistent totals for all torrent-clients. Up- and down-totals are persistent
			and share-ratio is calculated from "real totals" and not from "session totals".</li>
			<li>Multi-Operations of various types and more usability-improvements in the web-interface.</li>
			<li>Commandline-Interface "fluxcli.php".</li>
			<li>Nearly all tweakable can be tweaked from within the webapp-administration. Things are resorted + categorized
			and there is a new admin-section only for ui-tweaks.</li>
			<li>Superadmin-page offering backups, maintenance-operations, access to latest news/changelog/issues and a
			easy-to-use "web-update".</li>
			<li>Per-User settings (mostly ui-stuff).</li>
			<li>Linux and FreeBSD supported. Works on Linux and FreeBSD out of the box. (well... more or less, there are
			still some flaws on FreeBSD which will be fixed sooner or later ;)).</li>
			<li>MRTG-Graphs integrated with a poller (perl-script "fluxpoller.pl") and configs for traffic- and
			connection-graphs. (config-examples for more graphs included).</li>
			<li>Gadgets like rss-feed, dereferrer-page, dir-watch (with fluxcli.php) and netstat-addons which show
			connections of torrent-clients.</li>
			<li>Some hacks from the TorrentFlux-forum included.</li>
			<li>Many improvements and cleanups in the codebase. Much is rewritten to be more convenient, more generic
			and more modular.</li>
			<li>Much more... see <a href="http://tf-b4rt.berlios.de/changelog" title="Changelog">Changelog</a>.</li>
		</ul>
		<p>
			<a href="http://developer.berlios.de/dbimage.php?id=3023" title="torrentflux 2.1-b4rt-94 : Index-Page" target="_blank">
				<img src="images/v94-index_small.png" width="445px" height="220px" border="0" alt="torrentflux 2.1-b4rt-94 : Index-Page" align="left" class="img_left" />
			</a>
		</p>
		<p>
			<strong>Screenshot</strong> of one possible configuration of the new index-page.
			Old TorrentFlux 2.1 transfer-list is also included and can be selected in
			the index-page-settings. The code for old page is also heavy modified but basically it looks the same.
			Bottom-Stats, QueueManager-Stats and XFER-Stats can be enabled in both index-pages.
			(all disabled on this screenshot).
			Top-Right-Box can include links, usernames and statistics.
			The torrents on the screenshot are ran by different clients, the type can be seen in the
			column labeled with "C" where "B" is BitTornado and "T" is Transmission.
		</p>
		<p>
			<a href="http://developer.berlios.de/dbimage.php?id=3025" title="torrentflux 2.1-b4rt-94 : User-Profile" target="_blank">
				<img src="images/v94-userprofile_small.png" width="315px" height="337px" border="0" alt="torrentflux 2.1-b4rt-94 : User-Profile" align="right" class="img_right" />
			</a>
		</p>
		<p>
			<strong>Personalized</strong> settings accessible for users in their profile-page.
			There are only settings available on profile-page that "make sense" for a personalized config. (it is mostly
			ui-stuff). Global hacks or features can only be configured by admins in admin-settings. The settings
			in admin-settings-pages are also the defaults for new created users.
			Note that users settings are only saved on a submit of the profile-page when they are different from the
			defaults. After that is done there is no way anymore for admins to change users personalized
			settings within admin-pages.
		</p>
<?php
}

/**
 * prints page "changelog"
 *
 */
function printPageChangelog() {
?>
		<h1 id="changelog">Changelog</h1>
		<pre class="changelog"><?php echo trim(getDataFromFile(_FILE_CHANGELOG)); ?></pre>
<?php
}

/* EOF */ ?>