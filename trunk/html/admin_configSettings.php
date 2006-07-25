<?php
require_once("AliasFile.php");
require_once("RunningTorrent.php");
echo DisplayHead("Administration - Settings");
// Admin Menu
displayMenu();
// Main Settings Section
echo "<div align=\"center\">";
echo "<table width=\"100%\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
echo "<tr><td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">TorrentFlux Settings</font>";
echo "</td></tr><tr><td align=\"center\">";
?>
<script language="JavaScript">
function validateSettings() {
	var rtnValue = true;
	var msg = "";
	if (isNumber(document.theForm.max_upload_rate.value) == false) {
		msg = msg + "* Max Upload Rate must be a valid number.\n";
		document.theForm.max_upload_rate.focus();
	}
	if (isNumber(document.theForm.max_download_rate.value) == false) {
		msg = msg + "* Max Download Rate must be a valid number.\n";
		document.theForm.max_download_rate.focus();
	}
	if (isNumber(document.theForm.max_uploads.value) == false) {
		msg = msg + "* Max # Uploads must be a valid number.\n";
		document.theForm.max_uploads.focus();
	}
	if ((isNumber(document.theForm.minport.value) == false) || (isNumber(document.theForm.maxport.value) == false)) {
		msg = msg + "* Port Range must have valid numbers.\n";
		document.theForm.minport.focus();
	}
	if (isNumber(document.theForm.rerequest_interval.value) == false) {
		msg = msg + "* Rerequest Interval must have a valid number.\n";
		document.theForm.rerequest_interval.focus();
	}
	if (document.theForm.rerequest_interval.value < 10) {
		msg = msg + "* Rerequest Interval must 10 or greater.\n";
		document.theForm.rerequest_interval.focus();
	}
	if (isNumber(document.theForm.days_to_keep.value) == false) {
		msg = msg + "* Days to keep Audit Actions must be a valid number.\n";
		document.theForm.days_to_keep.focus();
	}
	if (isNumber(document.theForm.minutes_to_keep.value) == false) {
		msg = msg + "* Minutes to keep user online must be a valid number.\n";
		document.theForm.minutes_to_keep.focus();
	}
	if (isNumber(document.theForm.rss_cache_min.value) == false) {
		msg = msg + "* Minutes to Cache RSS Feeds must be a valid number.\n";
		document.theForm.rss_cache_min.focus();
	}
	if (isNumber(document.theForm.page_refresh.value) == false) {
		msg = msg + "* Page Refresh must be a valid number.\n";
		document.theForm.page_refresh.focus();
	}
	if (isNumber(document.theForm.sharekill.value) == false) {
		msg = msg + "* Keep seeding until Sharing % must be a valid number.\n";
		document.theForm.sharekill.focus();
	}
	if ((document.theForm.maxport.value > 65535) || (document.theForm.minport.value > 65535)) {
		msg = msg + "* Port can not be higher than 65535.\n";
		document.theForm.minport.focus();
	}
	if ((document.theForm.maxport.value < 0) || (document.theForm.minport.value < 0)) {
		msg = msg + "* Can not have a negative number for port value.\n";
		document.theForm.minport.focus();
	}
	if (document.theForm.maxport.value < document.theForm.minport.value) {
		msg = msg + "* Port Range is not valid.\n";
		document.theForm.minport.focus();
	}
	// maxcons
	if (isNumber(document.theForm.maxcons.value) == false) {
		msg = msg + "* Max Cons must be a valid number.\n" ;
	}
	// Specific save path
	if (isNumber(document.theForm.maxdepth.value) == false) {
		msg = msg + "* Max Depth must be a valid number.\n" ;
	}
	if (msg != "") {
		rtnValue = false;
		alert("Please check the following:\n\n" + msg);
	}
	return rtnValue;
}

function isNumber(sText) {
	var ValidChars = "0123456789";
	var IsNumber = true;
	var Char;
	for (i = 0; i < sText.length && IsNumber == true; i++) {
		Char = sText.charAt(i);
		if (ValidChars.indexOf(Char) == -1) {
			IsNumber = false;
		}
	}
	return IsNumber;
}
</script>

<div align="center">
	<table cellpadding="5" cellspacing="0" border="0" width="100%">
	<form name="theForm" action="admin.php?op=updateConfigSettings" method="post" onsubmit="return validateSettings()">
	<input type="Hidden" name="continue" value="configSettings">
	<tr>
		<td align="left" width="350" valign="top"><strong>Path</strong><br>
		Define the PATH where the downloads will go <br>(make sure it ends with a / [slash]).
		It must be chmod'd to 777:
		</td>
		<td valign="top">
			<input name="path" type="Text" maxlength="254" value="<?php echo($cfg["path"]); ?>" size="55"><?php echo validatePath($cfg["path"]); ?>
		</td>
	</tr>

	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Authentication</strong></td></tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Select Auth-Type</strong><br>
		<u>Form-Auth</u> : Standard TF 2.1 Form-Based Auth<br>
		<u>Form-Auth + Cookie</u> : Form-Based Auth with "Remember Me"-Cookie<br>
		<u>Basic-Auth</u> : Basic-Auth with Realm "<?php echo(_AUTH_BASIC_REALM); ?>"<br>
		<u>Basic-Passthru</u> : gets credentials of already authenticated user and passes them to flux<br>
		</td>
		<td valign="top">
			<?php
				echo '<select name="auth_type">';
				echo '<option value="0"';
				if ($cfg["auth_type"] == "0")
					echo " selected";
				echo '>Form-Auth</option>';
				echo '<option value="1"';
				if ($cfg["auth_type"] == "1")
					echo " selected";
				echo '>Form-Auth + Cookie</option>';
				echo '<option value="2"';
				if ($cfg["auth_type"] == "2")
					echo " selected";
				echo '>Basic-Auth</option>';
				echo '<option value="3"';
				if ($cfg["auth_type"] == "3")
					echo " selected";
				echo '>Basic-Passthru</option>';
				echo '</select>';
			?>
		</td>
	</tr>

	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>BitTorrent</strong></td></tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>default BitTorrent-Client</strong><br>
		Choose the default BitTorrent-Client.
		</td>
		<td valign="top">
			<?php printBTClientSelect($cfg["btclient"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Select torrent-metainfo-client</strong><br>
		The client that is used to decode torrent-meta-data.
		</td>
		<td valign="top">
			<?php
				echo '<select name="metainfoclient">';
				echo '<option value="btshowmetainfo.py"';
				if ($cfg["metainfoclient"] == "btshowmetainfo.py")
					echo " selected";
				echo '>btshowmetainfo.py</option>';
				echo '<option value="transmissioncli"';
				if ($cfg["metainfoclient"] == "transmissioncli")
					echo " selected";
				echo '>transmissioncli</option>';
				echo '</select>';
			?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>tornado : btphptornado.py Path</strong><br>
		Specify the path to the btphptornado.py python script:
		</td>
		<td valign="top">
			<input name="btclient_tornado_bin" type="Text" maxlength="254" value="<?php echo($cfg["btclient_tornado_bin"]); ?>" size="55"><?php echo validateFile($cfg["btclient_tornado_bin"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>tornado : Extra Commandline Options</strong><br>
		DO NOT include --max_upload_rate, --minport, --maxport, --max_uploads , --max_initiate here.
		</td>
		<td valign="top">
			<input name="btclient_tornado_options" type="Text" maxlength="254" value="<?php echo($cfg["btclient_tornado_options"]); ?>" size="55">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>transmission : transmissioncli Path</strong><br>
		Specify the path to the transmission binary (transmissioncli):
		</td>
		<td valign="top">
			<input name="btclient_transmission_bin" type="Text" maxlength="254" value="<?php echo($cfg["btclient_transmission_bin"]); ?>" size="55"><?php echo validateFile($cfg["btclient_transmission_bin"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>transmission : Extra Commandline Options</strong><br>
		</td>
		<td valign="top">
			<input name="btclient_transmission_options" type="Text" maxlength="254" value="<?php echo($cfg["btclient_transmission_options"]); ?>" size="55">
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>btshowmetainfo.py Path</strong><br>
		Specify the path to the btshowmetainfo.py python script:
		</td>
		<td valign="top">
			<input name="btshowmetainfo" type="Text" maxlength="254" value="<?php echo($cfg["btshowmetainfo"]); ?>" size="55"><?php echo validateFile($cfg["btshowmetainfo"]); ?>
		</td>
	</tr>

	<tr><td colspan="2"><hr noshade></td></tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Max Upload Rate (B+T)</strong><br>
		Set the default value for the max upload rate per torrent:
		</td>
		<td valign="top">
			<input name="max_upload_rate" type="Text" maxlength="5" value="<?php echo($cfg["max_upload_rate"]); ?>" size="5"> KB/second
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Max Download Rate (B+T)</strong><br>
		Set the default value for the max download rate per torrent (0 for no limit):
		</td>
		<td valign="top">
			<input name="max_download_rate" type="Text" maxlength="5" value="<?php echo($cfg["max_download_rate"]); ?>" size="5"> KB/second
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Max Upload Connections (B)</strong><br>
		Set the default value for the max number of upload connections per torrent:
		</td>
		<td valign="top">
			<input name="max_uploads" type="Text" maxlength="5" value="<?php echo($cfg["max_uploads"]); ?>" size="5">
		</td>
	</tr>

	<tr>
	   <td align="left" width="350" valign="top"><strong>Max Cons (B)</strong><br>
	   Set default-value for maxcons.
	   </td>
	   <td valign="top">
		   <input name="maxcons" type="Text" maxlength="4" value="<?php echo ($cfg["maxcons"]); ?>" size="4">
	   </td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Port Range (B+T)</strong><br>
		Set the default values for the for port range (Min - Max):
		</td>
		<td valign="top">
			<input name="minport" type="Text" maxlength="5" value="<?php echo($cfg["minport"]); ?>" size="5"> -
			<input name="maxport" type="Text" maxlength="5" value="<?php echo($cfg["maxport"]); ?>" size="5">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Rerequest Interval (B)</strong><br>
		Set the default value for the rerequest interval to the tracker (default 1800 seconds):
		</td>
		<td valign="top">
			<input name="rerequest_interval" type="Text" maxlength="5" value="<?php echo($cfg["rerequest_interval"]); ?>" size="5">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Default Torrent Completion Activity (B)</strong><br>
		Select whether or not a torrent should keep seeding when download is complete
		(please seed your torrents):
		</td>
		<td valign="top">
			<select name="torrent_dies_when_done">
					<option value="True">Die When Done</option>
					<option value="False" <?php
					if ($cfg["torrent_dies_when_done"] == "False")
						echo "selected";
					?>>Keep Seeding</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Default Percentage When Seeding should Stop (B+T)</strong><br>
		Set the default share pecentage where torrents will shutoff
		when running torrents that do not die when done.
		Value '0' will seed forever.
		</td>
		<td valign="top">
			<input name="sharekill" type="Text" maxlength="3" value="<?php echo($cfg["sharekill"]); ?>" size="3">%
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable File Priority (B)</strong><br>
		When enabled, users will be allowed to select particular files from the torrent to download.:
		</td>
		<td valign="top">
			<select name="enable_file_priority">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_file_priority"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Skip HashCheck (B)</strong><br>
		Set the default	 for skip hash-checks on torrent-start:
		</td>
		<td valign="top">
			<select name="skiphashcheck">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["skiphashcheck"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>misc</strong></td></tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Enable umask</strong><br>
		Enable/Disable setting umask to 0000 when starting a torrent-client. :
		</td>
		<td valign="top">
			<select name="enable_umask">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_umask"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>nice</strong><br>
		nice clients and increment priority by given adjustment first:
		</td>
		<td valign="bottom">
			<select name="nice_adjust">
			<?php
				for ($i = 0; $i < 20 ; $i++) {
					if ($i != 0) {
						echo '<option value="'.$i.'"';
						if ($cfg["nice_adjust"] == $i)
							echo " selected";
						echo '>'.$i.'</option>';
					} else {
						echo '<option value="'.$i.'"';
						if ($cfg["nice_adjust"] == $i)
							echo " selected";
						echo '>Dont use nice</option>';
					}
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Use Advanced Start Dialog</strong><br>
		When enabled, users will be given the advanced start dialog popup when starting a torrent:
		</td>
		<td valign="top">
			<select name="advanced_start">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["advanced_start"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable multi-operations</strong><br>
		Enable/Disable torrent-multi-operations. (Start/Stop/Delete/...) :
		</td>
		<td valign="top">
			<select name="enable_multiops">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_multiops"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable bulk-operations</strong><br>
		Enable/Disable bulk-operations. (Stop/Resume/Start) :
		</td>
		<td valign="top">
			<select name="enable_bulkops">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_bulkops"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable dereferrer</strong><br>
		When enabled, links will be proxied over the dereferrer-page.
		</td>
		<td valign="top">
			<select name="enable_dereferrer">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_dereferrer"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Enable Torrent Search</strong><br>
		When enabled, users will be allowed to perform torrent searches from the home page:
		</td>
		<td valign="top">
			<select name="enable_search">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_search"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Default Torrent Search Engine</strong><br>
		Select the default search engine for torrent searches:
		</td>
		<td valign="top">
			<?php echo buildSearchEngineDDL($cfg["searchEngine"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable Make Torrent</strong><br>
		When enabled, users will be allowed to make torrent files from the directory view:
		</td>
		<td valign="top">
			<select name="enable_maketorrent">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_maketorrent"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>btmakemetafile.py Path</strong><br>
		Specify the path to the btmakemetafile.py python script (used for making torrents):
		</td>
		<td valign="top">
			<input name="btmakemetafile" type="Text" maxlength="254" value="<?php echo($cfg["btmakemetafile"]); ?>" size="55"><?php echo validateFile($cfg["btmakemetafile"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable Torrent File Download</strong><br>
		When enabled, users will be allowed to download the torrent meta file from the torrent list view:
		</td>
		<td valign="top">
			<select name="enable_torrent_download">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_torrent_download"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable File Download</strong><br>
		When enabled, users will be allowed to download from the directory view:
		</td>
		<td valign="top">
			<select name="enable_file_download">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_file_download"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable Text/NFO Viewer</strong><br>
		When enabled, users will be allowed to view Text/NFO files from the directory listing:
		</td>
		<td valign="top">
			<select name="enable_view_nfo">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_view_nfo"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Download Package Type</strong><br>
		When File Download is enabled, users will be allowed download from the directory view using
		a packaging system.	 Make sure your server supports the package type you select:
		</td>
		<td valign="top">
			<select name="package_type">
					<option value="tar" selected>tar</option>
					<option value="zip" <?php
					if ($cfg["package_type"] == "zip")
						echo "selected";
					?>>zip</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Days to keep Audit Actions in the Log</strong><br>
		Number of days that audit actions will be held in the database:
		</td>
		<td valign="top">
			<input name="days_to_keep" type="Text" maxlength="3" value="<?php echo($cfg["days_to_keep"]); ?>" size="3">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Minutes to Keep User Online Status</strong><br>
		Number of minutes before a user status changes to offline after leaving TorrentFlux:
		</td>
		<td valign="top">
			<input name="minutes_to_keep" type="Text" maxlength="2" value="<?php echo($cfg["minutes_to_keep"]); ?>" size="2">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Minutes to Cache RSS Feeds</strong><br>
		Number of minutes to cache the RSS XML feed on server (speeds up reload):
		</td>
		<td valign="top">
			<input name="rss_cache_min" type="Text" maxlength="3" value="<?php echo($cfg["rss_cache_min"]); ?>" size="3">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Default Theme</strong><br>
		Select the default theme that users will have (including login screen):
		</td>
		<td valign="top">
			<select name="default_theme">
<?php
$arThemes = GetThemes();
for($inx = 0; $inx < sizeof($arThemes); $inx++) {
	$selected = "";
	if ($cfg["default_theme"] == $arThemes[$inx])
		$selected = "selected";
	echo "<option value=\"".$arThemes[$inx]."\" ".$selected.">".$arThemes[$inx]."</option>";
}
?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Default Language</strong><br>
		Select the default language that users will have:
		</td>
		<td valign="top">
			<select name="default_language">
<?php
$arLanguage = GetLanguages();
for($inx = 0; $inx < sizeof($arLanguage); $inx++) {
	$selected = "";
	if ($cfg["default_language"] == $arLanguage[$inx])
		$selected = "selected";
	echo "<option value=\"".$arLanguage[$inx]."\" ".$selected.">".GetLanguageFromFile($arLanguage[$inx])."</option>";
}
?>
		</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Show SQL Debug Statements</strong><br>
		SQL Errors will always be displayed but when this feature is enabled the SQL Statement
		that caused the error will be displayed as well:
		</td>
		<td valign="top">
			<select name="debug_sql">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["debug_sql"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Hacks</strong></td></tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Enable MRTG-Integration</strong><br>
		Enable/Disable MRTG-Graphs-Integration. :
		</td>
		<td valign="top">
			<select name="enable_mrtg">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_mrtg"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Enable xfer</strong><br>
		Enable/Disable xfer-hack :
		</td>
		<td valign="top">
			<select name="enable_xfer">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_xfer"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable xfer realtime</strong><br>
		Enable/Disable xfer-realtime-stats updated on every index-reload :
		</td>
		<td valign="top">
			<select name="xfer_realtime">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["xfer_realtime"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable public xfer</strong><br>
		Enable/Disable public xfer of xfer-hack :
		</td>
		<td valign="top">
			<select name="enable_public_xfer">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_public_xfer"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>xfer - total</strong><br>
		Specify xfer_total. (default is "0"; 1TB is "1048576"):
		</td>
		<td valign="bottom">
			<input name="xfer_total" type="Text" maxlength="20" value="<?php echo($cfg["xfer_total"]); ?>" size="20">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>xfer - month</strong><br>
		Specify xfer_month. (default is "0"; 1GB is "1024"):
		</td>
		<td valign="bottom">
			<input name="xfer_month" type="Text" maxlength="20" value="<?php echo($cfg["xfer_month"]); ?>" size="20">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>xfer - week</strong><br>
		Specify xfer_week. (default is "0"):
		</td>
		<td valign="bottom">
			<input name="xfer_week" type="Text" maxlength="20" value="<?php echo($cfg["xfer_week"]); ?>" size="20">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>xfer - day</strong><br>
		Specify xfer_day. (default is "0"):
		</td>
		<td valign="bottom">
			<input name="xfer_day" type="Text" maxlength="20" value="<?php echo($cfg["xfer_day"]); ?>" size="20">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>xfer - week start</strong><br>
		Specify week_start. (default is "Monday"):
		</td>
		<td valign="bottom">
			<select name="week_start">
			<?php
				echo '<option value="Monday"';
				if ($cfg["week_start"] == 'Monday')
					echo " selected";
				echo '>Monday</option>';
				echo '<option value="Tuesday"';
				if ($cfg["week_start"] == 'Tuesday')
					echo " selected";
				echo '>Tuesday</option>';
				echo '<option value="Wednesday"';
				if ($cfg["week_start"] == 'Wednesday')
					echo " selected";
				echo '>Wednesday</option>';
				echo '<option value="Thursday"';
				if ($cfg["week_start"] == 'Thursday')
					echo " selected";
				echo '>Thursday</option>';
				echo '<option value="Friday"';
				if ($cfg["week_start"] == 'Friday')
					echo " selected";
				echo '>Friday</option>';
				echo '<option value="Saturday"';
				if ($cfg["week_start"] == 'Saturday')
					echo " selected";
				echo '>Saturday</option>';
				echo '<option value="Sunday"';
				if ($cfg["week_start"] == 'Sunday')
					echo " selected";
				echo '>Sunday</option>';
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>xfer - month start</strong><br>
		Specify month_start. (default is "1"):
		</td>
		<td valign="bottom">
			<select name="month_start">
			<?php
				for ($i = 1; $i <= 31 ; $i++) {
					echo '<option value="'.$i.'"';
					if ($cfg["month_start"] == $i)
						echo " selected";
					echo '>'.$i.'</option>';
				}
			?>
			</select>
		</td>
	</tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Enable multi-upload</strong><br>
		Enable/Disable multi-upload-hack :
		</td>
		<td valign="top">
			<select name="enable_multiupload">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_multiupload"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>multi-upload - rows</strong><br>
		Specify rows on multi-upload page. (default is "6"):
		</td>
		<td valign="bottom">
			<input name="hack_multiupload_rows" type="Text" maxlength="2" value="<?php echo($cfg["hack_multiupload_rows"]); ?>" size="2">
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable dir-stats</strong><br>
		Enable/Disable dir-stats-hack :
		</td>
		<td valign="top">
			<select name="enable_dirstats">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_dirstats"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable unrar</strong><br>
		Enable/Disable unrar-hack :
		</td>
		<td valign="top">
			<select name="enable_rar">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_rar"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable sfv-check</strong><br>
		Enable/Disable SFV Check-hack :
		</td>
		<td valign="top">
			<select name="enable_sfvcheck">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_sfvcheck"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable wget</strong><br>
		Enable/Disable wget-hack :
		</td>
		<td valign="top">
			<select name="enable_wget">
					<option value="1">true</option>
					<option value="0" <?php
					if (!$cfg["enable_wget"])
						echo "selected";
					?>>false</option>
			</select>
		</td>
	</tr>

	<!-- Specific save path -->
	<tr>
	   <td align="left" width="350" valign="top"><strong>Enable Specific save path</strong><br>
	   Show Directory Tree when you are going to start a torrent,
	   allowing you to select the path where you want
	   to download the file(s).
	   </td>
	   <td valign="top">
		   <select name="showdirtree">
				   <option value="1">true</option>
				   <option value="0" <?php
				   if (!$cfg["showdirtree"])
					   echo "selected";
				   ?>>false</option>
		   </select>
	   </td>
	</tr>
	<tr>
	   <td align="left" width="350" valign="top"><strong>Max Depth in Directory Tree</strong><br>
	   Set the max depth of subfolders in your user directory when
	   displaying directory tree. Set it to 0 if you want to
	   display all subfolders.
	   </td>
	   <td valign="top">
		   <input name="maxdepth" type="Text" maxlength="1" value="<?php echo ($cfg["maxdepth"]); ?>" size="1">
	   </td>
	</tr>

	<!-- "Only Admin can see other user torrents" -->
	<tr>
	   <td align="left" width="350" valign="top"><strong>Enable Only Admin can see other user torrents</strong><br>
	   Enable/Disable "Only Admin can see other user torrents"-hack :
	   </td>
	   <td valign="top">
		   <select name="enable_restrictivetview">
				   <option value="1">true</option>
				   <option value="0" <?php
				   if (!$cfg["enable_restrictivetview"])
					   echo "selected";
				   ?>>false</option>
		   </select>
	   </td>
	</tr>

	<!-- Rename Hack -->
	<tr>
		<td align="left" width="350" valign="top"><strong>Enable Rename Files</strong><br>
			Enable/Disable Rename Files or Folders:
		</td>
	  <td valign="top"><select name="enable_rename">
				<option value="1">true</option>
				<option value="0" <?php
				if (!$cfg["enable_rename"])
					echo "selected";
				?>>false</option>
			</select>
	  </td>
	</tr>

	<!-- Move Hack -->
	<tr>
	  <td align="left" width="350" valign="top"><strong>Move Settings</strong><br>
		Enable/Disable Moving Files into a specified dir:
	  </td>
	  <td valign="top"><select name="enable_move">
					  <option value="1">true</option>
					  <option value="0" <?php
					  if (!$cfg["enable_move"])
					   echo "selected";
					  ?>>false</option>
			</select>
	  </td>
	</tr>

	<!-- Move Hack settings -->
	<script src="move_extensionSettings.js" type="text/javascript"></script>
	<tr>
		<td align="left" width="350" valign="top"><strong>Move Settings</strong><br>
		  <u>Note :</u> You must specify absolute paths here. relative paths are not valid.<br>
		  <u>Note :</u> The created dirs will not be deleted after removing a entry from the List.
		</td>
		<td valign="top">
		 <?php echo getMoveSettingsForm(); ?>
		  </td>
	</tr>

	<!-- bins -->

	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Bins</strong></td></tr>

	<tr>
		<td align="left" width="350" valign="top"><strong>Path : grep</strong><br>
		Specify the path to the grep binary (/bin/grep):
		</td>
		<td valign="top">
			<input name="bin_grep" type="Text" maxlength="254" value="<?php echo($cfg["bin_grep"]); ?>" size="55"><?php echo validateFile($cfg["bin_grep"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : cat</strong><br>
		Specify the path to the cat binary (/bin/cat):
		</td>
		<td valign="top">
			<input name="bin_cat" type="Text" maxlength="254" value="<?php echo($cfg["bin_cat"]); ?>" size="55"><?php echo validateFile($cfg["bin_cat"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : php</strong><br>
		Specify the path to the php binary (/usr/bin/php):
		</td>
		<td valign="top">
			<input name="bin_php" type="Text" maxlength="254" value="<?php echo($cfg["bin_php"]); ?>" size="55"><?php echo validateFile($cfg["bin_php"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : python</strong><br>
		Specify the path to the python binary (/usr/bin/python):
		</td>
		<td valign="top">
			<input name="pythonCmd" type="Text" maxlength="254" value="<?php echo($cfg["pythonCmd"]); ?>" size="55"><?php echo validateFile($cfg["pythonCmd"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : awk</strong><br>
		Specify the path to the awk binary (/usr/bin/awk):
		</td>
		<td valign="top">
			<input name="bin_awk" type="Text" maxlength="254" value="<?php echo($cfg["bin_awk"]); ?>" size="55"><?php echo validateFile($cfg["bin_awk"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : du</strong><br>
		Specify the path to the du binary (/usr/bin/du):
		</td>
		<td valign="top">
			<input name="bin_du" type="Text" maxlength="254" value="<?php echo($cfg["bin_du"]); ?>" size="55"><?php echo validateFile($cfg["bin_du"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : wget</strong><br>
		Specify the path to the wget binary (/usr/bin/wget):
		</td>
		<td valign="top">
			<input name="bin_wget" type="Text" maxlength="254" value="<?php echo($cfg["bin_wget"]); ?>" size="55"><?php echo validateFile($cfg["bin_wget"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : unzip</strong><br>
		Specify the path to the unzip binary (/usr/bin/unzip):
		</td>
		<td valign="top">
			<input name="bin_unzip" type="Text" maxlength="254" value="<?php echo($cfg["bin_unzip"]); ?>" size="55"><?php echo validateFile($cfg["bin_unzip"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : cksfv</strong><br>
		Specify the path to the cksfv binary (/usr/bin/cksfv):
		</td>
		<td valign="top">
			<input name="bin_cksfv" type="Text" maxlength="254" value="<?php echo($cfg["bin_cksfv"]); ?>" size="55"><?php echo validateFile($cfg["bin_cksfv"]); ?>
		</td>
	</tr>

	<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>OS-specific</strong> <em>(<?php echo(php_uname('s')); echo " "; echo(php_uname('r')); ?>)</em></td></tr>
<?php
switch (_OS) {
	case 1: // linux
?>
	<tr>
		<td align="left" width="350" valign="top"><strong>loadavg Path</strong><br>
		Path to the loadavg file (/proc/loadavg):
		</td>
		<td valign="top">
			<input name="loadavg_path" type="Text" maxlength="254" value="<?php echo($cfg["loadavg_path"]); ?>" size="55"><?php echo validateFile($cfg["loadavg_path"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : netstat</strong><br>
		Specify the path to the netstat binary (/bin/netstat):
		</td>
		<td valign="top">
			<input name="bin_netstat" type="Text" maxlength="254" value="<?php echo($cfg["bin_netstat"]); ?>" size="55"><?php echo validateFile($cfg["bin_netstat"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : unrar</strong><br>
		Specify the path to the unrar binary (/usr/bin/unrar):
		</td>
		<td valign="top">
			<input name="bin_unrar" type="Text" maxlength="254" value="<?php echo($cfg["bin_unrar"]); ?>" size="55"><?php echo validateFile($cfg["bin_unrar"]); ?>
		</td>
	</tr>
<?php
	break;
	case 2: // bsd
?>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : fstat</strong><br>
		Specify the path to the fstat binary (/usr/bin/fstat):
		</td>
		<td valign="top">
			<input name="bin_fstat" type="Text" maxlength="254" value="<?php echo($cfg["bin_fstat"]); ?>" size="55"><?php echo validateFile($cfg["bin_fstat"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : sockstat</strong><br>
		Specify the path to the sockstat binary (/usr/bin/sockstat):
		</td>
		<td valign="top">
			<input name="bin_sockstat" type="Text" maxlength="254" value="<?php echo($cfg["bin_sockstat"]); ?>" size="55"><?php echo validateFile($cfg["bin_sockstat"]); ?>
		</td>
	</tr>
	<tr>
		<td align="left" width="350" valign="top"><strong>Path : rarbsd</strong><br>
		Specify the path to the rarbsd binary:
		</td>
		<td valign="top">
			<input name="bin_unrar" type="Text" maxlength="254" value="<?php echo($cfg["bin_unrar"]); ?>" size="55"><?php echo validateFile($cfg["bin_unrar"]); ?>
		</td>
	</tr>

<?php
break;
}
?>

	</table>
	<br>
	<input type="Submit" value="Update Settings">
	</form>
</div>
<br>
<?php
echo "</td></tr>";
echo "</table></div>";
echo DisplayFoot(true,true);
?>