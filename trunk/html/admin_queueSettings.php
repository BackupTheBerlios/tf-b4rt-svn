<?php
/* $Id: admin_queueSettings.php 102 2006-07-31 05:01:28Z msn_exploder $ */
require_once("AliasFile.php");
require_once("RunningTorrent.php");
require_once("QueueManager.php");
$queueManager = QueueManager::getQueueManagerInstance($cfg);
// QueueManager Running ?
$queueManagerRunning = false;
$shutdown = getRequestVar('s');
if ((isset($shutdown)) && ($shutdown == "1")) {
	$queueManagerRunning = false;
} else {
	if ($queueManager->isQueueManagerRunning()) {
		$queueManagerRunning = true;
	} else {
		if ($queueManager->managerName == "tfqmgr") {
			if ($queueManager->isQueueManagerReadyToStart()) {
				$queueManagerRunning = false;
			} else {
				$queueManagerRunning = true;
			}
		} else {
			$queueManagerRunning = false;
		}
	}
}
// head
echo getHead("Administration - Queue Settings");
// Admin Menu
echo getMenu();
// message section
$message = getRequestVar('m');
if ((isset($message)) && ($message != "")) {
	echo '<table cellpadding="5" cellspacing="0" border="0" width="100%">';
	echo '<tr><td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>';
	echo urldecode($message);
	echo '</strong></td></tr></table>';
}
// Queue Manager Section
echo "<div align=\"center\">";
echo "<a name=\"QManager\" id=\"QManager\"></a>";
echo "<table width=\"100%\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
echo "<tr><td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
echo "<font class=\"title\">";
//if(checkQManager() > 0)
//	   echo "&nbsp;&nbsp;<img src=\"images/green.gif\" align=\"absmiddle\" align=\"absmiddle\"> Queue Manager Running [PID=".getQManagerPID()." with ".strval(getRunningTorrentCount())." torrent(s)]";
if ($queueManagerRunning)
	echo "&nbsp;&nbsp;<img src=\"images/green.gif\" align=\"absmiddle\" align=\"absmiddle\"> Queue Manager Running (". $queueManager->managerName ."; pid: ". $queueManager->getQueueManagerPid() .")";
else
	echo "&nbsp;&nbsp;<img src=\"images/black.gif\" align=\"absmiddle\"> Queue Manager Off";
echo "</font>";
echo "</td></tr><tr><td align=\"center\">";
?>
<script language="JavaScript">
function validateSettings() {
	var rtnValue = true;
	var msg = "";
	if (isNumber(document.theForm.maxServerThreads.value) == false) {
		msg = msg + "* Max Server Threads must be a valid number.\n";
		document.theForm.maxServerThreads.focus();
	}
	if (isNumber(document.theForm.maxUserThreads.value) == false) {
		msg = msg + "* Max User Threads must be a valid number.\n";
		document.theForm.maxUserThreads.focus();
	}
	if (isNumber(document.theForm.sleepInterval.value) == false) {
		msg = msg + "* Sleep Interval must be a valid number.\n";
		document.theForm.sleepInterval.focus();
	}
	if (msg != "") {
		rtnValue = false;
		alert("Please check the following:\n\n" + msg);
	}
	return rtnValue;
}
function isNumber(sText) {
	var ValidChars = "0123456789.";
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

<?php
	//if ((! $queueManager->isQueueManagerRunning()) && ( !$queueManager->isQueueManagerReadyToStart())) {
	if ((isset($shutdown)) && ($shutdown == "1")) {
		echo '<tr><br>';
		echo '<td align="center" colspan="2">';
		echo 'QueueManager going down... Please Wait.';
		echo '<br><br></td>';
		echo '</tr>';
	} else {
		echo '<form name="controlForm" action="admin.php?op=controlQueueManager" method="post">';
		if ($queueManagerRunning) {
			echo '<input type="Hidden" name="a" value="stop">';
			echo '<tr><br>';
			echo '<td align="center" colspan="2">';
			echo '<input type="Submit" value="Stop QueueManager">';
			echo '<br><br></td>';
			echo '</tr>';
		} else {
			echo '<input type="Hidden" name="a" value="start">';
			echo '<tr>';
			echo '<td align="left" width="350" valign="top"><strong>Choose Queue Manager</strong><br>';
			echo '<u>Note</u> : tfQManager only supports tornado-clients.';
			echo '</td>';
			echo '<td>';
			echo '<select name="queuemanager">';
			echo '<option value="tfqmgr"';
			if ($cfg["queuemanager"] == "tfqmgr")
				echo " selected";
			echo '>tfqmgr</option>';
			echo '<option value="Qmgr"';
			if ($cfg["queuemanager"] == "Qmgr")
				echo " selected";
			echo '>Qmgr</option>';
			echo '<option value="tfQManager"';
			if ($cfg["queuemanager"] == "tfQManager")
				echo " selected";
			echo '>tfQManager</option>';
			echo '</select>';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td align="center" colspan="2">';
			echo '<input type="Submit" value="Start QueueManager">';
			echo '<br><br></td>';
			echo '</tr>';
		}
		echo '</form>';
	}
?>

		<form name="theForm" action="admin.php?op=updateQueueSettings" method="post" onsubmit="return validateSettings()">
		<tr>
		 <td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>">
		  <strong>tfqmgr</strong>&nbsp;
		  <?php
			echo '(';
			echo getSuperAdminLink('?q=1','log');
			echo ' | ';
			echo getSuperAdminLink('?q=2','ps');
			if ((isset($shutdown)) && ($shutdown == "1")) {
			} else {
				if ($queueManagerRunning && ($queueManager->managerName == "tfqmgr")) {
					echo ' | ';
					echo getSuperAdminLink('?q=3','status');
				}
			}
			echo ' )';
		  ?>
		 </td>
		</tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>perl Path</strong><br>
			Specify the path to perl (/usr/bin/perl):
			</td>
			<td valign="top">
				<input name="perlCmd" type="Text" maxlength="254" value="<?php echo($cfg["perlCmd"]); ?>" size="55"><?php echo validateFile($cfg["perlCmd"]) ?>
			</td>
		</tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>tfqmgr Path</strong><br>
			Specify the path of tfqmgr (/var/www/tfqmgr):
			</td>
			<td valign="top">
				<input name="tfqmgr_path" type="Text" maxlength="254" value="<?php echo($cfg["tfqmgr_path"]); ?>" size="55"><?php echo validateFile($cfg["tfqmgr_path"]."/tfqmgr.pl") ?>
			</td>
		</tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>fluxcli Path</strong><br>
			Specify the path where fluxcli.php is. (/var/www):
			</td>
			<td valign="top">
				<input name="tfqmgr_path_fluxcli" type="Text" maxlength="254" value="<?php echo($cfg["tfqmgr_path_fluxcli"]); ?>" size="55"><?php echo validateFile($cfg["tfqmgr_path_fluxcli"]."/fluxcli.php") ?>
			</td>
		</tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>Limit Torrent Global</strong><br>
			Specify the maximum number of torrents the server will allow to run at one time (admins may override this):
			</td>
			<td valign="top">
				<input name="tfqmgr_limit_global" type="Text" maxlength="3" value="<?php echo($cfg["tfqmgr_limit_global"]); ?>" size="3">
			</td>
		</tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>Limit Torrent Per User</strong><br>
			Specify the maximum number of torrents a single user may run at one time:
			</td>
			<td valign="top">
				<input name="tfqmgr_limit_user" type="Text" maxlength="3" value="<?php echo($cfg["tfqmgr_limit_user"]); ?>" size="3">
			</td>
		</tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>Loglevel</strong><br>
			Specify the level of logging (default is 0):
			</td>
			<td valign="top">
				<input name="tfqmgr_loglevel" type="Text" maxlength="2" value="<?php echo($cfg["tfqmgr_loglevel"]); ?>" size="5">
			</td>
		</tr>

		<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Qmgr</strong></td></tr>
		<tr>
			 <td align="left" width="350" valign="top"><strong>Path to Qmgr scripts</strong><br>
			   Specify the path to the Qmgr.pl and Qmgrd.pl scripts:
			   </td>
			   <td valign="top">
				   <input name="Qmgr_path" type="Text" maxlength="254" value="<?php echo($cfg["Qmgr_path"]); ?>" size="55"><?php echo validateFile($cfg["Qmgr_path"]."/Qmgrd.pl") ?>
			   </td>
		</tr>
		<tr>
			   <td align="left" width="350" valign="top"><strong>Max User Torrents</strong><br>
			   Total number of torrents to allow a single user at once:
			   </td>
			   <td valign="top">
				   <input name="Qmgr_maxUserTorrents" type="Text" maxlength="3" value="<?php echo($cfg["Qmgr_maxUserTorrents"]); ?>" size="3">
			   </td>
		</tr>
		<tr>
			   <td align="left" width="350" valign="top"><strong>Max Total Torrents</strong><br>
			   Total number of torrents the server will run at once:
			   </td>
			   <td valign="top">
				   <input name="Qmgr_maxTotalTorrents" type="Text" maxlength="3" value="<?php echo($cfg["Qmgr_maxTotalTorrents"]); ?>" size="3">
			   </td>
		</tr>
		<tr>
			   <td align="left" width="350" valign="top"><strong>Perl's Path</strong><br>
			   Specify the path to perl:
			   </td>
			   <td valign="top">
				   <input name="Qmgr_perl" type="Text" maxlength="254" value="<?php echo($cfg["Qmgr_perl"]); ?>" size="55"><?php echo validateFile($cfg["Qmgr_perl"]); ?>
			   </td>
		</tr>
		<tr>
			   <td align="left" width="350" valign="top"><strong>Fluxcli.php path</strong><br>
			   Specify the path to the fluxcli executable:
			   </td>
			   <td valign="top">
				   <input name="Qmgr_fluxcli" type="Text" maxlength="254" value="<?php echo($cfg["Qmgr_fluxcli"]); ?>" size="55"><?php echo validateFile($cfg["Qmgr_fluxcli"]."/fluxcli.php") ?>
			   </td>
		</tr>
		<tr>
			   <td align="left" width="350" valign="top"><strong>Qmgrd host</strong><br>
			   The host running the Qmgrd.pl script, probably localhost:
			   </td>
			   <td valign="top">
				   <input name="Qmgr_host" type="Text" maxlength="254" value="<?php echo($cfg["Qmgr_host"]); ?>" size="15">
			   </td>
		</tr>
		<tr>
			   <td align="left" width="250" valign="top"><strong>Qmgrd port</strong><br>
			   the port number to run the Qmgrd.pl script on:
			   </td>
			   <td valign="top">
				   <input name="Qmgr_port" type="Text" maxlength="5" value="<?php echo($cfg["Qmgr_port"]); ?>" size="5">
			   </td>
		</tr>
		<tr>
			   <td align="left" width="250" valign="top"><strong><Qmgrd Loglevel</strong><br>
			   Level of logging (default to 0):
			   </td>
			   <td valign="top">
				   <input name="Qmgr_loglevel" type="Text" maxlength="2" value="<?php echo($cfg["Qmgr_loglevel"]); ?>" size="5">
			   </td>
		</tr>
		<tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>tfQManager</strong></td></tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>tfQManager Path</strong><br>
			Specify the path to the tfQManager.py python script:
			</td>
			<td valign="top">
				<input name="tfQManager" type="Text" maxlength="254" value="<?php echo($cfg["tfQManager"]); ?>" size="55"><?php echo validateFile($cfg["tfQManager"]) ?>
			</td>
		</tr>

		<tr>
			<td align="left" width="350" valign="top"><strong>Max Server Threads</strong><br>
			Specify the maximum number of torrents the server will allow to run at
			one time (admins may override this):
			</td>
			<td valign="top">
				<input name="maxServerThreads" type="Text" maxlength="3" value="<?php	 echo($cfg["maxServerThreads"]); ?>" size="3">
			</td>
		</tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>Max User Threads</strong><br>
			Specify the maximum number of torrents a single user may run at
			one time:
			</td>
			<td valign="top">
				<input name="maxUserThreads" type="Text" maxlength="3" value="<?php	   echo($cfg["maxUserThreads"]); ?>" size="3">
			</td>
		</tr>
		<tr>
			<td align="left" width="350" valign="top"><strong>Polling Interval</strong><br>
			Number of seconds the Queue Manager will sleep before checking for new torrents to run:
			</td>
			<td valign="top">
				<input name="sleepInterval" type="Text" maxlength="3" value="<?php	  echo($cfg["sleepInterval"]); ?>" size="3">
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
<br>
<?php
echo "</td></tr>";
echo "</table></div>";
$displayQueue = True;
$displayRunningTorrents = True;
// Its a timming thing.
if ($displayRunningTorrents) {
	// get Running Torrents.
	$runningTorrents = getRunningTorrents();
}
if ($displayQueue) {
	echo "\n";
	echo "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
	echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
	echo "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr>";
	echo "<td><img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\"> Queued Items </font></td>";
	echo "</tr></table>";
	echo "</td></tr>";
	echo "<tr>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._USER."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._FILE."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._TIMESTAMP."</div></td>";
	echo "</tr>";
	echo "\n";
	echo $queueManager->formattedQueueList();
	echo "</table>";
}
if ($displayRunningTorrents) {
	$output = "";
	echo "\n";
	echo "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
	echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
	echo "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr>";
	echo "<td><img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\"> Running Items </font></td>";
	echo "</tr></table>";
	echo "</td></tr>";
	echo "<tr>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._USER."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._FILE."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"1%\"><div align=center class=\"title\">".str_replace(" ","<br>",_FORCESTOP)."</div></td>";
	echo "</tr>";
	echo "\n";
	// messy...
	// get running tornado torrents and List them out.
	$runningTorrents = getRunningTorrents("tornado");
	foreach ($runningTorrents as $key => $value) {
		$rt = RunningTorrent::getRunningTorrentInstance($value,$cfg,"tornado");
		$output .= $rt->BuildAdminOutput();
	}
	// get running transmission torrents and List them out.
	$runningTorrents = getRunningTorrents("transmission");
	foreach ($runningTorrents as $key => $value) {
		$rt = RunningTorrent::getRunningTorrentInstance($value,$cfg,"transmission");
		$output .= $rt->BuildAdminOutput();
	}
	if( strlen($output) == 0 )
		$output = "<tr><td colspan=3><div class=\"tiny\" align=center>No Running Torrents</div></td></tr>";
	echo $output;
	echo "</table>";
}
echo getFoot(true,true);
?>