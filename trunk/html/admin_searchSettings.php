<?php
/* $Id: admin_searchSettings.php 102 2006-07-31 05:01:28Z msn_exploder $ */
require_once("AliasFile.php");
require_once("RunningTorrent.php");
require_once("searchEngines/SearchEngineBase.php");
echo DisplayHead("Administration - Search Settings");
// Admin Menu
echo displayMenu();
// Main Settings Section
echo "<div align=\"center\">";
echo "<table width=\"100%\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
echo "<tr><td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">Search Settings</font>";
echo "</td></tr><tr><td align=\"center\">";
?>
<div align="center">
	<table cellpadding="5" cellspacing="0" border="0" width="100%">
	<form name="theForm" action="admin.php?op=searchSettings" method="post">
	<tr>
		<td align="right" width="350" valign="top"><strong>Select Search Engine</strong><br>
		</td>
		<td valign="top">
<?php
			$searchEngine = getRequestVar('searchEngine');
			if (empty($searchEngine))
				$searchEngine = $cfg["searchEngine"];
			echo buildSearchEngineDDL($searchEngine,true)
?>
		</td>
	</tr>
	</form>
	</table>
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr><td>
<?php
if (is_file('searchEngines/'.$searchEngine.'Engine.php')) {
	include_once('searchEngines/'.$searchEngine.'Engine.php');
	$sEngine = new SearchEngine(serialize($cfg));
	if ($sEngine->initialized) {
		echo "<table width=\"100%\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\"><tr>";
		echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\"><img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">".$sEngine->mainTitle." Search Settings</font></td>";
		echo "</tr></table></td>";
		echo "<form name=\"theSearchEngineSettings\" action=\"admin.php?op=updateSearchSettings\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"searchEngine\" value=\"".$searchEngine."\">";
?>
		</td>
	</tr>
	<tr>
		<td>

	<table cellpadding="5" cellspacing="0" border="0" width="100%">
	<tr>
		<td align="left" width="350" valign="top"><strong>Search Engine URL:</strong></td>
		<td valign="top">
			<?php echo "<a href=\"http://".$sEngine->mainURL."\" target=\"_blank\">".$sEngine->mainTitle."</a>"; ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Search Module Author:</strong></td>
		<td valign="top">
			<?php echo $sEngine->author; ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Version:</strong></td>
		<td valign="top">
			<?php echo $sEngine->version; ?>
		</td>
	</tr>
<?php
	if(strlen($sEngine->updateURL)>0) {
?>
	<tr>
		<td align="left" width="350" valign="top"><strong>Update Location:</strong></td>
		<td valign="top">
			<?php echo "<a href=\"".$sEngine->updateURL."\" target=\"_blank\">Check for Update</a>"; ?>
		</td>
	</tr>
<?php
	}
		if (! $sEngine->catFilterName == '') {
?>
	<tr>
		<td align="left" width="350" valign="top"><strong>Search Filter:</strong><br>
		Select the items that you DO NOT want to show in the torrent search:
		</td>
		<td valign="top">
<?php
			echo "<select multiple name=\"".$sEngine->catFilterName."[]\" size=\"8\" STYLE=\"width: 125px\">";
			echo "<option value=\"\">[NO FILTER]</option>";
			foreach ($sEngine->getMainCategories(false) as $mainId => $mainName) {
				echo "<option value=\"".$mainId."\" ";
				if (@in_array($mainId, $sEngine->catFilter))
					echo " selected";
				echo ">".$mainName."</option>";
			}
			echo "</select>";
			echo "			  </td>\n";
			echo "		  </tr>\n";
		}
	}
}
?>
</table>
	</td></tr></table>
		<br
		<input type="Submit" value="Update Settings">
		</form>
		</div>
		<br>
	</td></tr>
</table></div>
<?php
echo DisplayFoot(true,true);
?>