<?php

/**
 * <p> $Id$ </p>
 * @version $Revision$
 */

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

// file-defines
define('_FILE_NEWS','newshtml.txt');
define('_FILE_VERSION','version.txt');

// get + define current version
define('_VERSION_CURRENT',trim(getDataFromFile(_FILE_VERSION)));

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

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
   <title>TorrentFlux-b4rt</title>
   <link rel="stylesheet" type="text/css" href="/css/default.css">
   <link rel="alternate" title="News - RSS 0.91" href="http://developer.berlios.de/export/rss_bsnews.php?group_id=7000" type="application/rss+xml">
   <link rel="alternate" title="Releases - RSS 0.91" href="http://developer.berlios.de/export/rss_bsnewreleases.php?group_id=7000" type="application/rss+xml">
   <link rel="alternate" title="News - RSS 2.0" href="http://developer.berlios.de/export/rss20_bsnews.php?group_id=7000" type="application/rss+xml">
   <link rel="alternate" title="Releases - RSS 2.0" href="http://developer.berlios.de/export/rss20_bsnewreleases.php?group_id=7000" type="application/rss+xml">
</head>
<body onload="status=location.hostname;">

<!-- content -->
<table class="std">
 <tr>
  <td style="vertical-align:bottom; text-align:left" nowrap>
   <a href="/"><span class="path-root">TorrentFlux-b4rt</span></a>
  </td>
  <td style="vertical-align:top; text-align:right" nowrap>
   <div class="small">
    Current Version : <?php echo _VERSION_CURRENT; ?>
   </div>
  </td>
 </tr>
</table>
<hr class="header">
<br>
<a href="http://developer.berlios.de/project/showfiles.php?group_id=7000" target="_blank"
	onmouseover="status='Releases'; return true;"
	onmouseout="status=location.hostname; return true;" class="nav">
	<img src="/images/hand.right.gif" align="absmiddle" />
	Releases
</a>
<br>
<a href="http://developer.berlios.de/projects/tf-b4rt/" target="_blank"
	onmouseover="status='BerliOS Developer Project'; return true;"
	onmouseout="status=location.hostname; return true;" class="nav">
	<img src="/images/hand.right.gif" align="absmiddle" />
	BerliOS Developer Project
</a>
<br>
<a href="http://www.torrentflux.com/forum/index.php/topic,1265.0.html" target="_blank"
	onmouseover="status='Thread on TorrentFlux-Forum'; return true;"
	onmouseout="status=location.hostname; return true;" class="nav">
	<img src="/images/hand.right.gif" align="absmiddle" />
	Thread on TorrentFlux-Forum
</a>
<br>
<a href="ftp://ftp.berlios.de/pub/tf-b4rt/misc/" target="_blank"
	onmouseover="status='misc Files'; return true;"
	onmouseout="status=location.hostname; return true;" class="nav">
	<img src="/images/hand.right.gif" align="absmiddle" />
	misc Files
</a>
<br><br>
<h4>News :</h4>
<table class="std">
 <tr>
  <td>
   <?php echo(getDataFromFile(_FILE_NEWS)); ?>
  </td>
 </tr>
</table>
<p>
<hr class="header">
<div class="small">$Id$</div>
<!-- end content -->

<!-- footer -->
<hr class="header">
<a href="http://developer.berlios.de" title="BerliOS Developer" target="_blank">
 <img src="http://developer.berlios.de/bslogo.php?group_id=7000" width="124px" height="32px" border="0" alt="BerliOS Developer Logo">
</a>
<!-- end footer -->

</body>
</html>