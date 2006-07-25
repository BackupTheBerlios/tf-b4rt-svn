<?php
echo DisplayHead(_ADMINEDITLINKS);
// Admin Menu
displayMenu();
echo "<div align=\"center\">";
echo "<table border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
// Link Mod
//echo "<tr><td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
//echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">"._ADMINEDITLINKS."</font>";
echo "<tr><td colspan=\"2\" bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">"._ADMINEDITLINKS."</font>";
//echo "</td></tr><tr><td align=\"center\">";
echo "</td></tr><tr><td colspan=2 align=\"center\">";
// Link Mod
?>
<form action="admin.php?op=addLink" method="post">
<?php echo _FULLURLLINK ?>:
<!-- Link Mod -->
<!-- <input type="Text" size="50" maxlength="255" name="newLink"> -->
<input type="Text" size="30" maxlength="255" name="newLink">
<?php echo _FULLSITENAME ?>:
<input type="Text" size="30" maxlength="255" name="newSite">
<!-- Link Mod -->
<input type="Submit" value="<?php echo _UPDATE ?>"><br>
</form>
<?php
echo "</td></tr>";
$arLinks = GetLinks();
$arLid = Array_Keys($arLinks);
$inx = 0;
// Link Mod
$link_count = count($arLinks);
// Link Mod
foreach($arLinks as $link) {
	$lid = $arLid[$inx++];
	// Link Mod
	//echo "<tr><td><a href=\"admin.php?op=deleteLink&lid=".$lid."\"><img src=\"images/delete_on.gif\" width=16 height=16 border=0 title=\""._DELETE." ".$lid."\" align=\"absmiddle\"></a>&nbsp;";
	//echo "<a href=\"".$link."\" target=\"_blank\">".$link."</a></td></tr>\n";
	if ( isset($_GET["edit"]) && $_GET["edit"] == $link['lid']) {
		echo "<tr><td colspan=\"2\">";
?>
<form action="admin.php?op=editLink" method="post">
<?php echo _FULLURLLINK ?>:
<input type="Text" size="30" maxlength="255" name="editLink" value="<?php echo $link['url'] ?>">
<?php echo _FULLSITENAME ?>:
<input type="Text" size="30" maxlength="255" name="editSite" value="<?php echo $link['sitename'] ?>">
<input type="hidden" name="lid" value="<?php echo $lid ?>">
<input type="Submit" value="<?php echo _UPDATE ?>"><br>
</form>
<?php
	} else {
		echo "<tr><td>";
		echo "<a href=\"admin.php?op=deleteLink&lid=".$lid."\"><img src=\"images/delete_on.gif\" width=16 height=16 border=0 title=\""._DELETE." ".$lid."\" align=\"absmiddle\"></a>&nbsp;";
		echo "<a href=\"admin.php?op=editLinks&edit=".$lid."\"><img src=\"images/edit.gif\" width=16 height=16 border=0 title=\""._EDIT." ".$lid."\" align=\"absmiddle\"></a>&nbsp;";
		echo "<a href=\"".$link['url']."\" target=\"_blank\">".$link['sitename']."</a></td>\n";
		echo "<td align=right width='36'>";
		if ($inx > 1 ){
			// Only put an 'up' arrow if this isn't the first entry:
			echo "<a href='admin.php?op=moveLink&amp;direction=up&amp;lid=".$lid."'>";
			echo "<img src='images/uparrow.png' width='16' height='16' ";
			echo "border='0' title='Move link up' align='absmiddle' alt='Up'></a>";
		}
		// If only one link, just put in a space in each cell:
		echo ($inx==1 ? "<img src='images/blank.gif' width='16' align='absmiddle'>" : "");
		echo "&nbsp;";
		if ($inx != count($arLinks)) {
			// Only put a 'down' arrow if this isn't the last item:
			echo "<a href='admin.php?op=moveLink&amp;direction=down&amp;lid=".$lid."'>";
			echo "<img src='images/downarrow.png' width='16' height='16' ";
			echo "border='0' title='Move link down' align='absmiddle' alt='Down'></a>";
		}
		echo "</td></tr>";
	}
	// Link Mod
}
echo "</table></div><br><br><br>";
echo DisplayFoot(true,true);
?>