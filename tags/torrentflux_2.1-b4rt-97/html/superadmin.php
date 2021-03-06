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

// defines
define('_DIR_BACKUP','.backup');
define('_URL_HOME','http://tf-b4rt.berlios.de/');
define('_VERSION_LOCAL','version');
define('_SUPERADMIN_URLBASE','http://tf-b4rt.berlios.de/');
define('_SUPERADMIN_PROXY','superadminProxy.php');
define('_FILE_THIS',$_SERVER['SCRIPT_NAME']);

// includes
require_once("config.php");
require_once("functions.php");

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
performAuthentication();

// fopen
ini_set("allow_url_fopen", "1");

// get + define this version (is done in config.php but we dont want that here)
define('_VERSION_THIS',trim(getDataFromFile(_VERSION_LOCAL)));

// backup
if (isset($_REQUEST["b"])) {
	$backupStep = trim($_REQUEST["b"]);
	if ($backupStep != "") {
		switch($backupStep) {
			case "0": // choose backup-type
				buildPage("b");
				$htmlMain .= '<br>';
				$htmlMain .= '<a href="'. _FILE_THIS .'?b=3"><img src="images/arrow.gif" width="9" height="9" title="Backups on Server" border="0"> Backups on Server</a><p>';
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
				$htmlTitle = "Backup";
				printPage();
				exit;
				break;
			case "1": // server-backup
				if (ob_get_level() == 0) ob_start();
				$htmlTitle = "Backup";
				buildPage("b");
				printPageStart(1);
				$backupArchive = backupCreate(true,$_REQUEST["c"]);
				if ($backupArchive == "") {
					sendLine('<br><br>');
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
				ob_end_flush();
				exit;
				break;
			case "2": // client-backup
				$backupArchive = backupCreate(false,$_REQUEST["c"]);
				if ($backupArchive == "") {
					buildPage("-b");
					$htmlMain .= '<br><br>';
					$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
					$htmlMain .= $error;
					$htmlTitle = "Backup";
					printPage();
					exit;
				} else {
					backupSend($backupArchive,true);
					exit;
				}
				break;
			case "3": // backup-list
				$htmlTitle = "Backup";
				buildPage("b");
				$htmlMain .= '<br>';
				$htmlMain .= backupListDisplay();
				printPage();
				exit;
				break;
			case "4": // download backup
				$backupArchive = trim($_REQUEST["f"]);
				if (backupParamCheck($backupArchive)) {
					backupSend($backupArchive,false);
					exit;
				} else {
					buildPage("-b");
					$htmlMain .= '<br><br>';
					$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
					$htmlMain .= $backupArchive.' is not a valid Backup-ID';
					$htmlTitle = "Backup";
					printPage();
					exit;
				}
				break;
			case "5": // delete backup
				$backupArchive = trim($_REQUEST["f"]);
				if (backupParamCheck($backupArchive)) {
					backupDelete($backupArchive);
					buildPage("b");
					$htmlMain .= '<br>';
					$htmlMain .= '<em>'.$backupArchive.'</em> deleted.';
					$htmlMain .= '<br><br>';
					$htmlMain .= backupListDisplay();
					$htmlTitle = "Backup";
					printPage();
					exit;
				} else {
					buildPage("-b");
					$htmlMain .= '<br><br>';
					$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
					$htmlMain .= $backupArchive.' is not a valid Backup-ID';
					$htmlTitle = "Backup";
					printPage();
					exit;
				}
				exit;
				break;
		}
		exit;
	}
}

// update
if (isset($_REQUEST["u"])) {
	$updateStep = trim($_REQUEST["u"]);
	if ($updateStep != "") {
		switch($updateStep) {
			case "0":
				// get updateIndex to check if update from this version possible
				$updateIndexData = trim(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=0&v=" . _VERSION_THIS));
				if ((isset($updateIndexData)) && ($updateIndexData != "")) {
					$updateIndexVars = explode("\n",$updateIndexData);
					$updatePossible = trim($updateIndexVars[0]);
					if ($updatePossible != "1") {
						buildPage("-u");
						printPage();
						exit;
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
						exit;
					}
				} else {
					buildPage("-u");
					printPage();
					exit;
				}
				break;
			case "1":
				// get db-settings
				$updateDBData = trim(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=1&v=" . _VERSION_THIS));
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
						exit;
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
						exit;
					} else {
						updateError($updateDBData);
						exit;
					}
				} else {
					updateError();
				}
				break;
			case "2":
				// get sql-data
				$updateSQLData = trim(gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=2&v=" . _VERSION_THIS . "&d=".$cfg["db_type"])));
				if ((isset($updateSQLData)) && ($updateSQLData != "")) {
					if (ob_get_level() == 0) ob_start();
					sendLine('<strong>Update - Database</strong><br><br><em>Updating Database... Please Wait...</em><ul>');
					$updateSQLStates = explode("\n",$updateSQLData);
					// get ado-connection
					$dbCon = getAdoConnection();
					if (!$dbCon) {
						echo '</em></li></ul><strong><font color="red"><strong>Error updating Database.</font></strong><br><br>Please restore backup and try again (or do manual update).</strong><br><br>';
						echo $dbCon->ErrorMsg();
						ob_end_flush();
						exit;
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
									echo '</em></li></ul><strong><font color="red"><strong>Error updating Database.</font></strong><br><br>Please restore backup and try again (or do manual update).</strong><br><br>';
									ob_end_flush();
									exit;
								}
							}
						}
						// close ado-connection
						$dbCon->Close();
						// talk and continue
						sendLine('</ul><p><font color="green">Database-Update done.</font><br><br>');
						sendLine('<form name="update" action="' . _FILE_THIS . '" method="post"><input type="Hidden" name="u" value="3"><input type="submit" value="Next Step - File-Update"></form><br>');
						ob_end_flush();
						exit;
					}
				} else {
					updateError("\n"."cant get update-sql."."\n".$updateSQLData);
				}
				break;
			case "3":
				// get file-list
				$updateFileList = trim(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=3&v=" . _VERSION_THIS));
				if ((isset($updateFileList)) && ($updateFileList != "")) {
					echo '<strong>Update - Files</strong>';
					echo '<br><br>';
					echo 'Files that require an update in this Version :';
					echo '<pre>';
					echo $updateFileList;
					echo '</pre>';
					echo '<br><br>';
					echo '<form name="update" action="' . _FILE_THIS . '" method="post">';
					echo '<input type="Hidden" name="u" value="4">';
					echo '<input type="Hidden" name="f" value="' . $updateFileList . '">';
					echo '<input type="submit" value="Next Step - Perform File-Update">';
					echo '</form>';
					exit;
				} else {
					updateError("\n"."cant get file-list."."\n".$updateFileList);
				}
				break;
			case "4":
				$updateFileList = trim($_POST["f"]);
				if ((isset($updateFileList)) && ($updateFileList != "")) {
					if (ob_get_level() == 0) ob_start();
					sendLine('<strong>Update - Files</strong><br><br><em>Updating Files... Please Wait...</em><ul>');
					$updateFileAry = explode("\n",$updateFileList);
					foreach ($updateFileAry as $requestFile) {
						$requestFile = trim($requestFile);
						sendLine('<li>'.$requestFile);
						$fileData = trim(gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=4&v=" . _VERSION_THIS . "&f=".$requestFile)));
						sendLine(' (' . strlen($fileData) .')');
						if ($handle = fopen($requestFile, "w")) {
							if (fwrite($handle, $fileData)) {
								fclose($handle);
								sendLine(' <font color="green">Ok</font></li>');
							} else {
								sendLine('</li></ul><br><br>');
								sendLine('<font color="red"><strong>Error updating files</font><br><br>Please restore backup and try again (or do manual update).</strong><br><br>');
								ob_end_flush();
								exit;
							}
						} else {
							sendLine('</li></ul><br><br>');
							sendLine('<font color="red"><strong>Error updating files</font><br><br>Please restore backup and try again (or do manual update).</strong><br><br>');
							ob_end_flush();
							exit;
						}
					}
					sendLine('</ul><p><font color="green">File-Update done.</font><br><br>');
					sendLine('Updating Version-Information...');
					$versionAvailable = trim(getDataFromUrl(_SUPERADMIN_URLBASE._SUPERADMIN_PROXY));
					if ((isset($versionAvailable)) && ($versionAvailable != "")) {
						if ($handle = fopen(_VERSION_LOCAL, "w")) {
							if (fwrite($handle, $versionAvailable)) {
								fclose($handle);
								sendLine(' <font color="green">Ok</font>');
							} else {
								sendLine('<br><br>');
								sendLine('<font color="red"><strong>Error writing version-file</font><br><br>Please restore backup and try again (or do manual update).</strong><br><br>');
								ob_end_flush();
								exit;
							}
						} else {
							sendLine('<br><br>');
							sendLine('<font color="red"><strong>Error writing version-file</font><br><br>Please restore backup and try again (or do manual update).</strong><br><br>');
							ob_end_flush();
							exit;
						}
						sendLine('<hr><br><strong>Update to '.$versionAvailable.' done.</strong><br><br>');
						sendLine('Keep thumbs pressed and give it a try.');
						sendLine('<form name="update" action="#" method="get"><input type="submit" onClick="window.close()" value="Close"></form>');
						sendLine('<br>');
						ob_end_flush();
						exit;
					} else {
							sendLine('<br><br><font color="red"><strong>Error getting version-file</font><br><br>Please restore backup and try again (or do manual update).</strong><br><br>');
							ob_end_flush();
							exit;
					}
				} else {
					updateError("\n"."cant perform file-update."."\n".$updateFileList);
				}
				break;
		}
		exit;
	}
}

// queue
if (isset($_REQUEST["q"])) {
	$queueAction = trim($_REQUEST["q"]);
	if ($queueAction != "") {
		buildPage("q");
		switch($queueAction) {
			case "0": // tfqmgr-main
				$htmlTitle = "tfqmgr";
				break;
			case "1": // tfqmgr-log
				$htmlTitle = "tfqmgr-log";
				$htmlMain .= '<pre>';
				$htmlMain .= getDataFromFile($cfg["path"].'.tfqmgr/tfqmgr.log');
				$htmlMain .= '</pre>';
				break;
			case "2": // tfqmgr-ps
				$htmlTitle = "tfqmgr-ps";
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." tfqmgr.pl | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				break;
			case "3": // tfqmgr-status
				$htmlTitle = "tfqmgr-status";
				include_once("QueueManager.php");
				$queueManager = QueueManager::getQueueManagerInstance($cfg);
				if (($queueManager->isQueueManagerRunning()) && ($queueManager->managerName == "tfqmgr")) {
					$htmlMain .= '<br><pre>';
					$htmlMain .= $queueManager->statusQueueManager();
					$htmlMain .= '</pre>';
				} else {
					$htmlMain .= '<br><strong>tfqmgr not running</strong>';
				}
				break;
		}
		printPage();
		exit;
	}
}

// maintenance
if (isset($_REQUEST["m"])) {
	$mAction = trim($_REQUEST["m"]);
	if ($mAction != "") {
		buildPage("m");
		switch($mAction) {
			case "0": // Maintenance-main
				$htmlTitle = "Maintenance";
				break;
			case "1": // Maintenance-Kill
				$htmlTitle = "Maintenance - Kill";
				$htmlMain .= '<br>';
				$htmlMain .= '<font color="red"><strong>DONT</strong> do this or you will screw up things for sure !</font><br><br>';
				$htmlMain .= 'This is only meant as emergency-break if things go terrible wrong already.<br>Please use this only if you know what you are doing.';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>php</strong><br>';
				$htmlMain .= 'use this to kill all php processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=11"><img src="images/arrow.gif" width="9" height="9" title="php-kill" border="0"> php-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>python</strong><br>';
				$htmlMain .= 'use this to kill all python processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=12"><img src="images/arrow.gif" width="9" height="9" title="python-kill" border="0"> python-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>perl</strong><br>';
				$htmlMain .= 'use this to kill all perl processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=13"><img src="images/arrow.gif" width="9" height="9" title="perl-kill" border="0"> perl-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>transmissioncli</strong><br>';
				$htmlMain .= 'use this to kill all transmissioncli processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=14"><img src="images/arrow.gif" width="9" height="9" title="transmissioncli-kill" border="0"> transmissioncli-kill</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>wget</strong><br>';
				$htmlMain .= 'use this to kill all wget processes.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=15"><img src="images/arrow.gif" width="9" height="9" title="wget-kill" border="0"> wget-kill</a>';
				$htmlMain .= '<br><br>';
				break;
			case "11": // Maintenance-Kill : php
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
				sleep(1); // just a sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." php | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;
			case "12": // Maintenance-Kill : python
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
				sleep(1); // just a sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." python | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;
			case "13": // Maintenance-Kill : perl
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
				sleep(1); // just a sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." perl | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;
			case "14": // Maintenance-Kill : transmissioncli
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
				sleep(1); // just a sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." transmissioncli | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;
			case "15": // Maintenance-Kill : wget
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
				sleep(1); // just a sec
				$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
				$htmlMain .= '<pre>';
				$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." wget | ".$cfg['bin_grep']." -v grep"));
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				break;
			case "2": // Maintenance-clean
				$htmlTitle = "Maintenance-clean";
				$htmlMain .= '<br>';
				$htmlMain .= '<strong>pid-file-leftovers</strong><br>';
				$htmlMain .= 'use this to delete pid-file-leftovers of deleted torrents.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=21"><img src="images/arrow.gif" width="9" height="9" title="pid-file-clean" border="0"> pid-file-clean</a>';
				$htmlMain .= '<p>';
				$htmlMain .= '<strong>transmission-cache</strong><br>';
				$htmlMain .= 'use this to delete cache-leftovers of deleted transmission-torrents.<br>';
				$htmlMain .= '<a href="' . _FILE_THIS . '?m=22"><img src="images/arrow.gif" width="9" height="9" title="transmission-cache-clean" border="0"> transmission-cache-clean</a>';
				$htmlMain .= '<br><br>';
				break;
			case "21": // Maintenance-clean : pid-file-clean
				$htmlTitle = "Maintenance-pid-file-clean";
				$htmlMain .= '<br>';
				$result = "";
				$torrents = getTorrentListFromDB();
				if ($dirHandle = opendir($cfg["torrent_file_path"])) {
					while (false !== ($file = readdir($dirHandle))) {
						if ((substr($file, -1, 1)) == "d") {
							$tname = substr($file,0,-9).'.torrent';
							if (! in_array($tname, $torrents)) {
								// torrent not in db. delete pid-file.
								$result .= $file."\n";
								@unlink($cfg["torrent_file_path"].$file);
							}
						}
					}
					closedir($dirHandle);
				}
				if (strlen($result) > 0)
					$htmlMain .= '<br>Deleted pid-leftovers : <pre>'.$result.'</pre><br>';
				else
					$htmlMain .= '<br>No pid-leftovers found.<br><br>';
				break;
			case "22": // Maintenance-clean : transmission-cache-clean
				$htmlTitle = "Maintenance-transmission-cache-clean";
				$htmlMain .= '<br>';
				$result = "";
				$torrents = getTorrentListFromDB();
				$hashes = array();
				foreach ($torrents as $torrent)
					array_push($hashes, getTorrentHash($torrent));
				if ($dirHandle = opendir($cfg["path"].".transmission/cache/")) {
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
					$htmlMain .= '<br>Deleted cache-leftovers : <pre>'.$result.'</pre><br>';
				else
					$htmlMain .= '<br>No cache-leftovers found.<br><br>';
				break;
			case "3": // Maintenance : Repair
				$htmlTitle = "Maintenance - Repair";
				$htmlMain .= '<br>';
				$htmlMain .= '<font color="red"><strong>DONT</strong> do this if your system is running as it should. You WILL break something.</font>';
				$htmlMain .= '<br>use this after server-reboot, if torrents were killed or if there are other problems with the webapp.';
				$htmlMain .= '<br><a href="' . _FILE_THIS . '?m=31"><img src="images/arrow.gif" width="9" height="9" title="Repair" border="0"> Repair</a>';
				$htmlMain .= '<br><br>';
				break;
			case "31": // Maintenance : Repair
				$htmlTitle = "Maintenance - Repair";
				$htmlMain .= '<br>';
				$htmlMain .= 'Repair of torrentflux-b4rt Installation';
				repairTorrentflux();
				$htmlMain .= ' <font color="green">done.</font>';
				$htmlMain .= '<br><br>';
				break;
		}
		printPage();
		exit;
	}
}

// torrents
if (isset($_REQUEST["t"])) {
	$torrentAction = @trim($_REQUEST["t"]);
	if ($torrentAction != "") {
		buildPage("t");
		switch($torrentAction) {
			case "0": // Torrents-main
				$htmlTitle = "Torrents";
				break;
			case "1": // Torrents-Stop
				include_once("ClientHandler.php");
				$htmlTitle = "Torrents-Stop";
				$htmlMain .= '<br><strong>Torrents Stopped :</strong><br>';
				$htmlMain .= '<pre>';
				$torrents = getTorrentListFromFS();
				foreach ($torrents as $torrent) {
					$torrentRunningFlag = isTorrentRunning($torrent);
					if ($torrentRunningFlag != 0) {
						$alias = getAliasName($torrent).".stat";
						$btclient = getTorrentClient($torrent);
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
						$clientHandler->stopTorrentClient($torrent, $alias);
						$htmlMain .=  ' - '.$torrent."";
						$htmlMain .=  "\n";
						// just 2 sec..
						sleep(2);
					}
				}
				$htmlMain .= '</pre>';
				$htmlMain .= '<hr><br>';
				break;
			case "2": // Torrents-Start
				include_once("ClientHandler.php");
				$htmlTitle = "Torrents-Start";
				$htmlMain .= '<br><strong>Torrents Started :</strong><br>';
				$htmlMain .= '<pre>';
				$torrents = getTorrentListFromFS();
				foreach ($torrents as $torrent) {
					$torrentRunningFlag = isTorrentRunning($torrent);
					if ($torrentRunningFlag == 0) {
						$btclient = getTorrentClient($torrent);
						if ($cfg["enable_file_priority"]) {
							include_once("setpriority.php");
							// Process setPriority Request.
							setPriority($torrent);
						}
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
						$clientHandler->startTorrentClient($torrent, 0);
						$htmlMain .=  ' - '.$torrent."";
						$htmlMain .=  "\n";
						// just 2 sec..
						sleep(2);
					}
				}
				$htmlMain .= '</pre>';
				$htmlMain .= '<hr><br>';
				break;
			case "3": // Torrents-Resume
				include_once("ClientHandler.php");
				$htmlTitle = "Torrents-Resume";
				$htmlMain .= '<br><strong>Torrents Resumed :</strong><br>';
				$htmlMain .= '<pre>';
				$torrents = getTorrentListFromDB();
				foreach ($torrents as $torrent) {
					$torrentRunningFlag = isTorrentRunning($torrent);
					if ($torrentRunningFlag == 0) {
						$btclient = getTorrentClient($torrent);
						if ($cfg["enable_file_priority"]) {
							include_once("setpriority.php");
							// Process setPriority Request.
							setPriority($torrent);
						}
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
						$clientHandler->startTorrentClient($torrent, 0);
						$htmlMain .=  ' - '.$torrent."";
						$htmlMain .=  "\n";
						// just 2 sec..
						sleep(2);
					}
				}
				$htmlMain .= '</pre>';
				$htmlMain .= '<hr><br>';
				break;
		}
		$htmlMain .= '<br><strong>Torrents :</strong><br>';
		$htmlMain .= '<pre>';
		$torrents = getTorrentListFromFS();
		foreach ($torrents as $torrent) {
			$htmlMain .=  ' - '.$torrent."";
			if (isTorrentRunning($torrent))
				$htmlMain .=  " (running)";
			$htmlMain .=  "\n";
		}
		$htmlMain .= '</pre>';
		printPage();
		exit;
	}
}

// standard-action
buildPage(@trim($_REQUEST["a"]));
printPage();
exit;

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * performAuthentication
 *
 */
function performAuthentication($message = "") {
	if (! IsSuperAdmin()) {
		header("Content-Type: text/plain");
		echo "\nAccess Error"."\n\n";
		if ((isset($message)) && ($message != ""))
			echo $message."\n";
		else
			echo "Only SuperAdmin can access superadmin-page.\n";
		exit;
	}
}

/**
 * builds page
 *
 */
function buildPage($action) {
	global $cfg, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	// navi
	$htmlTop .= '<a href="' . _FILE_THIS . '?t=0">Torrents</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?b=0">Backup</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?m=0">Maintenance</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?q=0">tfqmgr</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?a=1">Help</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?a=0">Version</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?a=5">News</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?a=2">Changelog</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?a=3" target="_blank">Issues</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?a=4">Update</a>';
	// body
	switch($action) {
		case "b": // backup passthru
			$statusImage = "yellow.gif";
			break;
		case "-b": // backup-error passthru
			$statusImage = "red.gif";
			break;
		case "-u": // update-error passthru
			$statusImage = "red.gif";
			$htmlTitle = "Update";
			$htmlMain = '<br><font color="red"><strong>Update from your Version not possible.</strong></font>';
			$htmlMain .= '<br><br>';
			$htmlMain .= 'Please use the most recent tarball and perform a manual update.';
			$htmlMain .= '<br>';
			//$htmlMain .= '<br><br>';
			//$htmlMain .= getReleaseList();
			break;
		case "q": // queue passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?q=1">log</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?q=2">ps</a>';
			include_once("QueueManager.php");
			$queueManager = QueueManager::getQueueManagerInstance($cfg);
			if (($queueManager->isQueueManagerRunning()) && ($queueManager->managerName == "tfqmgr")) {
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?q=3">status</a>';
			}
			$htmlMain .= '</td><td align="right"><strong>tfqmgr</td>';
			$htmlMain .= '</td></tr></table>';
			break;
		case "m": // maintenance passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=1">kill</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=2">clean</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=3">repair</a>';
			$htmlMain .= '</td><td align="right"><strong>Maintenance</td>';
			$htmlMain .= '</td></tr></table>';
			break;
		case "t": // torrent passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?t=1">Stop All Torrents</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?t=2">Start All Torrents</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?t=3">Resume All Torrents</a>';
			$htmlMain .= '</td><td align="right"><strong>Torrents</td>';
			$htmlMain .= '</td></tr></table>';
			break;
		case "0": // version
			$htmlTitle = "Version";
			// version-check
			$versionAvailable = trim(getDataFromUrl(_SUPERADMIN_URLBASE._SUPERADMIN_PROXY));
			if ((isset($versionAvailable)) && ($versionAvailable != "")) {
				// set image
				if ($versionAvailable == _VERSION_THIS || (substr(_VERSION_THIS, 0, 3)) == "svn")
					$statusImage = "green.gif";
				else
					$statusImage = "red.gif";
				// version-text
				$htmlMain .= '<br>';
				if ((substr(_VERSION_THIS, 0, 3)) == "svn") {
				        $htmlMain .= '<strong>This Version : </strong>'._VERSION_THIS;
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong>Available Version : </strong>';
    					$htmlMain .= $versionAvailable;
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<font color="blue">This Version is a svn-Version.</font>';
				} else {
    				if ($versionAvailable != _VERSION_THIS) {
    					$htmlMain .= '<strong>This Version : </strong>';
    					$htmlMain .= '<font color="red">'._VERSION_THIS.'</font>';
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong>Available Version : </strong>';
    					$htmlMain .= $versionAvailable;
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong><font color="red">There is a new Version available !</font></strong>';
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong>Homepage : </strong>';
    					$htmlMain .= '<br>';
    					$htmlMain .= '<a href="'._URL_HOME.'" target="_blank">'._URL_HOME.'</a>';
    					//$htmlMain .= '<br><br>';
    					//$htmlMain .= getReleaseList();
    				} else {
    					$htmlMain .= '<strong>This Version : </strong>'._VERSION_THIS;
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
				$htmlMain .= '<strong>Homepage : </strong>';
				$htmlMain .= '<br>';
				$htmlMain .= '<a href="'._URL_HOME.'" target="_blank">'._URL_HOME.'</a>';
				$htmlMain .= '<br>';
			}
			break;
		case "1": // help
			$htmlTitle = "Help";
			$htmlMain .= '<br><p>';
			$htmlMain .= '<strong>For Help with this Version check Homepage on berliOS :</strong>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="'._URL_HOME.'" target="_blank"><img src="images/arrow.gif" width="9" height="9" title="Homepage on berliOS" border="0"> '._URL_HOME.'</a>';
			$htmlMain .= '<br><br>';
			break;
		case "2": // changelog
			$htmlTitle = "Changelog";
			/*
			$htmlMain .= '<br>';
			$htmlMain .= '<h4>Changelog<h4>';
			$htmlMain .= '<hr>';
			$htmlMain .= '<pre>';
			$htmlMain .= @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=1"));
			$htmlMain .= '</pre>';
			*/
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=1"));
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;
		case "3": // issues
			$htmlTitle = "Issues";
			$issueText = "Error getting issues";
			$issueText = @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=2"));
			header("Content-Type: text/plain");
			echo $issueText;
			exit;
			break;
		case "4": // update
			$htmlTitle = "Update";
			// version-check
			$versionAvailable = trim(getDataFromUrl(_SUPERADMIN_URLBASE._SUPERADMIN_PROXY));
			if ((isset($versionAvailable)) && ($versionAvailable != "")) {
				// set image
				if ($versionAvailable == _VERSION_THIS)
					$statusImage = "green.gif";
				else
					$statusImage = "red.gif";
				// version-text
				$htmlMain .= '<br>';
				if ($versionAvailable != _VERSION_THIS) {
					$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
					$htmlMain .= '<input type="Hidden" name="u" value="0">';
					$htmlMain .= '<input type="submit" value="Update to Version '.$versionAvailable.'">';
					$htmlMain .= '</form><p>';
				}
			}
			$htmlMain .= '<strong>Homepage : </strong>';
			$htmlMain .= '<br>';
			$htmlMain .= '<a href="'._URL_HOME.'" target="_blank"><img src="images/arrow.gif" width="9" height="9" title="Homepage on berliOS" border="0"> '._URL_HOME.'</a>';
			//$htmlMain .= '<br><br>';
			//$htmlMain .= getReleaseList();
			break;
		case "5": // news
			$htmlTitle = "News";
			/*
			$htmlMain .= '<br>';
			$htmlMain .= '<h4>News<h4>';
			$htmlMain .= '<hr>';
			$htmlMain .= @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=0"));
			*/
			$htmlMain .= '<br>';
			$htmlMain .= @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=0"));
			$htmlMain .= '<br><br>';
			break;
		default:
			$htmlTitle = "SuperAdmin";
			$statusImage = "black.gif";
			$htmlMain = '<br>';
			break;
	}
}

/**
 * echo a string. use echo or sendLine
 *
 * @param $string : string to echo
 * @param $mode : 0 = echo | 1 = sendLine
 */
function doEcho($string, $mode = 0) {
	switch ($mode) {
		case 0:
			echo $string;
			return;
		case 1:
			sendLine($string);
			return;
	}
}

/**
 * prints the page
 *
 */
function printPage() {
	printPageStart(0);
	global $htmlMain;
	echo $htmlMain;
	printPageEnd(0);
}

/**
 * prints the page-start
 *
 */
function printPageStart($echoMode = 0) {
	global $cfg, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	doEcho('<HTML>',$echoMode);
	doEcho('<HEAD>',$echoMode);
	doEcho('<TITLE>TorrentFlux - SuperAdmin</TITLE>',$echoMode);
	doEcho('<link rel="icon" href="images/favicon.ico" type="image/x-icon" />',$echoMode);
	doEcho('<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />',$echoMode);
	doEcho('<LINK REL="StyleSheet" HREF="themes/'.$cfg["theme"].'/style.css" TYPE="text/css">',$echoMode);
	doEcho('<META HTTP-EQUIV="Pragma" CONTENT="no-cache; charset='. _CHARSET .'">',$echoMode);
	doEcho('</HEAD>',$echoMode);
	doEcho('<BODY topmargin="8" leftmargin="5" bgcolor="'.$cfg["main_bgcolor"].'">',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table border="0" cellpadding="0" cellspacing="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<table border="1" bordercolor="'.$cfg["table_border_dk"].'" cellpadding="4" cellspacing="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td bgcolor="'.$cfg["main_bgcolor"].'" background="themes/'.$cfg["theme"].'/images/bar.gif">',$echoMode);
	doEcho('<table width="100%" cellpadding="0" cellspacing="0" border="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td align="left"><font class="title">'.$cfg["pagetitle"]." - ".$htmlTitle.'</font></td>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td bgcolor="'.$cfg["table_header_bg"].'">',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table width="100%" bgcolor="'.$cfg["body_data_bg"].'">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table width="100%" cellpadding="0" cellspacing="0" border="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td align="left">',$echoMode);
	doEcho($htmlTop,$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('<td align="right" width="16">',$echoMode);
	if ($statusImage != "yellow.gif")
		doEcho('<a href="' . _FILE_THIS . '">',$echoMode);
	doEcho('<img src="images/'.$statusImage.'" width="16" height="16" border="0" title="'.$statusMessage.'">',$echoMode);
	if ($statusImage != "yellow.gif")
		doEcho('</a>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('<table bgcolor="'.$cfg["table_header_bg"].'" width="750" cellpadding="1">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<div align="left">',$echoMode);
	doEcho('<table border="0" cellpadding="2" cellspacing="2" width="100%">',$echoMode);
}

/**
 * prints the page-end
 *
 */
function printPageEnd($echoMode = 0) {
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</BODY>',$echoMode);
	doEcho('</HTML>',$echoMode);
}

/**
 * bails out cause of version-errors.
 *
 */
function updateErrorNice($message = "") {
	global $statusImage, $statusMessage, $htmlTop, $htmlMain;
	$htmlTop = "<strong>Update</strong>";
	$htmlMain = '<br><font color="red"><strong>Update from your Version not possible.</strong></font>';
	$htmlMain .= '<br><br>';
	$htmlMain .= 'Please use the most recent tarball and perform a manual update.';
	$htmlMain .= '<br>';
	if ((isset($message)) && ($message != "") && (trim($message) != "0"))
		$htmlMain .= '<br><pre>'.$message.'</pre>';
	//$htmlMain .= '<br><br>';
	//$htmlMain .= getReleaseList();
	$statusImage = "red.gif";
	printPage();
	exit;
}

/**
 * bails out cause of version-errors.
 *
 */
function updateError($message = "") {
	$errorString = "ERROR processing auto-update. please do manual update.";
	if ((isset($message)) && ($message != ""))
		$errorString .= "\n".$message;
	header("Content-Type: text/plain");
	echo $errorString;
	exit;
}

/**
 * sendLine - sends a line to the browser
 *
 */
function sendLine($line = "") {
	echo $line;
	echo str_pad('',4096)."\n";
	ob_flush();
	flush();
}

/**
 * load data of file
 *
 * @param $file the file
 * @return data
 */
function getDataFromFile($file) {
	if($fileHandle = @fopen($file,'r')) {
		$data = null;
		while (!@feof($fileHandle))
			$data .= @fgets($fileHandle, 8192);
		@fclose ($fileHandle);
		return $data;
	}
}

/**
 * get data of a url
 *
 * @param $url the url
 * @return data
 */
function getDataFromUrl($url) {
	ini_set("allow_url_fopen", "1");
	ini_set("user_agent", "TorrentFlux/". _VERSION_THIS);
	if($fileHandle = @fopen($url,'r')) {
		$data = null;
		while (!@feof($fileHandle))
			$data .= @fgets($fileHandle, 4096);
		@fclose ($fileHandle);
		return $data;
	}
}

/**
 * get a ado-connection to our database.
 *
 * @return database-connection or false on error
 */
function getAdoConnection() {
	global $cfg;
	// create ado-object
    $db = &ADONewConnection($cfg["db_type"]);
    // connect
    @ $db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
    // check for error
    if ($db->ErrorNo() != 0)
    	return false;
    // return db-connection
	return $db;
}

/**
 * get release-list
 *
 * @return release-list as html-snip
 */
function getReleaseList() {
	global $cfg, $error;
	$retVal = "";
	$releaseList = gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=3"));
	if ((isset($releaseList)) && ($releaseList != "")) {
		$retVal .= '<strong>Available Tarballs : </strong>';
		$retVal .= '<br>';
		$retVal .= '<table cellpadding="2" cellspacing="1" border="1" bordercolor="'.$cfg["table_border_dk"].'" bgcolor="'.$cfg["body_data_bg"].'">';
		$retVal .= '<tr>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'">&nbsp;</td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Version</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Checksum</strong></td>';
		$retVal .= '</tr>';
		$releaseListFiles = explode("\n",$releaseList);
		foreach ($releaseListFiles as $release) {
			$release = trim($release);
			if ((isset($release)) && ($release != "")) {
				$tempArray = explode("_", $release);
				$tempString = array_pop($tempArray);
				$releaseVersion = substr(($tempString), 0, -8);
				$retVal .= '<tr>';
				$retVal .= '<td align="center">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'">';
				$retVal .= '<img src="images/download_owner.gif" title="Download '.$releaseVersion.'" border="0">';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '<td align="right">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'">';
				$retVal .= $releaseVersion;
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '<td align="right">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'.md5">';
				$retVal .= 'md5';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '</tr>';
			}
		}
		$retVal .= '</table>';
	}
	return $retVal;
}

/**
 * formats a timestamp-string to human readable format.
 *
 * @param $timestampString string with prop. timestamp
 * @return string with human-readable date
 */
function formatHumanDate($timestampString) {
	return gmstrftime("%b %d %Y %H:%M:%S", mktime(
		(int) substr($timestampString, 8, 2),
		(int) substr($timestampString, 10, 2),
		(int) substr($timestampString, 12, 2),
		(int) substr($timestampString, 4, 2),
		(int) substr($timestampString, 6, 2),
		(int) substr($timestampString, 0, 4)
		));
}

/**
 * formats a size-string to human readable format.
 *
 * @param $sizeInByte number with bytes
 * @return string with human-readable size
 */
function formatHumanSize($sizeInByte) {
	if ($sizeInByte > (1073741824)) // > 1G
	  return (string) (round($sizeInByte/(1073741824), 1))."G";
	if ($sizeInByte > (1048576)) // > 1M
	  return (string) (round($sizeInByte/(1048576), 1))."M";
	if ($sizeInByte > (1024)) // > 1k
	  return (string) (round($sizeInByte/(1024), 1))."k";
	return (string) $sizeInByte;
}

/**
 * checks if backup-id is a valid backup-archive
 *
 * @param $param the param with the backup-id
 * @param boolean if archive-name is a valid backup-archive
 */
function backupParamCheck($param) {
	global $cfg, $error;
	// sanity-checks
	if( preg_match("/\\\/", urldecode($param)) )
		return false;
	if( preg_match("/\.\./", urldecode($param)) )
		return false;
	// check id
	$fileList = backupList();
	if ((isset($fileList)) && ($fileList != "")) {
		$validFiles = explode("\n",$fileList);
		if (in_array($param, $validFiles))
			return true;
		else
			return false;
	} else {
		return false;
	}
	return false;
}

/**
 * build backup-list
 *
 * @return backup-list as string
 */
function backupListDisplay() {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP . '/';
	//
	$retVal = "";
	$fileList = backupList();
	if ((isset($fileList)) && ($fileList != "")) {
		$retVal .= '<strong>Backups on Server :</strong>';
		$retVal .= '<br><br>';
		$retVal .= '<table cellpadding="2" cellspacing="1" border="1" bordercolor="'.$cfg["table_border_dk"].'" bgcolor="'.$cfg["body_data_bg"].'">';
		$retVal .= '<tr>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Version</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Date</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Comp.</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Size</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'">&nbsp;</td>';
		$retVal .= '</tr>';
		$backupListFiles = explode("\n",$fileList);
		foreach ($backupListFiles as $backup) {
			$backup = trim($backup);
			$backupFile = $dirBackup.$backup;
			if ((isset($backup)) && ($backup != "") && (is_file($backupFile))) {
				$backupElements = explode("_",$backup);
				$retVal .= '<tr>';
				$retVal .= '<td align="center">'.$backupElements[1].'</td>';
				$retVal .= '<td align="right">'.formatHumanDate(substr($backupElements[2], 0, 14)).'</td>';
				$lastChar = substr($backupElements[2], -1, 1);
				$retVal .= '<td align="center">';
				switch ($lastChar) {
					case "r":
						$retVal .= 'none';
					break;
					case "z":
						$retVal .= 'gzip';
					break;
					case "2":
						$retVal .= 'bzip2';
					break;
					default:
						$retVal .= 'unknown';
					break;
				}
				$retVal .= '</td>';
				$retVal .= '<td align="right">'.(string)(formatHumanSize(filesize($backupFile))).'</td>';
				$retVal .= '<td align="center">';
				$retVal .= '<a href="'. _FILE_THIS .'?b=4&f='.$backup.'">';
				$retVal .= '<img src="images/download_owner.gif" title="Download" border="0">';
				$retVal .= '</a>';
				$retVal .= '&nbsp;&nbsp;';
				$retVal .= '<a href="'. _FILE_THIS .'?b=5&f='.$backup.'">';
				$retVal .= '<img src="images/delete.png" title="Delete" border="0">';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '</tr>';
			}
		}
		$retVal .= '</table>';
	} else {
		$retVal .= '<strong>No Backups on Server</strong>';
	}
	return $retVal;
}

/**
 * get backup-list
 *
 * @return backup-list as string or empty string on error / no files
 */
function backupList() {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP;
	if (file_exists($dirBackup)) {
		if ($dirHandle = opendir($dirBackup)) {
			$fileList = "";
			while (false !== ($file = readdir($dirHandle))) {
				if ((substr($file, 0, 1)) != ".")
					$fileList .= $file . "\n";
			}
			closedir($dirHandle);
			return $fileList;
		} else {
			return "";
		}
	} else {
		return "";
	}
}

/**
 * deletes a backup of a flux-installation
 *
 * @param $filename the file with the backup
 */
function backupDelete($filename) {
	global $cfg;
	$backupFile = $cfg["path"]. _DIR_BACKUP . '/' . $filename;
	@unlink($backupFile);
	AuditAction($cfg["constants"]["admin"], "FluxBackup Deleted : ".$filename);
}

/**
 * sends a backup of flux-installation to a client
 *
 * @param $filename the file with the backup
 * @param $delete boolean if file should be deleted.
 */
function backupSend($filename, $delete = false) {
	global $cfg;
	$backupFile = $cfg["path"]. _DIR_BACKUP . '/' . $filename;
	if ($delete) {
		session_write_close();
		ob_end_clean();
		if (connection_status() != 0)
			return false;
		set_time_limit(0);
	}
	if (! is_file($backupFile))
		return false;
	// log before we screw up the file-name
	AuditAction($cfg["constants"]["admin"], "FluxBackup Sent : ".$filename);
	// filenames in IE containing dots will screw up the filename
	if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
		$filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
	// send data
	header("Cache-Control: ");
	header("Pragma: ");
	header("Content-Type: application/octet-stream");
	header("Content-Length: " .(string)(filesize($backupFile)) );
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header("Content-Transfer-Encoding: binary\n");
	if ($delete) { // read data to mem, delete file and send complete
		$data = getDataFromFile($backupFile);
		@unlink($backupFile);
		echo $data;
	} else { // read / write file with 8kb-buffer
		if($handle = fopen($backupFile, 'rb')){
			while( (! feof($handle)) && (connection_status() == 0) ){
				print(fread($handle, 8192));
				flush();
			}
			fclose($handle);
		}
	}
	// return
	if ($delete) {
		return true;
	} else {
		return((connection_status()==0) and !connection_aborted());
	}
}

/**
 * backup of flux-installation
 *
 * @param $talk : boolean if function should talk
 * @param $compression : 0 = none | 1 = gzip | 2 = bzip2
 * @return string with name of backup-archive, string with "" in error-case.
 */
function backupCreate($talk = false, $compression = 0) {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP;
	if (! checkDirectory($dirBackup)) {
		$error = "Errors when checking/creating backup-dir : ".$dirBackup;
		return "";
	}
	// files and more strings
	$backupName = "backup_". _VERSION_THIS ."_".date("YmdHis");
	$fileArchiveName = $backupName.".tar";
	$tarSwitch = "-cf";
	switch ($compression) {
		case 1:
			$fileArchiveName .= ".gz";
			$tarSwitch = "-zcf";
			break;
		case 2:
			$fileArchiveName .= ".bz2";
			$tarSwitch = "-jcf";
			break;
	}
	$fileArchive = $dirBackup . '/' . $fileArchiveName;
	$fileDatabase = $dirBackup . '/database.sql';
	$fileDocroot = $dirBackup . '/docroot.tar';
	// command-strings
	$commandArchive = "cd ".$dirBackup."; tar ".$tarSwitch." ".$fileArchiveName." ";
	$commandDatabase = "";
	switch($cfg["db_type"]) {
		case "mysql":
			$commandDatabase = "mysqldump -h ".$cfg["db_host"]." -u ".$cfg["db_user"]." --password=".$cfg["db_pass"]." --all -f ".$cfg["db_name"]." > ".$fileDatabase;
			$commandArchive .= 'database.sql ';
			break;
		case "sqlite":
			$commandDatabase = "sqlite ".$cfg["db_host"]." .dump > ".$fileDatabase;
			$commandArchive .= 'database.sql ';
			break;
		case "postgres":
			$commandDatabase = "pg_dump -h ".$cfg["db_host"]." -D ".$cfg["db_name"]." -U ".$cfg["db_user"]." -f ".$fileDatabase;
			$commandArchive .= 'database.sql ';
			break;
	}
	$commandArchive .= 'docroot.tar';
	//$commandDocroot = "cd ".$dirBackup."; tar -cf docroot.tar ".$_SERVER['DOCUMENT_ROOT']; // with path of docroot
	$commandDocroot = "cd ".escapeshellarg($_SERVER['DOCUMENT_ROOT'])."; tar -cf ".$fileDocroot." ."; // only content of docroot
	// database-command
	if ($commandDatabase != "") {
		if ($talk)
			sendLine('Backup of Database <em>'.$cfg["db_name"].'</em> ...');
		shell_exec($commandDatabase);
	}
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// docroot-command
	if ($talk)
		sendLine('Backup of Docroot <em>'.$_SERVER['DOCUMENT_ROOT'].'</em> ...');
	shell_exec($commandDocroot);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// create the archive
	if ($talk)
		sendLine('Creating Archive <em>'.$fileArchiveName.'</em> ...');
	shell_exec($commandArchive);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// delete temp-file(s)
	if ($talk)
		sendLine('Deleting temp-files ...');
	if ($commandDatabase != "")
		@unlink($fileDatabase);
	@unlink($fileDocroot);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// log
	if ($talk)
		sendLine('<font color="green">Backup Complete.</font><br>');
	AuditAction($cfg["constants"]["admin"], "FluxBackup Created : ".$fileArchiveName);
	return $fileArchiveName;
}

?>