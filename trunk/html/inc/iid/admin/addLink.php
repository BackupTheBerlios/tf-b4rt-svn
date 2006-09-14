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

$newLink = getRequestVar('newLink');
$newSite = getRequestVar('newSite');

if (!empty($newLink)){
	if (strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0)
		$newLink = "http://".$newLink;
	empty($newSite) && $newSite = $newLink;
	addNewLink($newLink, $newSite);
	AuditAction($cfg["constants"]["admin"], "New ".$cfg['_LINKS_MENU'].": ".$newSite." [".$newLink."]");
}
header("location: index.php?iid=admin&op=editLinks");

?>