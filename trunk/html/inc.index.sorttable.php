<?php

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

if(! isset($_SESSION['user'])) {
    header('location: login.php');
    exit();
}

// =============================================================================

?>
<script src="sorttable.js" type="text/javascript"></script>
<style type="text/css">
table.sortable a.sortheader {
    color: white;
    text-decoration: none;
    font-weight: bold;
}
table.sortable span.sortarrow {
    color: white;
    text-decoration: none;
}
</style>