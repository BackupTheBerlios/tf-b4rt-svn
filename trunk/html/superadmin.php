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

// main.internal
require_once("inc/main.internal.php");

// all functions
require_once('inc/functions/functions.all.php');

// superadmin functions
require_once('inc/functions/functions.superadmin.php');

// defines
define('_DIR_BACKUP','.backup');
define('_URL_HOME','http://tf-b4rt.berlios.de/');
define('_URL_RELEASE','http://tf-b4rt.berlios.de/current');
define('_SUPERADMIN_URLBASE','http://tf-b4rt.berlios.de/');
define('_SUPERADMIN_PROXY','tf-b4rt.php');
define('_FILE_CHECKSUMS_PRE','checksums-');
define('_FILE_CHECKSUMS_SUF','.txt');
define('_FILE_THIS', 'superadmin.php');
define('_UPDATE_ARCHIVE','update.tar.bz2');

// global fields
$error = "";
$statusImage = "black.gif";
$statusMessage = "";
$htmlTitle = "";
$htmlTop = "";
$htmlMain = "";

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// authenticate first
superadminAuthentication();

// fopen
ini_set("allow_url_fopen", "1");

// version
if (is_file('version.php'))
	require_once('version.php');
else
	@error("version.php is missing");

// -----------------------------------------------------------------------------
// backup
// -----------------------------------------------------------------------------
if (isset($_REQUEST["b"])) {
	$backupStep = trim($_REQUEST["b"]);
	if ($backupStep != "") {
		switch($backupStep) {

			case "0": // choose backup-type
				buildPage("b");
				$htmlTitle = "Backup - Create";
				$htmlMain .= '<br>';
				$htmlMain .= '<form name="backupServer" action="' . _FILE_THIS . '" method="post">';
				$htmlMain .= '<select name="c">';
				$htmlMain .= '<option value="0">none</option>';
				$htmlMain .= '<option value="1" selected>gzip</option>';
				$htmlMain .= '<option value="2">bzip2</option>';
				$htmlMain .= '</select>&nbsp;&nbsp;';
				$htmlMain .= '<input type="Hidden" name="b" value="1">';
				$htmlMain .= '<input type="submit" value="Backup on Server">';
				$htmlMain .= '</form><p>';
				$htmlMain .= '<form name="backupClient" action="' . _FILE_THIS . '" method="post">';
				$htmlMain .= '<select name="c">';
				$htmlMain .= '<option value="0">none</option>';
				$htmlMain .= '<option value="1" selected>gzip</option>';
				$htmlMain .= '<option value="2">bzip2</option>';
				$htmlMain .= '</select>&nbsp;&nbsp;';
				$htmlMain .= '<input type="Hidden" name="b" value="2">';
				$htmlMain .= '<input type="submit" value="Backup to Client">';
				$htmlMain .= '</form><p>';
				$htmlMain .= 'In case you choose "Backup on Server" the archive will be located in : <br>';
				$htmlMain .= '<em>'.$cfg["path"]. _DIR_BACKUP . '/</em>';
				$htmlMain .= '<br><br>';
				$htmlMain .= 'Be patient until "its done" and dont click stuff while backup is created.<br>';
				$htmlMain .= 'This script will tell you if things go wrong so no need to stress it.<br>';
				printPage();
				exit();

			case "1": // server-backup
				buildPage("b");
				$htmlTitle = "Backup - Create - Server";
				printPageStart(1);
				echo $htmlMain;
				$backupArchive = backupCreate(true,$_REQUEST["c"]);
				if ($backupArchive == "") {
					sendLine('<br>');
					sendLine('<font color="red"><strong>Backup - Error</strong></font><br><br>');
					sendLine($error);
				} else {
					sendLine('<br>');
					sendLine('<strong>Backup Created</strong>');
					sendLine('<br><br>Archive of backup is <em>'.$backupArchive.'</em>');
					sendLine('<br><br>');
					sendLine(backupListDisplay());
				}
				printPageEnd(1);
				exit();

			case "2": // client-backup
				$backupArchive = backupCreate(false,$_REQUEST["c"]);
				if ($backupArchive == "") {
					buildPage("-b");
					$htmlTitle = "Backup - Create - Client";
					$htmlMain .= '<br><br>';
					$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
					$htmlMain .= $error;
					printPage();
				} else {
					backupSend($backupArchive,true);
				}
				exit();

			case "3": // backup-list
				$htmlTitle = "Backup - Backups on Server";
				buildPage("b");
				$htmlMain .= '<br>';
				$htmlMain .= backupListDisplay();
				printPage();
				exit();

			case "4": // download backup
				$backupArchive = trim($_REQUEST["f"]);
				if (backupParamCheck($backupArchive)) {
					backupSend($backupArchive,false);
				} else {
					buildPage("-b");
					$htmlTitle = "Backup - Download";
					$htmlMain .= '<br><br>';
					$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
					$htmlMain .= $backupArchive.' is not a valid Backup-ID';
					printPage();
				}
				exit();

			case "5": // delete backup
				$backupArchive = trim($_REQUEST["f"]);
				if (backupParamCheck($backupArchive)) {
					backupDelete($backupArchive);
					buildPage("b");
					$htmlTitle = "Backup - Delete";
					$htmlMain .= '<br>';
					$htmlMain .= '<em>'.$backupArchive.'</em> deleted.';
					$htmlMain .= '<br><br>';
					$htmlMain .= backupListDisplay();
				} else {
					buildPage("-b");
					$htmlTitle = "Backup - Delete";
					$htmlMain .= '<br><br>';
					$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
					$htmlMain .= $backupArchive.' is not a valid Backup-ID';
				}
				printPage();
				exit();

		}
		exit();
	}
}

// -----------------------------------------------------------------------------
// update
// -----------------------------------------------------------------------------
if (isset($_REQUEST["u"])) {
	$updateStep = trim($_REQUEST["u"]);
	if ($updateStep != "") {
		switch($updateStep) {

			case "0":
				// get updateIndex to check if update from this version possible
				$updateIndexData = trim(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=0&v=" . _VERSION));
				if ((isset($updateIndexData)) && ($updateIndexData != "")) {
					$updateIndexVars = explode("\n",$updateIndexData);
					$updatePossible = trim($updateIndexVars[0]);
					if ($updatePossible != "1") {
						buildPage("-u");
						printPage();
						exit();
					} else {
						$htmlTop = "<strong>Update - Check</strong>";
						$htmlMain = "<br>Update from your Version possible.";
						$htmlMain .= '<br><br>';
						$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
						$htmlMain .= '<input type="Hidden" name="u" value="1">';
						$htmlMain .= '<input type="submit" value="Next Step - Database-Update">';
						$htmlMain .= '</form>';
						$htmlMain .= '<br>';
						$statusImage = "yellow.gif";
						$htmlTitle = "Update";
						printPage();
						exit();
					}
				} else {
					buildPage("-u");
					printPage();
					exit();
				}
				break;

			case "1":
				// get db-settings
				$updateDBData = trim(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=1&v=" . _VERSION));
				if ((isset($updateDBData)) && ($updateDBData != "")) {
					$updateDBVars = explode("\n",$updateDBData);
					$updateNeeded = trim($updateDBVars[0]);
					if ($updateNeeded == "0") {
						$htmlTop = "<strong>Update - Database</strong>";
						$htmlMain = "<br>Database-Update in this Update not needed.";
						$htmlMain .= '<br><br>';
						$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
						$htmlMain .= '<input type="Hidden" name="u" value="3">';
						$htmlMain .= '<input type="submit" value="Next Step - File-Update">';
						$htmlMain .= '</form>';
						$htmlMain .= '<br>';
						$statusImage = "yellow.gif";
						$htmlTitle = "Update";
						printPage();
						exit();
					} else if ($updateNeeded == "1") {
						$htmlTop = "<strong>Update - Database</strong>";
						$htmlMain = "<br>Database-Update in this Update is needed.";
						$htmlMain .= '<br><br>';
						$htmlMain .= 'Type : <em>'.$cfg["db_type"].'</em>';
						$htmlMain .= '<br><br>';
						$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
						$htmlMain .= '<input type="Hidden" name="u" value="2">';
						$htmlMain .= '<input type="submit" value="Next Step - Perform Database-Update">';
						$htmlMain .= '</form>';
						$htmlMain .= '<br>';
						$statusImage = "yellow.gif";
						$htmlTitle = "Update";
						printPage();
						exit();
					} else {
						updateError($updateDBData);
						exit();
					}
				} else {
					updateError();
				}
				break;

			case "2":
				// get sql-data
				$updateSQLData = @trim(gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=2&v=" . _VERSION . "&d=".$cfg["db_type"])));
				if ((isset($updateSQLData)) && ($updateSQLData != "")) {
					sendLine('<strong>Update - Database</strong><br><br><em>Updating Database... Please Wait...</em><ul>');
					$updateSQLStates = explode("\n",$updateSQLData);
					// get ado-connection
					$dbCon = getAdoConnection();
					if (!$dbCon) {
						echo '</em></li></ul><font color="red"><strong>Error updating Database.</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>';
						echo $dbCon->ErrorMsg();
						exit();
					} else {
						foreach ($updateSQLStates as $sqlState) {
							$sqlState = trim($sqlState);
							if ((isset($sqlState)) && ($sqlState != "") && ((substr($sqlState, 0, 2)) != "--")) {
								sendLine('<li>'.$sqlState);
								$dbCon->Execute($sqlState);
								if($dbCon->ErrorNo() == 0) {
									sendLine(' <font color="green">Ok</font></li>');
								} else { // damn there was an error
									// close ado-connection
									$dbCon->Close();
									// talk and out
									echo '</em></li></ul><font color="red"><strong>Error updating Database.</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>';
									exit();
								}
							}
						}
						// close ado-connection
						$dbCon->Close();
						// talk and continue
						sendLine('</ul><p><font color="green">Database-Update done.</font><br><br>');
						sendLine('<form name="update" action="' . _FILE_THIS . '" method="post"><input type="Hidden" name="u" value="3"><input type="submit" value="Next Step - File-Update"></form><br>');
						exit();
					}
				} else {
					updateError("\n"."cant get update-sql."."\n".$updateSQLData);
				}
				break;

			case "3":
				// get file-list
				$updateFileList = @trim(gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=3&v=" . _VERSION)));
				if ((isset($updateFileList)) && ($updateFileList != "")) {
					sendLine('<strong>Update - Files</strong>');
					sendLine('<br><br>');
					sendLine('Files that require an update in this Version :');
					sendLine('<pre>');
					sendLine($updateFileList);
					sendLine('</pre>');
					sendLine('<form name="update" action="' . _FILE_THIS . '" method="post">');
					sendLine('<input type="Hidden" name="u" value="4">');
					sendLine('<input type="submit" value="Next Step - Perform File-Update">');
					sendLine('</form>');
					sendLine('<strong>Ensure script can write to docroot <em>'.$cfg['docroot'].'</em> now !</strong>');
					exit();
				} else {
					updateError("\n"."cant get file-list."."\n".$updateFileList);
				}
				break;

			case "4":
				sendLine('<strong>Update - Files</strong><br><br><em>Updating Files... Please Wait...</em><br><ul>');
				sendLine('<li>Getting Update-Archive :<br>');
				ini_set("allow_url_fopen", "1");
				ini_set("user_agent", "torrentflux-b4rt/". _VERSION);
				// get md5
				$md5hash = getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=4&v=" . _VERSION);
				if ((!isset($md5hash)) || (strlen($md5hash) != 32)) {
					sendLine('</li></ul><br><br><font color="red"><strong>Error getting Update-Archive.</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>');
					exit();
				}
				// download archive
				$fileHandle = @fopen($cfg['docroot']._UPDATE_ARCHIVE, "w");
				$urlHandle = @fopen(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=5&v=" . _VERSION, 'r');
				if (($fileHandle) && ($urlHandle)) {
					$results = array();
					$i = 0;
					while (!@feof($urlHandle)) {
						$data = @fgets($urlHandle, 8192);
						$results[$i] = @fwrite($fileHandle, $data);
						sendLine('.');
						$i++;
					}
					@fclose($fileHandle);
					@fclose($urlHandle);
					$done = true;
					foreach ($results as $result) {
						if ($result === false)
							$done = false;
					}
					if ($done) {
						sendLine('<font color="green">done</font></li>');
					} else {
						sendLine('<br></li></ul><br><br><strong><font color="red">Error writing archive <em>'.$cfg['docroot']._UPDATE_ARCHIVE.'</em>.</font></strong><br><br>Please restore backup and try again (or do manual update).<br><br>');
						exit();
					}
				} else {
					sendLine('</li></ul><br><br><strong><font color="red">Error updating files.</font></strong><br><br>Please restore backup and try again (or do manual update).<br><br>');
					exit();
				}
				// validate archive
				sendLine('<li>Validating Update-Archive : ');
				if ((file_exists($cfg['docroot']._UPDATE_ARCHIVE))
					&& ($md5hash == @md5_file($cfg['docroot']._UPDATE_ARCHIVE))) {
					sendLine('<font color="green">Ok</font> (<em>'.$md5hash.'</em>)<br></li>');
				} else {
					sendLine('<font color="red">failed</font></ul><br><br>Please restore backup and try again (or do manual update).</strong><br><br>');
					exit();
				}
				// extract archive
				sendLine('<li>Extracting Update-Archive : <br>');
				sendLine('<em>');
				$cmd  = 'cd '.escapeshellarg($cfg['docroot']).' && tar jxvf '._UPDATE_ARCHIVE;
				$cmd .= ' 2>&1';
				$handle = @popen($cmd, 'r');
				while (!@feof($handle)) {
					$read = @fread($handle, 64);
					sendLine(nl2br($read));
				}
				@pclose($handle);
				sendLine('</em>');
				sendLine('<font color="green">done</font></li>');
				// delete archive
				sendLine('<li>Deleting Update-Archive : ');
				if (@unlink($cfg['docroot']._UPDATE_ARCHIVE))
					sendLine('<font color="green">done</font></li>');
				else
					sendLine('<font color="red">failed</font><br>remove archive '.$cfg['docroot']._UPDATE_ARCHIVE.' manual now.</li>');

				// version-file
				sendLine('<li>Updating Version-Information : ');
				$versionAvailable = trim(getDataFromUrl(_SUPERADMIN_URLBASE._SUPERADMIN_PROXY));
				if ((isset($versionAvailable)) && ($versionAvailable != "")) {
					if ($handle = @fopen("version.php", "w")) {
						if (@fwrite($handle, '<?php define("_VERSION", "'.$versionAvailable.'"); ?>')) {
							@fclose($handle);
							sendLine(' <font color="green">done</font></li>');
						} else {
							@fclose($handle);
							sendLine('</li></ul><br><br><font color="red"><strong>Error writing version-file</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>');
							exit();
						}
					} else {
						sendLine('<br><br>');
						sendLine('</li></ul><font color="red"><strong>Error writing version-file</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>');
						exit();
					}
				} else {
					sendLine('</li></ul><br><br><font color="red"><strong>Error getting version-file</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>');
					exit();
				}
				sendLine('</ul>');
				// done
				sendLine('<p><em>Done Updating Files.</em></p>');
				sendLine('<hr><br><strong>Update to '.$versionAvailable.' completed.</strong><br><br>');
				sendLine('<form name="update" action="#" method="get"><input type="submit" onClick="window.close()" value="Close"></form>');
				sendLine('<br>');
				// flush cache
				cacheFlush();
				// exit
				exit();

		}
		exit();
	}
}

// -----------------------------------------------------------------------------
// fluxd
// -----------------------------------------------------------------------------
if (isset($_REQUEST["f"])) {
	$action = trim($_REQUEST["f"]);
	if ($action != "") {
		buildPage("f");
		switch($action) {

			case "0": // fluxd-main
				$htmlTitle = "fluxd";
				break;

			case "1": // fluxd-log
				$htmlTitle = "fluxd - log";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= file_get_contents($cfg["path"].'.fluxd/fluxd.log');
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "2": // fluxd-error-log
				$htmlTitle = "fluxd - error-log";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= file_get_contents($cfg["path"].'.fluxd/fluxd-error.log');
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "3": // fluxd-ps
				$htmlTitle = "fluxd - ps";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= shell_exec("ps auxww | ".$cfg['bin_grep']." fluxd.pl | ".$cfg['bin_grep']." -v grep");
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "4": // fluxd-status
				$htmlTitle = "fluxd - status";
				if (Fluxd::isRunning()) {
					$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
					$htmlMain .= '<pre>';
					$htmlMain .= Fluxd::status();
					$htmlMain .= '</pre>';
					$htmlMain .= '</div>';
				} else {
					$htmlMain .= '<br><strong>fluxd not running</strong>';
				}
				break;

			case "5": // fluxd-check
				$htmlTitle = "fluxd - check";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= shell_exec($cfg["perlCmd"]." -I ".$cfg["docroot"]."bin/fluxd ".$cfg["docroot"]."bin/fluxd/fluxd.pl check");
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "6": // fluxd-db-debug
				$htmlTitle = "fluxd - db-debug";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= shell_exec($cfg["perlCmd"]." -I ".$cfg["docroot"]."bin/fluxd ".$cfg["docroot"]."bin/fluxd/fluxd.pl debug db ".$cfg["docroot"]." ".$cfg["path"]." ".$cfg["bin_php"]);
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "9": // fluxd-version
				$htmlTitle = "fluxd - version";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= shell_exec($cfg["perlCmd"]." -I ".$cfg["docroot"]."bin/fluxd ".$cfg["docroot"]."bin/fluxd/fluxd.pl version");
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;
		}
		printPage();
		exit();
	}
}

// -----------------------------------------------------------------------------
// Processes
// -----------------------------------------------------------------------------
if (isset($_REQUEST["p"])) {
	$action = trim($_REQUEST["p"]);
	if ($action != "") {
		buildPage("p");
		switch($action) {

			case "0": // Processes-main
				$htmlTitle = "Processes";
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?p=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="All" border="0"> All</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?p=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Transfers" border="0"> Transfers</a>';
				$htmlMain .= '<br><br>';
				break;

			case "1": // Processes - All
				$htmlTitle = "Processes - All";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<br>';
				$htmlMain .= '<p><strong>fluxd</strong>';
				$htmlMain .= '<pre>';
				$htmlMain .= shell_exec("ps auxww | ".$cfg['bin_grep']." fluxd.pl | ".$cfg['bin_grep']." -v grep");
				$htmlMain .= '</pre>';
				$clients = array('tornado', 'transmission', 'mainline', 'wget');
				foreach ($clients as $client) {
					$clientHandler = ClientHandler::getInstance($client);
					$htmlMain .= '<p><strong>'.$client.'</strong>';
					$htmlMain .= '<br>';
					$htmlMain .= '<pre>';
					$htmlMain .= shell_exec("ps auxww | ".$cfg['bin_grep']." ".$clientHandler->binClient." | ".$cfg['bin_grep']." -v grep");
					$htmlMain .= '</pre>';
					$htmlMain .= '<br>';
					$htmlMain .= '<pre>';
					$htmlMain .= $clientHandler->runningProcessInfo();
					$htmlMain .= '</pre>';
				}
				$htmlMain .= '</div>';
				break;

			case "2": // Processes - Transfers
				$htmlTitle = "Processes - Transfers";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<br>
					<table width="700" border=1 bordercolor="'.$cfg["table_admin_border"].'" cellpadding="2" cellspacing="0" bgcolor="'.$cfg["table_data_bg"].'">
				    <tr><td colspan=6 bgcolor="'.$cfg["table_header_bg"].'" background="themes/'.$cfg["theme"].'/images/bar.gif">
				    	<table width="100%" cellpadding=0 cellspacing=0 border=0><tr><td><font class="title"> Running Items </font></td></tr></table>
				    </td></tr>
				    <tr>
				        <td bgcolor="'.$cfg["table_header_bg"].'" width="15%" nowrap><div align=center class="title">'.$cfg["_USER"].'</div></td>
				        <td bgcolor="'.$cfg["table_header_bg"].'" nowrap><div align=center class="title">'.$cfg["_FILE"].'</div></td>
				        <td bgcolor="'.$cfg["table_header_bg"].'" width="1%" nowrap><div align=center class="title">'.$cfg["_FORCESTOP"].'</div></td>
				    </tr>
				';
				$running = getRunningClientProcesses();
				foreach ($running as $rng) {
					$rt = RunningTransfer::getInstance($rng['pinfo'], $rng['client']);
				    $htmlMain .= '<tr bgcolor="'.$cfg["table_header_bg"].'">';
				    $htmlMain .= '<td nowrap><div class="tiny">';
				    $htmlMain .= $rt->transferowner;
				    $htmlMain .= '</div></td>';
				    $htmlMain .= '<td nowrap><div align=center><div class="tiny" align="left">';
				    $htmlMain .= str_replace(array(".stat"),"",$rt->statFile);
				    $htmlMain .= '</div></td>';
				    $htmlMain .= '<td nowrap>';
				    $htmlMain .= '<a href="dispatcher.php?action=forceStop';
				    $htmlMain .= "&transfer=".urlencode($rt->transferFile);
				    $htmlMain .= "&pid=".$rt->processId;
				    $htmlMain .= '"><img src="themes/'.$cfg["theme"].'/images/kill.gif" width="16" height="16" title="'.$cfg['_FORCESTOP'].'" border="0"></a></td>';
				    $htmlMain .= '</tr>';
				}
				$htmlMain .= '</table>';
				$htmlMain .= '</div>';
				break;
		}
		printPage();
		exit();
	}
}

// -----------------------------------------------------------------------------
// maintenance
// -----------------------------------------------------------------------------
if (isset($_REQUEST["m"])) {
	$mAction = trim($_REQUEST["m"]);
	if ($mAction != "") {
		buildPage("m");
		switch($mAction) {

			case "0": // Maintenance-main
				$htmlTitle = "Maintenance";
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Main" border="0"> Main</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Kill" border="0"> Kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=3"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Clean" border="0"> Clean</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=4"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Repair" border="0"> Repair</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=5"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Reset" border="0"> Reset</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=6"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Lock" border="0"> Lock</a>';
				$htmlMain .= '<br><br>';
				break;

			case "1": // Maintenance : Main
				$htmlTitle = "Maintenance - Main";
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>Standard</strong><br>';
				$htmlMain .= 'Standard Maintenance-Run. (same as on index-page and automatic called on every login).<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=11"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Standard Maintenance-Run" border="0"> Standard Maintenance-Run</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>Extended</strong><br>';
				$htmlMain .= 'Extended Maintenance-Run. Like a standard-run but will also restart all died Transfers.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=12"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="vlc-kill" border="0"> Extended Maintenance-Run</a>';
				$htmlMain .= '<br><br>';
				break;

			case "11": // Maintenance : Main : Standard Maintenance-Run
				$htmlTitle = "Maintenance - Main - Standard Maintenance-Run";
				$htmlMain .= '<br>';
				$htmlMain .= 'Standard Maintenance-Run';
				require_once("inc/classes/MaintenanceAndRepair.php");
				MaintenanceAndRepair::maintenance(false);
				$htmlMain .= ' <font color="green">done</font>';
				$htmlMain .= '<br><br>';
				break;

			case "12": // Maintenance : Main
				$htmlTitle = "Maintenance - Main - Extended Maintenance-Run";
				$htmlMain .= '<br>';
				$htmlMain .= 'Extended Maintenance-Run';
				require_once("inc/classes/MaintenanceAndRepair.php");
				MaintenanceAndRepair::maintenance(true);
				$htmlMain .= ' <font color="green">done</font>';
				$htmlMain .= '<br><br>';
				break;

			case "2": // Maintenance-Kill
				$htmlTitle = "Maintenance - Kill";
				$htmlMain .= '<br>';
				$htmlMain .= '<font color="red"><strong>DONT</strong> do this or you will screw up things for sure !</font><br><br>';
				$htmlMain .= 'This is only meant as emergency-break if things go terrible wrong already.<br>Please use this only if you know what you are doing.';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>php</strong><br>';
				$htmlMain .= 'use this to kill all php processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=21"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="php-kill" border="0"> php-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>python</strong><br>';
				$htmlMain .= 'use this to kill all python processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=22"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="python-kill" border="0"> python-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>perl</strong><br>';
				$htmlMain .= 'use this to kill all perl processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=23"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="perl-kill" border="0"> perl-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>transmissioncli</strong><br>';
				$htmlMain .= 'use this to kill all transmissioncli processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=24"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="transmissioncli-kill" border="0"> transmissioncli-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>wget</strong><br>';
				$htmlMain .= 'use this to kill all wget processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=25"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="wget-kill" border="0"> wget-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>vlc</strong><br>';
				$htmlMain .= 'use this to kill all vlc processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=26"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="vlc-kill" border="0"> vlc-kill</a>';
				$htmlMain .= '<br><br>';
				break;

			case "21": // Maintenance-Kill : php
				$htmlTitle = "Maintenance - Kill - php";
				$htmlMain .= '<br>';
				$htmlMain .= '"kill all php processes" done.';
				$htmlMain .= '<br><br>';
				$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." php | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				$callResult = trim(shell_exec("killall -9 php 2> /dev/null"));
				if ((isset($callResult)) && ($callResult != "")) {
					$htmlMain .= '<br>';
					$htmlMain .= 'Call-Result : <br>';
					$htmlMain .= '<pre>'.$callResult.'</pre>';
					$htmlMain .= '<br>';
				}
				sleep(2); // just 2 sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." php | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;

			case "22": // Maintenance-Kill : python
				$htmlTitle = "Maintenance - Kill - python";
				$htmlMain .= '<br>';
				$htmlMain .= '"kill all python processes" done.';
				$htmlMain .= '<br><br>';
				$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." python | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				$callResult = trim(shell_exec("killall -9 python 2> /dev/null"));
				if ((isset($callResult)) && ($callResult != "")) {
					$htmlMain .= '<br>';
					$htmlMain .= 'Call-Result : <br>';
					$htmlMain .= '<pre>'.$callResult.'</pre>';
					$htmlMain .= '<br>';
				}
				sleep(2); // just 2 sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." python | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;

			case "23": // Maintenance-Kill : perl
				$htmlTitle = "Maintenance - Kill - perl";
				$htmlMain .= '<br>';
				$htmlMain .= '"kill all perl processes" done.';
				$htmlMain .= '<br><br>';
				$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." perl | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				$callResult = trim(shell_exec("killall -9 perl 2> /dev/null"));
				if ((isset($callResult)) && ($callResult != "")) {
					$htmlMain .= '<br>';
					$htmlMain .= 'Call-Result : <br>';
					$htmlMain .= '<pre>'.$callResult.'</pre>';
					$htmlMain .= '<br>';
				}
				sleep(2); // just 2 sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." perl | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;

			case "24": // Maintenance-Kill : transmissioncli
				$htmlTitle = "Maintenance - Kill - transmissioncli";
				$htmlMain .= '<br>';
				$htmlMain .= '"kill all transmissioncli processes" done.';
				$htmlMain .= '<br><br>';
				$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." transmissioncli | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				$callResult = trim(shell_exec("killall -9 transmissioncli 2> /dev/null"));
				if ((isset($callResult)) && ($callResult != "")) {
					$htmlMain .= '<br>';
					$htmlMain .= 'Call-Result : <br>';
					$htmlMain .= '<pre>'.$callResult.'</pre>';
					$htmlMain .= '<br>';
				}
				sleep(2); // just 2 sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." transmissioncli | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;

			case "25": // Maintenance-Kill : wget
				$htmlTitle = "Maintenance - Kill - wget";
				$htmlMain .= '<br>';
				$htmlMain .= '"kill all wget processes" done.';
				$htmlMain .= '<br><br>';
				$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." wget | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				$callResult = trim(shell_exec("killall -9 wget 2> /dev/null"));
				if ((isset($callResult)) && ($callResult != "")) {
					$htmlMain .= '<br>';
					$htmlMain .= 'Call-Result : <br>';
					$htmlMain .= '<pre>'.$callResult.'</pre>';
					$htmlMain .= '<br>';
				}
				sleep(2); // just 2 sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." wget | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;

			case "26": // Maintenance-Kill : vlc
				$htmlTitle = "Maintenance - Kill - vlc";
				$htmlMain .= '<br>';
				$htmlMain .= '"kill all vlc processes" done.';
				$htmlMain .= '<br><br>';
				$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." vlc | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				$callResult = trim(shell_exec("killall -9 vlc 2> /dev/null"));
				if ((isset($callResult)) && ($callResult != "")) {
					$htmlMain .= '<br>';
					$htmlMain .= 'Call-Result : <br>';
					$htmlMain .= '<pre>'.$callResult.'</pre>';
					$htmlMain .= '<br>';
				}
				sleep(2); // just 2 sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." vlc | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;

			case "3": // Maintenance-Clean
				$htmlTitle = "Maintenance - Clean";
				$htmlMain .= '<br>';
				$htmlMain .= '<strong>pid-file-leftovers</strong><br>';
				$htmlMain .= 'use this to delete pid-file-leftovers of deleted torrents.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=31"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="pid-file-clean" border="0"> pid-file-clean</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>tornado</strong><br>';
				$htmlMain .= 'use this to delete the cache of tornado. (stop your tornados first !)<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=32"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="tornado-clean" border="0"> tornado-clean</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>transmission</strong><br>';
				$htmlMain .= 'use this to delete cache-leftovers of deleted transmission-torrents.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=33"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="transmission-clean" border="0"> transmission-clean</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>mainline</strong><br>';
				$htmlMain .= 'use this to delete the cache of mainline. (stop your mainlines first !)<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=34"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="mainline-clean" border="0"> mainline-clean</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>template-cache</strong><br>';
				$htmlMain .= 'use this to delete the template-cache.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=35"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="template-cache-clean" border="0"> template-cache-clean</a>';
				$htmlMain .= '<br><br>';
				break;

			case "31": // Maintenance-Clean : pid-file-clean
				$htmlTitle = "Maintenance - Clean - pid-file";
				$htmlMain .= '<br>';
				$result = "";
				$torrents = getTorrentListFromDB();
				if ($dirHandle = @opendir($cfg["transfer_file_path"])) {
					while (false !== ($file = readdir($dirHandle))) {
						if ((substr($file, -1, 1)) == "d") {
							$tname = substr($file, 0, -9).'.torrent';
							if (! in_array($tname, $torrents)) {
								// torrent not in db. delete pid-file.
								$result .= $file."\n";
								@unlink($cfg["transfer_file_path"].$file);
							}
						}
					}
					closedir($dirHandle);
				}
				if (strlen($result) > 0)
					$htmlMain .= '<br>Deleted pid-leftovers : <br><pre>'.$result.'</pre><br>';
				else
					$htmlMain .= '<br>No pid-leftovers found.<br><br>';
				break;

			case "32": // Maintenance-Clean : tornado-clean
				$htmlTitle = "Maintenance - Clean - tornado";
				$htmlMain .= '<br>';
				$result = "";
				$result .= cleanDir($cfg["path"].'.BitTornado/datacache');
				$result .= cleanDir($cfg["path"].'.BitTornado/torrentcache');
				$result .= cleanDir($cfg["path"].'.BitTornado/piececache');
				$result .= cleanDir($cfg["path"].'.BitTornado/icons');
				if (strlen($result) > 0)
					$htmlMain .= '<br>Deleted  : <br><pre>'.$result.'</pre><br>';
				else
					$htmlMain .= '<br>Nothing found.<br><br>';
				break;

			case "33": // Maintenance-Clean : transmission-clean
				$htmlTitle = "Maintenance - Clean - transmission";
				$htmlMain .= '<br>';
				$result = "";
				$torrents = getTorrentListFromDB();
				$hashes = array();
				foreach ($torrents as $transfer)
					array_push($hashes, getTorrentHash($transfer));
				if ($dirHandle = @opendir($cfg["path"].".transmission/cache/")) {
					while (false !== ($file = readdir($dirHandle))) {
						if ($file{0} == "r") {
							$thash = substr($file,-40);
							if (! in_array($thash, $hashes)) {
								// torrent not in db. delete cache-file.
								$result .= $file."\n";
								@unlink($cfg["path"].".transmission/cache/resume.".$thash);
							}
						}
					}
					closedir($dirHandle);
				}
				if (strlen($result) > 0)
					$htmlMain .= '<br>Deleted cache-leftovers : <br><pre>'.$result.'</pre><br>';
				else
					$htmlMain .= '<br>No cache-leftovers found.<br><br>';
				break;

			case "34": // Maintenance-Clean : mainline-clean
				$htmlTitle = "Maintenance - Clean - mainline";
				$htmlMain .= '<br>';
				$result = "";
				$result .= cleanDir($cfg["path"].'.bittorrent/console/resume');
				$result .= cleanDir($cfg["path"].'.bittorrent/console/metainfo');
				$result .= cleanDir($cfg["path"].'.bittorrent/console/torrents');
				$result .= cleanDir($cfg["path"].'.bittorrent/mutex');
				if (strlen($result) > 0)
					$htmlMain .= '<br>Deleted  : <br><pre>'.$result.'</pre><br>';
				else
					$htmlMain .= '<br>Nothing found.<br><br>';
				break;

			case "35": // Maintenance-Clean :template-cache-clean
				$htmlTitle = "Maintenance - Clean - template-cache";
				$htmlMain .= '<br>';
				$result = cleanDir($cfg["path"].'.templateCache');
				if (strlen($result) > 0)
					$htmlMain .= '<br>Deleted compiled templates : <br><pre>'.$result.'</pre><br>';
				else
					$htmlMain .= '<br>No compiled templates found.<br><br>';
				break;

			case "4": // Maintenance : Repair
				$htmlTitle = "Maintenance - Repair";
				$htmlMain .= '<br>';
				$htmlMain .= '<font color="red"><strong>DONT</strong> do this if your system is running as it should. You WILL break something.</font>';
				$htmlMain .= '<br>use this after server-reboot, if torrents were killed or if there are other problems with the webapp.';
				$htmlMain .= '<br><a href="' . _FILE_THIS . '?m=41"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Repair" border="0"> Repair</a>';
				$htmlMain .= '<br><br>';
				break;

			case "41": // Maintenance : Repair
				$htmlTitle = "Maintenance - Repair";
				$htmlMain .= '<br>';
				$htmlMain .= 'Repair';
				require_once("inc/classes/MaintenanceAndRepair.php");
				MaintenanceAndRepair::repair();
				$htmlMain .= ' <font color="green">done</font>';
				$htmlMain .= '<br><br>';
				break;

			case "5": // Maintenance : Reset
				$htmlTitle = "Maintenance - Reset";
				$htmlMain .= '<br>';
				$htmlMain .= '<strong>torrent-totals</strong><br>';
				$htmlMain .= 'use this to reset the torrent-totals.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=51"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="torrent-totals" border="0"> torrent-totals-reset</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>xfer-stats</strong><br>';
				$htmlMain .= 'use this to reset the xfer-stats.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=52"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="xfer-stats" border="0"> xfer-stats-reset</a>';
				$htmlMain .= '<br><br>';
				break;

			case "51": // Maintenance : Reset - torrent-totals
				$htmlTitle = "Maintenance - Reset - torrent-totals";
				$htmlMain .= '<br>';
				$htmlMain .= 'Reset of torrent-totals';
				$result = resetAllTorentTotals();
				if ($result === true)
					$htmlMain .= ' <font color="green">done</font>';
				else
					$htmlMain .= '<br><font color="red">Error :</font><br>'.$result;
				$htmlMain .= '<br><br>';
				break;

			case "52": // Maintenance : Reset - xfer
				$htmlTitle = "Maintenance - Reset - xfer";
				$htmlMain .= '<br>';
				$htmlMain .= 'Reset of xfer-stats';
				$result = resetXferStats();
				if ($result === true)
					$htmlMain .= ' <font color="green">done</font>';
				else
					$htmlMain .= '<br><font color="red">Error :</font><br>'.$result;
				$htmlMain .= '<br><br>';
				break;

			case "6": // Maintenance : Lock
				$htmlTitle = "Maintenance - Lock";
				$htmlMain .= '<br>';
				switch ($cfg['webapp_locked']) {
					case 0:
						$htmlMain .= '<strong><font color="green">webapp currently unlocked.</font></strong>';
						break;
					case 1:
						$htmlMain .= '<strong><font color="red">webapp currently locked.</font></strong>';
						break;
				}
				$htmlMain .= '<p>';
				$htmlMain .= 'Use this to lock/unlock your webapp. only superadmin can access locked webapp.';
				$htmlMain .= '<br><a href="' . _FILE_THIS . '?m=61"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Repair" border="0"> ';
				if ($cfg['webapp_locked'] == 1)
					$htmlMain .= 'un';
				$htmlMain .= 'lock</a>';
				$htmlMain .= '<br><br>';
				break;

			case "61": // Maintenance : lock/unlock
				$htmlTitle = "Maintenance - Lock";
				$htmlMain .= '<br>';
				switch ($cfg['webapp_locked']) {
					case 0:
						$result = setWebappLock(1);
						if ($result === true)
							$htmlMain .= '<font color="red">webapp locked.</font>';
						else
							$htmlMain .= '<br><font color="red">Error :</font><br>'.$result;
						break;
					case 1:
						$result = setWebappLock(0);
						if ($result === true)
							$htmlMain .= '<font color="green">webapp unlocked.</font>';
						else
							$htmlMain .= '<br><font color="red">Error :</font><br>'.$result;
						break;
				}
				$htmlMain .= '<br><br>';
				break;
		}
		printPage();
		exit();
	}
}

// -----------------------------------------------------------------------------
// log
// -----------------------------------------------------------------------------
if (isset($_REQUEST["l"])) {
	$action = trim($_REQUEST["l"]);
	if ($action != "") {
		buildPage("l");
		switch($action) {

			case "0": // log-main
				$htmlTitle = "log";
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?l=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="fluxd" border="0"> fluxd</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?l=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="fluxd-error" border="0"> fluxd-error</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?l=5"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="mainline" border="0"> mainline</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?l=8"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="transfers" border="0"> transfers</a>';
				$htmlMain .= '<br><br>';
				break;

			case "1": // fluxd-log
				$htmlTitle = "log - fluxd";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= file_get_contents($cfg["path"].'.fluxd/fluxd.log');
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "2": // fluxd-error-log
				$htmlTitle = "log - fluxd - error-log";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= file_get_contents($cfg["path"].'.fluxd/fluxd-error.log');
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "5": // mainline-log
				$htmlTitle = "log - mainline";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$mainlineLog = $cfg["path"].'.bittorrent/tfmainline.log';
				if (is_file($mainlineLog))
					$htmlMain .= file_get_contents($mainlineLog);
				else
					$htmlMain .= "mainline-log not found.";
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "8": // transfers
				$htmlTitle = "log - transfers";
				$logList = getTransferArray('na');
				if ((isset($logList)) && (is_array($logList))) {
					$htmlMain .= '<ul>';
					foreach ($logList as $logFile) {
						if ((isset($logFile)) && ($logFile != "")) {
							$htmlMain .= '<li>';
							$htmlMain .= '<a href="'. _FILE_THIS .'?l=9&transfer='.$logFile.'">';
							$htmlMain .= $logFile;
							$htmlMain .= '</a>';
							$htmlMain .= '</li>';
						}
					}
					$htmlMain .= '</ul>';
				}
				break;

			case "9": // transfer-log
				if (isset($_REQUEST["transfer"])) {
					$transfer = trim(htmlentities($_REQUEST["transfer"], ENT_QUOTES));
					// shorten name if too long
					if(strlen($transfer) >= 70)
						$htmlTitle = "log - transfer-log - ".substr($transfer, 0, 67)."...";
					else
						$htmlTitle = "log - transfer-log - ".$transfer;
					$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
					$htmlMain .= '<pre>';
					$htmlMain .= getTransferLog($transfer);
					$htmlMain .= '</pre>';
					$htmlMain .= '</div>';
				} else {
					$htmlTitle = "log - transfer-log";
					$htmlMain .= '<font color="red">Error. missing params</font>';
				}
				break;
		}
		printPage();
		exit();
	}
}

// -----------------------------------------------------------------------------
// torrents
// -----------------------------------------------------------------------------
if (isset($_REQUEST["t"])) {
	$torrentAction = @trim($_REQUEST["t"]);
	if ($torrentAction != "") {
		buildPage("t");
		switch($torrentAction) {

			case "0": // Torrents-main
				$htmlTitle = "Torrents";
				$htmlMain .= '<br>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?t=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Stop All Torrents" border="0"> Stop All Torrents</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?t=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Start All Torrents" border="0"> Start All Torrents</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?t=3"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Resume All Torrents" border="0"> Resume All Torrents</a>';
				$htmlMain .= '<br><br>';
				break;

			case "1": // Torrents-Stop
				$htmlTitle = "Torrents - Stop";
				$htmlMain .= '<br><strong>Torrents Stopped :</strong><br>';
				$htmlMain .= '<pre>';
				$torrents = getTorrentListFromFS();
				foreach ($torrents as $transfer) {
					$tRunningFlag = isTransferRunning($transfer);
					if ($tRunningFlag != 0) {
						$btclient = getTransferClient($transfer);
						$clientHandler = ClientHandler::getInstance($btclient);
						$clientHandler->stop($transfer);
						$htmlMain .=  ' - '.$transfer."";
						$htmlMain .=  "\n";
					}
				}
				$htmlMain .= '</pre>';
				$htmlMain .= '<hr><br>';
				break;

			case "2": // Torrents-Start
				$htmlTitle = "Torrents - Start";
				$htmlMain .= '<br><strong>Torrents Started :</strong><br>';
				$htmlMain .= '<pre>';
				$torrents = getTorrentListFromFS();
				foreach ($torrents as $transfer) {
					$tRunningFlag = isTransferRunning($transfer);
					if ($tRunningFlag == 0) {
						$btclient = getTransferClient($transfer);
						if ($cfg["enable_file_priority"]) {
							include_once("inc/functions/functions.setpriority.php");
							// Process setPriority Request.
							setPriority($transfer);
						}
						$clientHandler = ClientHandler::getInstance($btclient);
						$clientHandler->start($transfer, false, false);
						$htmlMain .=  ' - '.$transfer."";
						$htmlMain .=  "\n";
					}
				}
				$htmlMain .= '</pre>';
				$htmlMain .= '<hr><br>';
				break;

			case "3": // Torrents-Resume
				$htmlTitle = "Torrents - Resume";
				$htmlMain .= '<br><strong>Torrents Resumed :</strong><br>';
				$htmlMain .= '<pre>';
				$torrents = getTorrentListFromDB();
				foreach ($torrents as $transfer) {
					$tRunningFlag = isTransferRunning($transfer);
					if ($tRunningFlag == 0) {
						$btclient = getTransferClient($transfer);
						if ($cfg["enable_file_priority"]) {
							include_once("inc/functions/functions.setpriority.php");
							// Process setPriority Request.
							setPriority($transfer);
						}
						$clientHandler = ClientHandler::getInstance($cfg,$btclient);
						$clientHandler->start($transfer, false, false);
						$htmlMain .=  ' - '.$transfer."";
						$htmlMain .=  "\n";
					}
				}
				$htmlMain .= '</pre>';
				$htmlMain .= '<hr><br>';
				break;
		}
		$htmlMain .= '<br><strong>Torrents :</strong><br>';
		$htmlMain .= '<pre>';
		$torrents = getTorrentListFromFS();
		foreach ($torrents as $transfer) {
			$htmlMain .=  ' - '.$transfer."";
			if (isTransferRunning($transfer))
				$htmlMain .=  " (running)";
			$htmlMain .=  "\n";
		}
		$htmlMain .= '</pre>';
		printPage();
		exit();
	}
}

// -----------------------------------------------------------------------------
// tf-b4rt
// -----------------------------------------------------------------------------
if (isset($_REQUEST["z"])) {
	$action = trim($_REQUEST["z"]);
	if ($action != "") {
		buildPage("z");
		switch($action) {

			case "0": // main
				$htmlTitle = "tf-b4rt";
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Version" border="0"> Version</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="News" border="0"> News</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=3"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Changelog" border="0"> Changelog</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=9"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Misc" border="0"> Misc</a>';
				$htmlMain .= '<br><br>';
				break;

			case "1": // Version
				$htmlTitle = "tf-b4rt - Version";
				// version-check
				$versionAvailable = trim(getDataFromUrl(_SUPERADMIN_URLBASE._SUPERADMIN_PROXY));
				if ((isset($versionAvailable)) && ($versionAvailable != "")) {
					// set image
					if ($versionAvailable == _VERSION || (substr(_VERSION, 0, 3)) == "svn")
						$statusImage = "green.gif";
					else
						$statusImage = "red.gif";
					// version-text
					$htmlMain .= '<br>';
					if (strpos(_VERSION, "svn") !== false) {
					        $htmlMain .= '<strong>This Version : </strong>'._VERSION;
	    					$htmlMain .= '<br><br>';
	    					$htmlMain .= '<strong>Latest Release : </strong>';
	    					$htmlMain .= $versionAvailable;
	    					$htmlMain .= '<br><br>';
	    					$htmlMain .= '<font color="blue">This Version is a svn-Version.</font>';
					} else {
	    				if ($versionAvailable != _VERSION) {
	    					$htmlMain .= '<strong>This Version : </strong>';
	    					$htmlMain .= '<font color="red">'._VERSION.'</font>';
	    					$htmlMain .= '<br><br>';
	    					$htmlMain .= '<strong>Available Version : </strong>';
	    					$htmlMain .= $versionAvailable;
	    					$htmlMain .= '<br><br>';
	    					$htmlMain .= '<strong><font color="red">There is a new Version available !</font></strong>';
	    					$htmlMain .= '<br><br>';
							$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
							$htmlMain .= '<input type="Hidden" name="u" value="0">';
							$htmlMain .= '<input type="submit" value="Update to Version '.$versionAvailable.'">';
							$htmlMain .= '</form>';
	    					$htmlMain .= '<strong>Current Release : </strong>';
	    					$htmlMain .= '<br>';
	    					$htmlMain .= '<a href="'._URL_RELEASE.'" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Current Release" border="0"> '._URL_RELEASE.'</a>';
	    					$htmlMain .= '<br><br>';
	    					$htmlMain .= '<strong>Homepage : </strong>';
	    					$htmlMain .= '<br>';
	    					$htmlMain .= '<a href="'._URL_HOME.'" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Homepage on berliOS" border="0"> '._URL_HOME.'</a>';
	    					$htmlMain .= '<br>';
	    				} else {
	    					$htmlMain .= '<strong>This Version : </strong>'._VERSION;
	    					$htmlMain .= '<br><br>';
	    					$htmlMain .= '<strong>Available Version : </strong>';
	    					$htmlMain .= $versionAvailable;
	    					$htmlMain .= '<br><br>';
	    					$htmlMain .= '<font color="green">This Version looks good.</font>';
	    				}
					}
					$htmlMain .= '<br><br>';
				} else { // could not get the version
					$statusImage = "black.gif";
					$htmlTop = '<strong><font color="red">Error.</font></strong>';
					$htmlMain = '<br>';
					$htmlMain .= '<font color="red">Error getting available version.</font>';
					$htmlMain .= '<br><br>';
	    			$htmlMain .= '<strong>Current Release : </strong>';
	    			$htmlMain .= '<br>';
	    			$htmlMain .= '<a href="'._URL_RELEASE.'" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Current Release" border="0"> '._URL_RELEASE.'</a>';
					$htmlMain .= '<br><br>';
					$htmlMain .= '<strong>Homepage : </strong>';
					$htmlMain .= '<br>';
					$htmlMain .= '<a href="'._URL_HOME.'" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Homepage on berliOS" border="0"> '._URL_HOME.'</a>';
					$htmlMain .= '<br>';
				}
				break;

			case "2": // News
				$htmlTitle = "tf-b4rt - News";
				$htmlMain .= '<br>';
				$htmlMain .= @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=0"));
				$htmlMain .= '<br><br>';
				break;

			case "3": // Changelog;
				$htmlTitle = "tf-b4rt - Changelog";
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=1"));
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
				break;

			case "9": // Misc-main
				$htmlTitle = "Misc";
				$htmlMain .= '<p>';
				$htmlMain .= '<img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Checksums" border="0"> File-List (';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=91" target="_blank">html</a>';
				$htmlMain .= ' / ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=92" target="_blank">text</a>';
				$htmlMain .= ')';
				$htmlMain .= '<p>';
				$htmlMain .= '<img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Checksums" border="0"> Checksums (';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=93" target="_blank">html</a>';
				$htmlMain .= ' / ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=94" target="_blank">text</a>';
				$htmlMain .= ')';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=95" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Checksums of '._VERSION.'" border="0"> Checksums of '._VERSION.'</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?z=96" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Validate local files" border="0"> Validate local files</a>';
				$htmlMain .= '<br><br>';
				break;

			case "91": // Misc - File-List - html
				printFileList($cfg['docroot'], 1, 2);
				exit();

			case "92": // Misc - File-List - text
				@header("Content-Type: text/plain");
				printFileList($cfg['docroot'], 1, 1);
				exit();

			case "93": // Misc - Checksums - html
				printFileList($cfg['docroot'], 2, 2);
				exit();

			case "94": // Misc - Checksums - text
				@header("Content-Type: text/plain");
				printFileList($cfg['docroot'], 2, 1);
				exit();

			case "95": // Misc - Checksums - Latest
				@header("Content-Type: text/plain");
				echo getDataFromUrl(_SUPERADMIN_URLBASE._FILE_CHECKSUMS_PRE._VERSION._FILE_CHECKSUMS_SUF);
				exit();

			case "96": // Misc - Validate
				validateLocalFiles();
				exit();

		}
		printPage();
		exit();
	}
}

// -----------------------------------------------------------------------------
// default
// -----------------------------------------------------------------------------
buildPage("_");
printPage();
exit();

?>