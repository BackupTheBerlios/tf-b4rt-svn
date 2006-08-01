<?php
/* $Id: admin_uiSettings.php 102 2006-07-31 05:01:28Z msn_exploder $ */
// load global settings + overwrite per-user settings
loadSettings();
// display
echo DisplayHead("Administration - UI Settings");
// Admin Menu
echo displayMenu();
// Main Settings Section
?>
<div align="center">
<table width="100%" border="1" bordercolor="<?php echo $cfg["table_admin_border"] ?>" cellpadding="2" cellspacing="0" bgcolor="<?php echo $cfg["table_data_bg"] ?>">
<tr><td bgcolor="<?php echo $cfg["table_header_bg"] ?>" background="themes/<?php echo $cfg["theme"] ?>/images/bar.gif">
<img src="images/properties.png" width="18" height="13" border="0">&nbsp;&nbsp;<font class="title">UI Settings</font>
</td></tr><tr><td align="center">
<div align="center">
	 <table cellpadding="5" cellspacing="0" border="0" width="100%">
		<form name="theForm" action="admin.php?op=updateUiSettings" method="post">

	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Index-Page</strong></td></tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Select index-page</strong><br>
		Select the index-Page.
		</td>
		<td valign="top">
			<?php printIndexPageSelectForm(); ?>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>index-page settings</strong><br>
		Select the columns in transfer-list on index-Page.<br>(only for b4rt-index-page)
		</td>
		<td valign="top">
			<?php echo getIndexPageSettingsForm(); ?>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Width</strong><br>
		Specify the width of the index-page. (780):
		</td>
		<td valign="bottom">
			<input name="ui_dim_main_w" type="Text" maxlength="5" value="<?php echo($cfg["ui_dim_main_w"]); ?>" size="5">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Display Links</strong><br>
		Display Links on the index-page. (true):
		</td>
		<td valign="bottom">
			<select name="ui_displaylinks">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["ui_displaylinks"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Display Users</strong><br>
		Display Users on the index-page. (true):
		</td>
		<td valign="bottom">
			<select name="ui_displayusers">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["ui_displayusers"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Select Drivespace-Bar</strong><br>
		Select Style of Drivespace-Bar on index-Page.
		</td>
		<td valign="top">
			<?php printDrivespacebarSelectForm(); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Show Server Stats</strong><br>
		Enable showing the server stats at the bottom:
		</td>
		<td valign="top">
			<select name="index_page_stats">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["index_page_stats"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Show Server Load</strong><br>
		Enable showing the average server load over the last 15 minutes:
		</td>
		<td valign="top">
			<select name="show_server_load">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["show_server_load"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Show Connections</strong><br>
		Enable showing the Sum of TCP-Connections:
		</td>
		<td valign="top">
			<select name="index_page_connections">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["index_page_connections"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Use Refresh</strong><br>
		Use meta-refresh on index-page. (true):
		</td>
		<td valign="bottom">
			<select name="ui_indexrefresh">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["ui_indexrefresh"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Page Refresh (in seconds)</strong><br>
		Number of seconds before the torrent list page refreshes:
		</td>
		<td valign="top">
			<input name="page_refresh" type="Text" maxlength="3" value="<?php echo($cfg["page_refresh"]); ?>" size="3">
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Select Sort-Order</strong><br>
		Select default Sort-Order of transfers on index-Page.
		</td>
		<td valign="top">
			<?php echo getSortOrderSettingsForm(); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable sorttable</strong><br>
		Enable Client-Side sorting of Transfer-Table:
		</td>
		<td valign="top">
			<select name="enable_sorttable">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_sorttable"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Enable Good looking statistics</strong><br>
		Enable/Disable "Good looking statistics" :
		</td>
		<td valign="top">
			<select name="enable_goodlookstats">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_goodlookstats"])
						echo "selected";
					?>>false</option>
		   </select>
	   </td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Good looking statistics settings</strong><br>
		Configure Settings of "Good looking statistics" :
		</td>
		<td valign="top">
		<?php echo getGoodLookingStatsForm(); ?>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Enable Big bold drivespace warning</strong><br>
		Enable/Disable "Big bold drivespace warning" :
		</td>
		<td valign="top">
			<select name="enable_bigboldwarning">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_bigboldwarning"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>


	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Download-Details</strong></td></tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Width</strong><br>
		Specify the width of the details-popup. (450):
		</td>
		<td valign="bottom">
			<input name="ui_dim_details_w" type="Text" maxlength="5" value="<?php echo($cfg["ui_dim_details_w"]); ?>" size="5">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Height</strong><br>
		Specify the height of the details-popup. (290):
		</td>
		<td valign="bottom">
			<input name="ui_dim_details_h" type="Text" maxlength="5" value="<?php echo($cfg["ui_dim_details_h"]); ?>" size="5">
		</td>
	</tr>

	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Misc</strong></td></tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Display TorrentFlux Link</strong><br>
		Display TorrentFlux Link at bottom of pages. (true):
		</td>
		<td valign="bottom">
			<select name="ui_displayfluxlink">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["ui_displayfluxlink"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

		<tr><td colspan="2"><hr noshade></td></tr>
		<tr>
			<td align="center" colspan="2">
			<input type="Submit" value="Update Settings">
			</td>
		</tr>
		</form>
	</table>
</div>
</td></tr>
</table></div>
<?php
echo DisplayFoot(true,true);
?>