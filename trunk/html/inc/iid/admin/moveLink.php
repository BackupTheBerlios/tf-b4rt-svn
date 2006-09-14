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

if (!isset($lid) && !isset($direction) && $direction !== "up" && $direction !== "down") {
	header("location: index.php?iid=admin&op=editLinks");
	exit();
}

$idx=getLinkSortOrder($lid);
$position = array("up"=>-1, "down"=>1);
$new_idx = $idx + $position[$direction];
$sql = "UPDATE tf_links SET sort_order = $idx WHERE sort_order = $new_idx";
$db->Execute($sql);
showError($db, $sql);
$sql = "UPDATE tf_links SET sort_order = $new_idx WHERE lid = $lid";
$db->Execute($sql);
showError($db, $sql);
header("Location: index.php?iid=admin&op=editLinks");

?>