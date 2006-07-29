<?php
/* $Id$ */
echo DisplayHead("Administration - RSS");
// Admin Menu
displayMenu();
echo "<div align=\"center\">";
echo "<table border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
echo "<tr><td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">RSS Feeds</font>";
echo "</td></tr><tr><td align=\"center\">";
?>
<form action="admin.php?op=addRSS" method="post">
<?php echo _FULLURLLINK ?>:
<input type="Text" size="50" maxlength="255" name="newRSS">
<input type="Submit" value="<?php echo _UPDATE ?>"><br>
</form>
<?php
echo "</td></tr>";
$arLinks = GetRSSLinks();
$arRid = Array_Keys($arLinks);
$inx = 0;
foreach($arLinks as $link) {
	$rid = $arRid[$inx++];
	echo "<tr><td><a href=\"admin.php?op=deleteRSS&rid=".$rid."\"><img src=\"images/delete_on.gif\" width=16 height=16 border=0 title=\""._DELETE." ".$rid."\" align=\"absmiddle\"></a>&nbsp;";
	echo "<a href=\"".$link."\" target=\"_blank\">".$link."</a></td></tr>\n";
}
echo "</table></div><br><br><br>";
echo DisplayFoot(true,true);
?>