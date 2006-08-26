<?php
/* $Id$ */

$output .= '<div align="center" class="admincell">';

// torrentdetails
$torrentDetails = _TRANSFERDETAILS;
$output .= "<a href=\"index.php?iid=details&torrent=".urlencode($entry);
if($transferRunning == 1)
	$output .= "&als=false";
$output .= "\">";
$output .= "<img src=\"images/properties.png\" width=\"18\" height=\"13\" title=\"".$torrentDetails."\" border=\"0\">";

// link to datapath
$output .= '<a href="index.php?iid=dir&dir='.urlencode(str_replace($cfg["path"],'', $settingsAry['savepath']).$settingsAry['datapath']).'">';
$output .= '<img src="images/datadir.gif" title="'.$settingsAry['datapath'].'" border="0">';
$output .= '</a>';

if ($owner || IsAdmin($cfg["user"])) {
	if($percentDone >= 0 && $transferRunning == 1) {
		if ($isTorrent) {
			$output .= "<a href=\"index.php?iid=index&alias_file=".$alias."&kill=".$kill_id."&kill_torrent=".urlencode($entry)."\"><img src=\"images/kill.gif\" width=\"16\" height=\"16\" border=\"0\" title=\""._STOPTRANSFER."\"></a>";
			$output .= "<img src=\"images/delete_off.gif\" width=16 height=16 border=0>";
			if ($cfg['enable_multiops'] != 0)
				$output .= "<input type=\"checkbox\" name=\"transfer[]\" value=\"".urlencode($entry)."\">";
		} else {
			$output .= "<img src=\"images/run_off.gif\" width=\"16\" height=\"16\" border=\"0\" title=\"-\">";
			$output .= "<img src=\"images/delete_off.gif\" width=16 height=16 border=0>";
			$output .= "<input type=\"checkbox\" disabled=\"disabled\">";
		}
	} else {
		if($transferowner == "n/a") {
			$output .= "<img src=\"images/run_off.gif\" width=\"16\" height=\"16\" border=\"0\" title=\""._NOTOWNER."\">";
		} else {
			if ($transferRunning == 3) {
				$output .= "<a href=\"index.php?iid=index&alias_file=".$alias."&dQueue=".$kill_id."&QEntry=".urlencode($entry)."\"><img src=\"images/queued.gif\" width=\"16\" height=\"16\" border=\"0\" title=\""._DELQUEUE."\"></a>";
			} else {
				if (!is_file($cfg["torrent_file_path"].$alias.".pid")) {
					if ($isTorrent) {
						// Allow Avanced start popup?
						if ($cfg["advanced_start"] != 0) {
							// Avanced start popup
							$output .= "<a href=\"#\" onclick=\"StartTorrent('index.php?iid=startpop&torrent=".urlencode($entry)."')\">";
							if ($show_run)
								$output .= "<img src=\"images/run_on.gif\" width=\"16\" height=\"16\" title=\""._RUNTRANSFER."\" border=\"0\">";
							else
								$output .= "<img src=\"images/seed_on.gif\" width=\"16\" height=\"16\" title=\""._SEEDTRANSFER."\" border=\"0\">";
							$output .= "</a>";
						} else {
							// Quick Start
							$output .= "<a href=\"".$_SERVER['PHP_SELF']."?torrent=".urlencode($entry)."\">";
							if ($show_run)
								$output .= "<img src=\"images/run_on.gif\" width=\"16\" height=\"16\" title=\""._RUNTRANSFER."\" border=\"0\">";
							else
								$output .= "<img src=\"images/seed_on.gif\" width=\"16\" height=\"16\" title=\""._SEEDTRANSFER."\" border=\"0\">";
							$output .= "</a>";
						}
					} else {
						if ($show_run) {
							$output .= "<a href=\"".$_SERVER['PHP_SELF']."?torrent=".urlencode($entry)."\">";
							$output .= "<img src=\"images/run_on.gif\" width=\"16\" height=\"16\" title=\""._RUNTRANSFER."\" border=\"0\">";
							$output .= "</a>";
						} else {
							$output .= "<img src=\"images/run_off.gif\" width=\"16\" height=\"16\" border=\"0\" title=\"Done\">";
						}
					}
				} else {
					// pid file exists so this may still be running or dieing.
					$output .= "<img src=\"images/run_off.gif\" width=\"16\" height=\"16\" border=\"0\" title=\""._STOPPING."\">";
				}
			}
		}
		if (!is_file($cfg["torrent_file_path"].$alias.".pid")) {
			$deletelink = $_SERVER['PHP_SELF']."?alias_file=".$alias."&delfile=".urlencode($entry);
			$output .= "<a href=\"".$deletelink."\" onclick=\"return ConfirmDelete('".$entry."')\"><img src=\"images/delete_on.gif\" width=16 height=16 title=\""._DELETE."\" border=0></a>";
		} else {
			// pid file present so process may be still running. don't allow deletion.
			$output .= "<img src=\"images/delete_off.gif\" width=\"16\" height=\"16\" title=\""._STOPPING."\" border=0>";
		}
		if ($cfg['enable_multiops'] == 1)
			$output .= "<input type=\"checkbox\" name=\"transfer[]\" value=\"".urlencode($entry)."\">";
	}
} else {
	$output .= "<img src=\"images/locked.gif\" width=\"16\" height=\"16\" border=\"0\" title=\""._NOTOWNER."\">";
	$output .= "<img src=\"images/locked.gif\" width=\"16\" height=\"16\" border=\"0\" title=\""._NOTOWNER."\">";
	$output .= "<input type=\"checkbox\" disabled=\"disabled\">";
}

$output .= '</div>';

?>