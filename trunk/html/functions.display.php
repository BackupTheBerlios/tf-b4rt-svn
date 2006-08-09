<?php

/* $Id: functions.display.php 188 2006-08-06 19:17:07Z msn_exploder $ */

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

//XFER:****************************************************
//XFER: displayXferBar(max_bytes, used_bytes, title)
//XFER: displays xfer percentage bar
function displayXferBar($total, $used, $title) {
	global $cfg;
	$remaining = max(0,$total-$used/(1024*1024));
	$percent = round($remaining/$total*100,0);
	$text = ' ('.formatFreeSpace($remaining).') '._REMAINING;
	$bgcolor = '#';
	$bgcolor .= str_pad(dechex(255-255*($percent/150)),2,0,STR_PAD_LEFT);
	$bgcolor .= str_pad(dechex(255*($percent/150)),2,0,STR_PAD_LEFT);
	$bgcolor .='00';
	$displayXferBar = '<tr>';
	$displayXferBar .= '<td width="2%" nowrap align="right"><div class="tiny">'.$title.'</div></td>';
	$displayXferBar .= '<td width="92%">';
	$displayXferBar .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top:1px;margin-bottom:1px;"><tr>';
	$displayXferBar .= '<td bgcolor="'.$bgcolor.'" width="'.($percent+1).'%">';
	if ($percent >= 50) {
		$displayXferBar .= '<div class="tinypercent" align="center"';
		if ($percent == 100) {
			$displayXferBar .= ' style="background:#ffffff;">';
		}
		$displayXferBar .=
		$displayXferBar .= '>';
		$displayXferBar .= $percent.'%'.$text;
		$displayXferBar .= '</div>';
	}
	$displayXferBar .= '</td>';
	$displayXferBar .= '<td bgcolor="#000000" width="'.(100-$percent).'%" height="100%">';
	if ($percent < 50) {
		$displayXferBar .= '<div class="tinypercent" align="center" style="color:'.$bgcolor;
		if ($percent == 0) {
			$displayXferBar .= '; background:#ffffff;">';
		}
		else {
			$displayXferBar .= ';">';
		}
		$displayXferBar .= $percent.'%'.$text;
		$displayXferBar .= '</div>';
	}
	$displayXferBar .= '</td>';
	$displayXferBar .= '</tr></table>';
	$displayXferBar .= '</td>';
	$displayXferBar .= '</tr>';
	return $displayXferBar;
}

//XFER:****************************************************
//XFER: displayXfer()
//XFER: displays xfer usage page
function displayXfer() {
	global $cfg;
	$displayXferList = displayXferList();
	if (isset($_GET['user'])) {
		$displayXferList .= '<br><b>';
		$displayXferList .= ($_GET['user'] == '%') ? _SERVERXFERSTATS : _USERDETAILS.': '.$_GET['user'];
		$displayXferList .= '</b><br>';
		displayXferDetail($_GET['user'],_MONTHSTARTING,0,0);
		if (isset($_GET['month'])) {
			$mstart = $_GET['month'].'-'.$cfg['month_start'];
			$mend = date('Y-m-d',strtotime('+1 Month',strtotime($mstart)));
		}
		else {
			$mstart = 0;
			$mend = 0;
		}
		if (isset($_GET['week'])) {
			$wstart = $_GET['week'];
			$wend = date('Y-m-d',strtotime('+1 Week',strtotime($_GET['week'])));
		}
		else {
			$wstart = $mstart;
			$wend = $mend;
		}
		$displayXferList .= displayXferDetail($_GET['user'],_WEEKSTARTING,$mstart,$mend);
		$displayXferList .= displayXferDetail($_GET['user'],_DAY,$wstart,$wend);
	}
	return $displayXferList;
}

//XFER:****************************************************
//XFER: displayXferDetail(user, period_title, start_timestamp, end_timestamp)
//XFER: display table of month/week/day's usage for user
function displayXferDetail($user_id,$period,$period_start,$period_end)
{
	global $cfg, $xfer, $xfer_total, $db;
	$period_query = ($period_start) ? 'and date >= "'.$period_start.'" and date < "'.$period_end.'"' : '';
	$sql = 'SELECT SUM(download) AS download, SUM(upload) AS upload, date FROM tf_xfer WHERE user LIKE "'.$user_id.'" '.$period_query.' GROUP BY date ORDER BY date';
	$rtnValue = $db->GetAll($sql);
	showError($db,$sql);
	$displayXferDetail = "<table width='760' border=1 bordercolor='$cfg[table_admin_border]' cellpadding='2' cellspacing='0' bgcolor='$cfg[table_data_bg]'>";
	$displayXferDetail .= '<tr>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='20%'><div align=center class='title'>$period</div></td>";
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._TOTAL.'</div></td>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._DOWNLOAD.'</div></td>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._UPLOAD.'</div></td>';
	$displayXferDetail .= '</tr>';
	$start = '';
	$download = 0;
	$upload = 0;
	foreach ($rtnValue as $row) {
		$rtime = strtotime($row[2]);
		switch ($period) {
			case 'Month Starting':
				$newstart = $cfg['month_start'].' ';
				$newstart .= (date('j',$rtime) < $cfg['month_start']) ? date('M Y',strtotime('-1 Month',$rtime)) : date('M Y',$rtime);
			break;
			case 'Week Starting':
				$newstart = date('d M Y',strtotime('+1 Day last '.$cfg['week_start'],$rtime));
			break;
			case 'Day':
				$newstart = $row[2];
			break;
		}
		if ($row[2] == date('Y-m-d')) {
			if ($user_id == '%') {
				$row[0] = $xfer_total['day']['download'];
				$row[1] = $xfer_total['day']['upload'];
			}
			else {
				$row[0] = $xfer[$user_id]['day']['download'];
				$row[1] = $xfer[$user_id]['day']['upload'];
			}
		}
		if ($start != $newstart) {
			if ($upload + $download != 0) {
				$displayXferDetail .= '<tr>';
					$displayXferDetail .= "<td>$rowstr</td>";
					$downloadstr = formatFreeSpace($download/(1024*1024));
					$uploadstr = formatFreeSpace($upload/(1024*1024));
					$totalstr = formatFreeSpace(($download+$upload)/(1024*1024));
					$displayXferDetail .= "<td><div class='tiny' align='center'><b>$totalstr</b></div></td>";
					$displayXferDetail .= "<td><div class='tiny' align='center'>$downloadstr</div></td>";
					$displayXferDetail .= "<td><div class='tiny' align='center'>$uploadstr</div></td>";
				$displayXferDetail .= '</tr>';
			}
			$download = $row[0];
			$upload = $row[1];
			$start = $newstart;
		}
		else {
			$download += $row[0];
			$upload += $row[1];
		}
		switch ($period) {
			case 'Month Starting':
				$rowstr = "<a href='?op=xfer&user=$user_id&month=".date('Y-m',strtotime($start))."'>$start</a>";
			break;
			case 'Week Starting':
				$rowstr = "<a href='?op=xfer&user=$user_id&month=". @ $_GET[month] . "&week=".date('Y-m-d',strtotime($start))."'>$start</a>";
			break;
			case 'Day':
				$rowstr = $start;
			break;
		}
	}
	if ($upload + $download != 0) {
		$displayXferDetail .= '<tr>';
		$displayXferDetail .= "<td>$rowstr</td>";
		$downloadstr = formatFreeSpace($download/(1024*1024));
		$uploadstr = formatFreeSpace($upload/(1024*1024));
		$totalstr = formatFreeSpace(($download+$upload)/(1024*1024));
		$displayXferDetail .= "<td><div class='tiny' align='center'><b>$totalstr</b></div></td>";
		$displayXferDetail .= "<td><div class='tiny' align='center'>$downloadstr</div></td>";
		$displayXferDetail .= "<td><div class='tiny' align='center'>$uploadstr</div></td>";
		$displayXferDetail .= '</tr>';
	}
	$displayXferDetail .= '</table><br>';
	return $displayXferDetail;
}

//XFER:****************************************************
//XFER: dixpayXferList()
//XFER: show top summary table of xfer usage page
function displayXferList() {
	global $cfg, $xfer, $xfer_total, $db;
$displayXferList = "<table width='760' border=1 bordercolor='$cfg[table_admin_border]' cellpadding='2' cellspacing='0' bgcolor='$cfg[table_data_bg]'>";
	$displayXferList .= '<tr>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='15%'><div align=center class='title'>"._USER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._TOTALXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._MONTHXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._WEEKXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._DAYXFER.'</div></td>';
	$displayXferList .= '</tr>';
	$sql = 'SELECT user_id FROM tf_users ORDER BY user_id';
	$rtnValue = $db->GetCol($sql);
	showError($db,$sql);
	foreach ($rtnValue as $user_id) {
		$displayXferList .= '<tr>';
		$displayXferList .= '<td><a href="?op=xfer&user='.$user_id.'">'.$user_id.'</a></td>';
		$total = formatFreeSpace($xfer[$user_id]['total']['total']/(1024*1024));
		$month = formatFreeSpace(@ $xfer[$user_id]['month']['total']/(1024*1024));
		$week = formatFreeSpace(@ $xfer[$user_id]['week']['total']/(1024*1024));
		$day = formatFreeSpace(@ $xfer[$user_id]['day']['total']/(1024*1024));
		$displayXferList .= '<td><div class="tiny" align="center">'.$total.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$month.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$week.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$day.'</div></td>';
		$displayXferList .= '</tr>';
	}
	$displayXferList .= '<td><a href="?op=xfer&user=%"><b>'._TOTAL.'</b></a></td>';
	$total = formatFreeSpace($xfer_total['total']['total']/(1024*1024));
	$month = formatFreeSpace($xfer_total['month']['total']/(1024*1024));
	$week = formatFreeSpace($xfer_total['week']['total']/(1024*1024));
	$day = formatFreeSpace($xfer_total['day']['total']/(1024*1024));
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$total.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$month.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$week.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$day.'</b></div></td>';
	$displayXferList .= '</table>';
	return $displayXferList;
}

// get the header portion of admin views
function getHead($subTopic, $showButtons=true, $refresh="", $percentdone="") {
	global $cfg;
	$head = '
	<html>
	<HEAD>
		<TITLE>'.$percentdone.' '.$cfg["pagetitle"].'</TITLE>
		<link rel="icon" href="images/favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
		<LINK REL="StyleSheet" HREF="themes/'.$cfg["theme"].'/style.css" TYPE="text/css">
		<META HTTP-EQUIV="Pragma" CONTENT="no-cache; charset=ISO-8859-1">
	';
	if ($refresh != "") {
		$head .= "<meta http-equiv=\"REFRESH\" content=\"".$refresh."\">";
	}
	$head .= '
		<script type="text/javascript">
		function CheckSFV(dir,file) {
			var width = screen.width/2-300;
			var height = screen.height/2-110;
			var InfoWin = window.open("index.php?page=checkSFV&dir="+dir+"&file="+file, "CheckSFV", "status=no,toolbar=no,scrollbars=yes,resizable=yes,menubar=no,width=560,height=240,left="+width+",top="+height);
		}
		</script>
	</HEAD>
	<body topmargin="8" leftmargin="5" bgcolor="'.$cfg["main_bgcolor"].'">
	<div align="center">
	<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
	<table border="1" bordercolor="'.$cfg["table_border_dk"].'" cellpadding="4" cellspacing="0">
	<tr>
		<td bgcolor="'.$cfg["main_bgcolor"].'" background="themes/'.$cfg["theme"].'/images/bar.gif">
		'.getTitleBar($cfg["pagetitle"].' - '.$subTopic, $showButtons).'
		</td>
	</tr>
	<tr>
	<td bgcolor="'.$cfg["table_header_bg"].'">
	<div align="center">
	<table width="100%" bgcolor="'.$cfg["body_data_bg"].'">
	<tr><td>
	';
	return $head;
}

// ***************************************************************************
// ***************************************************************************
// get the footer portion
function getFoot($showReturn=true, $showVersionLink = false) {
	global $cfg;
	$foot = "</td></tr>";
	$foot .= "</table>";
	if ($showReturn)
		$foot .= "[<a href=\"index.php?page=index\">"._RETURNTOTORRENTS."</a>]";
	$foot .= "</div>";
	$foot .= "</td>";
	$foot .= "</tr>";
	$foot .= "</table>";
	$foot .=  getTorrentFluxLink($showVersionLink);
		$foot .= "</td>
	</tr>
	</table>
	</div>
	</body>
	</html>
	";
	return $foot;
}

// ***************************************************************************
// ***************************************************************************
// get TF Link and Version
function getTorrentFluxLink($showVersionLink = false) {
	global $cfg;
	if ($cfg["ui_displayfluxlink"] != 0) {
		$torrentFluxLink = "<div align=\"right\">";
		$torrentFluxLink .= "<a href=\"http://tf-b4rt.berlios.de/\" target=\"_blank\"><font class=\"tinywhite\">torrentflux-b4rt ".$cfg["version"]."</font></a>&nbsp;&nbsp;";
		if ($showVersionLink) {
			$torrentFluxLink .= getSuperAdminLink('?a=0','');
		}
		$torrentFluxLink .= "</div>";
	}
	return $torrentFluxLink;
}

// ***************************************************************************
// ***************************************************************************
// get Title Bar
// 2004-12-09 PFM: now using adodb.
function getTitleBar($pageTitleText, $showButtons=true) {
	global $cfg, $db;
	$titleBar = '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
	$titleBar .= '<tr>';
	$titleBar .= '<td align="left"><font class="title">'.$pageTitleText.'</font></td>';
	if ($showButtons) {
		$titleBar .= "<td align=right>";
		// Top Buttons
		$titleBar .= "&nbsp;&nbsp;";
		$titleBar .=	 "<a href=\"index.php?page=index\"><img src=\"themes/".$cfg["theme"]."/images/home.gif\" width=49 height=13 title=\""._TORRENTS."\" border=0></a>&nbsp;";
		$titleBar .=	 "<a href=\"index.php?page=dir\"><img src=\"themes/".$cfg["theme"]."/images/directory.gif\" width=49 height=13 title=\""._DIRECTORYLIST."\" border=0></a>&nbsp;";
		$titleBar .=	 "<a href=\"index.php?page=history\"><img src=\"themes/".$cfg["theme"]."/images/history.gif\" width=49 height=13 title=\""._UPLOADHISTORY."\" border=0></a>&nbsp;";
		$titleBar .=	 "<a href=\"index.php?page=profile\"><img src=\"themes/".$cfg["theme"]."/images/profile.gif\" width=49 height=13 title=\""._MYPROFILE."\" border=0></a>&nbsp;";
		// Does the user have messages?
		$sql = "select count(*) from tf_messages where to_user='".$cfg['user']."' and IsNew=1";
		$number_messages = $db->GetOne($sql);
		showError($db,$sql);
		if ($number_messages > 0) {
			// We have messages
			$message_image = "themes/".$cfg["theme"]."/images/messages_on.gif";
		} else {
			// No messages
			$message_image = "themes/".$cfg["theme"]."/images/messages_off.gif";
		}
		$titleBar .= "<a href=\"index.php?page=readmsg\"><img src=\"".$message_image."\" width=49 height=13 title=\""._MESSAGES."\" border=0></a>";
		if(IsAdmin()) {
			$titleBar .= "&nbsp;<a href=\"index.php?page=admin\"><img src=\"themes/".$cfg["theme"]."/images/admin.gif\" width=49 height=13 title=\""._ADMINISTRATION."\" border=0></a>";
		}
		$titleBar .= "&nbsp;<a href=\"logout.php\"><img src=\"images/logout.gif\" width=13 height=12 title=\"Logout\" border=0></a>";
	}
	$titleBar .= '</td>';
	$titleBar .= '</tr>';
	$titleBar .= '</table>';
	return $titleBar;
}

// ***************************************************************************
// ***************************************************************************
// get dropdown list to send message to a user
function getMessageList() {
	global $cfg;
	$users = GetUsers();
	$messageList = '<div align="center">'.
	'<table border="0" cellpadding="0" cellspacing="0">'.
	'<form name="formMessage" action="index.php?page=message" method="post">'.
	'<tr><td>' . _SENDMESSAGETO ;
	$messageList .= '<select name="to_user">';
	for($inx = 0; $inx < sizeof($users); $inx++) {
		$messageList .= '<option>'.$users[$inx].'</option>';
	}
	$messageList .= '</select>';
	$messageList .= '<input type="Submit" value="' . _COMPOSE .'">';
	$messageList .= '</td></tr></form></table></div>';
	return $messageList;
}

// ***************************************************************************
// Build Search Engine Drop Down List
function buildSearchEngineDDL($selectedEngine = 'TorrentSpy', $autoSubmit = false) {
	$output = "<select name=\"searchEngine\" ";
	if ($autoSubmit) {
		 $output .= "onchange=\"this.form.submit();\" ";
	}
	$output .= " STYLE=\"width: 125px\">";
	$handle = opendir("./searchEngines");
	while($entry = readdir($handle)) {
		$entrys[] = $entry;
	}
	natcasesort($entrys);
	foreach($entrys as $entry) {
		if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != "." && strpos($entry,"Engine.php")) {
			$tmpEngine = str_replace("Engine",'',substr($entry,0,strpos($entry,".")));
			$output .= "<option";
			if ($selectedEngine == $tmpEngine) {
				$output .= " selected";
			}
			$output .= ">".str_replace("Engine",'',substr($entry,0,strpos($entry,".")))."</option>";
		}
	}
	$output .= "</select>\n";
	return $output;
}

function getEngineLink($searchEngine) {
	$tmpLink = '';
	$engineFile = 'searchEngines/'.$searchEngine.'Engine.php';
	if (is_file($engineFile)) {
		$fp = @fopen($engineFile,'r');
		if ($fp) {
			$tmp = fread($fp, filesize($engineFile));
			@fclose( $fp );
			$tmp = substr($tmp,strpos($tmp,'$this->mainURL'),100);
			$tmp = substr($tmp,strpos($tmp,"=")+1);
			$tmp = substr($tmp,0,strpos($tmp,";"));
			$tmpLink = trim(str_replace(array("'","\""),"",$tmp));
		}
	}
	return $tmpLink;
}

/* ************************************************************************** */

/**
 * get superadmin-popup-link-html-snip.
 *
 */
function getSuperAdminLink($param = "", $linkText = "") {
	global $cfg;
	$superAdminLink = '
	<script language="JavaScript">
	function SuperAdmin(name_file) {
			window.open (name_file,"_blank","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width='.$cfg["ui_dim_superadmin_w"].',height='.$cfg["ui_dim_superadmin_h"].'")
	}
	</script>';
	$superAdminLink .= "<a href=\"JavaScript:SuperAdmin('superadmin.php".$param."')\">";
	if ((isset($linkText)) && ($linkText != ""))
		$superAdminLink .= $linkText;
	else
		$superAdminLink .= '<img src="images/arrow.gif" width="9" height="9" title="Version" border="0">';
	$superAdminLink .= '</a>';
	return $superAdminLink;
}

/**
 * get form of index-page-selection
 *
 */
function getIndexPageSelectForm() {
	global $cfg;
	$retVal = '<select name="index_page">';
	$retVal .= '<option value="tf"';
	if ($cfg["index_page"] == "tf")
		$retVal .= " selected";
	$retVal .= '>tf</option>';
	$retVal .= '<option value="b4rt"';
	if ($cfg["index_page"] == "b4rt")
		$retVal .= " selected";
	$retVal .= '>b4rt</option>';
	$retVal .= '</select>';
	return $retVal;
}

function getBTClientSelect($btclient = 'tornado') {
	global $cfg;
	$getBTClientSelect = '<select name="btclient">';
	$getBTClientSelect .= '<option value="tornado"';
	if ($btclient == "tornado")
		$getBTClientSelect .= " selected";
	$getBTClientSelect .= '>tornado</option>';
	$getBTClientSelect .= '<option value="transmission"';
	if ($btclient == "transmission")
		$getBTClientSelect .= " selected";
	$getBTClientSelect .= '>transmission</option>';
	$getBTClientSelect .= '</select>';
	return $getBTClientSelect;
}

/**
 * get form of sort-order-settings
 *
 */
function getSortOrderSettingsForm() {
	global $cfg;
	$sortOrderSettingsForm = '<select name="index_page_sortorder">';
	$sortOrderSettingsForm .= '<option value="da"';
	if ($cfg['index_page_sortorder'] == "da")
		$sortOrderSettingsForm .= " selected";
	$sortOrderSettingsForm .= '>Date - Ascending</option>';
	$sortOrderSettingsForm .= '<option value="dd"';
	if ($cfg['index_page_sortorder'] == "dd")
		$sortOrderSettingsForm .= " selected";
	$sortOrderSettingsForm .= '>Date - Descending</option>';
	$sortOrderSettingsForm .= '<option value="na"';
	if ($cfg['index_page_sortorder'] == "na")
		$sortOrderSettingsForm .= " selected";
	$sortOrderSettingsForm .= '>Name - Ascending</option>';
	$sortOrderSettingsForm .= '<option value="nd"';
	if ($cfg['index_page_sortorder'] == "nd")
		$sortOrderSettingsForm .= " selected";
	$sortOrderSettingsForm .= '>Name - Descending</option>';
	$sortOrderSettingsForm .= '</select>';
	return $sortOrderSettingsForm;
}

/**
 * get form of move-settings
 *
 */
function getMoveSettingsForm() {
	global $cfg;
	$moveSettingsForm = '<table>';
	$moveSettingsForm .= '<tr>';
	$moveSettingsForm .= '<td valign="top" align="left">Target-Dirs:</td>';
	$moveSettingsForm .= '<td valign="top" align="left">';
	$moveSettingsForm .= '<select name="categorylist" size="5">';
	if ((isset($cfg["move_paths"])) && (strlen($cfg["move_paths"]) > 0)) {
		$dirs = split(":", trim($cfg["move_paths"]));
		foreach ($dirs as $dir) {
			$target = trim($dir);
			if ((strlen($target) > 0) && ((substr($target, 0, 1)) != ";"))
				$moveSettingsForm .= "<option value=\"$target\">".$target."</option>\n";
		}
	}
	$moveSettingsForm .= '</select>';
	$moveSettingsForm .= '<input type="button" name="remCatButton" value="remove" onclick="removeEntry()">';
	$moveSettingsForm .= '</td>';
	$moveSettingsForm .= '</tr>';
	$moveSettingsForm .= '<tr>';
	$moveSettingsForm .= '<td valign="top" align="left">New Target-Dir:</td>';
	$moveSettingsForm .= '<td valign="top" align="left">';
	$moveSettingsForm .= '<input type="text" name="category" size="30">';
	$moveSettingsForm .= '<input type="button" name="addCatButton" value="add" onclick="addEntry()" size="30">';
	$moveSettingsForm .= '<input type="hidden" name="move_paths" value="'.$cfg["move_paths"].'">';
	$moveSettingsForm .= '</td>';
	$moveSettingsForm .= '</tr>';
	$moveSettingsForm .= '</table>';
	return $moveSettingsForm;
}

/**
 * get form of index page settings (0-2047)
 *
 * #
 * Torrent
 *
 * User			  [0]
 * Size			  [1]
 * DLed			  [2]
 * ULed			  [3]
 *
 * Status		  [4]
 * Progress		  [5]
 * DL Speed		  [6]
 * UL Speed		  [7]
 *
 * Seeds		  [8]
 * Peers		  [9]
 * ETA			 [10]
 * TorrentClient [11]
 *
 */
function getIndexPageSettingsForm() {
	global $cfg;
	$settingsIndexPage = convertIntegerToArray($cfg["index_page_settings"]);
	$indexPageSettingsForm = '<table>';
	$indexPageSettingsForm .= '<tr>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Owner: <input name="index_page_settings_0" type="Checkbox" value="1"';
	if ($settingsIndexPage[0] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Size: <input name="index_page_settings_1" type="Checkbox" value="1"';
	if ($settingsIndexPage[1] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Total Down: <input name="index_page_settings_2" type="Checkbox" value="1"';
	if ($settingsIndexPage[2] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Total Up: <input name="index_page_settings_3" type="Checkbox" value="1"';
	if ($settingsIndexPage[3] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '</tr>';
	$indexPageSettingsForm .= '<tr>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Status : <input name="index_page_settings_4" type="Checkbox" value="1"';
	if ($settingsIndexPage[4] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Progress : <input name="index_page_settings_5" type="Checkbox" value="1"';
	if ($settingsIndexPage[5] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Down-Speed : <input name="index_page_settings_6" type="Checkbox" value="1"';
	if ($settingsIndexPage[6] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Up-Speed : <input name="index_page_settings_7" type="Checkbox" value="1"';
	if ($settingsIndexPage[7] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '</tr>';
	$indexPageSettingsForm .= '<tr>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Seeds : <input name="index_page_settings_8" type="Checkbox" value="1"';
	if ($settingsIndexPage[8] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Peers : <input name="index_page_settings_9" type="Checkbox" value="1"';
	if ($settingsIndexPage[9] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Estimated Time : <input name="index_page_settings_10" type="Checkbox" value="1"';
	if ($settingsIndexPage[10] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Client : <input name="index_page_settings_11" type="Checkbox" value="1"';
	if ($settingsIndexPage[11] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '</tr>';
	$indexPageSettingsForm .= '</table>';
	return $indexPageSettingsForm;
}

/**
 * get form of good looking stats hack (0-63)
 *
 */
function getGoodLookingStatsForm() {
	global $cfg;
	$settingsHackStats = convertByteToArray($cfg["hack_goodlookstats_settings"]);
	$goodLookingStatsForm = '<table>';
	$goodLookingStatsForm .= '<tr><td align="right" nowrap>Download Speed: <input name="hack_goodlookstats_settings_0" type="Checkbox" value="1"';
	if ($settingsHackStats[0] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td>';
	$goodLookingStatsForm .= '<td align="right" nowrap>Upload Speed: <input name="hack_goodlookstats_settings_1" type="Checkbox" value="1"';
	if ($settingsHackStats[1] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td>';
	$goodLookingStatsForm .= '<td align="right" nowrap>Total Speed: <input name="hack_goodlookstats_settings_2" type="Checkbox" value="1"';
	if ($settingsHackStats[2] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td></tr>';
	$goodLookingStatsForm .= '<tr><td align="right" nowrap>Connections: <input name="hack_goodlookstats_settings_3" type="Checkbox" value="1"';
	if ($settingsHackStats[3] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td>';
	$goodLookingStatsForm .= '<td align="right" nowrap>Drive Space: <input name="hack_goodlookstats_settings_4" type="Checkbox" value="1"';
	if ($settingsHackStats[4] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td>';
	$goodLookingStatsForm .= '<td align="right" nowrap>Server Load: <input name="hack_goodlookstats_settings_5" type="Checkbox" value="1"';
	if ($settingsHackStats[5] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td></tr>';
	$goodLookingStatsForm .= '</table>';
	return $goodLookingStatsForm;
}

/*
 * This method Builds the Transfers Section of the Index Page
 *
 * @return transfer-list as string
 */
function getTransferList() {
	global $cfg, $db;
	include_once("AliasFile.php");
	$kill_id = "";
	$lastUser = "";
	$arUserTorrent = array();
	$arListTorrent = array();
	// settings
	$settings = convertIntegerToArray($cfg["index_page_settings"]);
	// sortOrder
	$sortOrder = getRequestVar("so");
	if ($sortOrder == "")
		$sortOrder = $cfg["index_page_sortorder"];
	// t-list
	$arList = getTransferArray($sortOrder);
	foreach($arList as $entry) {

		// ---------------------------------------------------------------------
		// init some vars
		$displayname = $entry;
		$show_run = true;
		$torrentowner = getOwner($entry);
		$owner = IsOwner($cfg["user"], $torrentowner);
		if(strlen($entry) >= 47) {
			// needs to be trimmed
			$displayname = substr($entry, 0, 44);
			$displayname .= "...";
		}
		if ($cfg["enable_torrent_download"])
			$torrentfilelink = "<a href=\"index.php?page=maketorrent&download=".urlencode($entry)."\"><img src=\"images/down.gif\" width=9 height=9 title=\"Download Torrent File\" border=0 align=\"absmiddle\"></a>";
		else
			$torrentfilelink = "";

		// ---------------------------------------------------------------------
		// alias / stat
		$alias = getAliasName($entry).".stat";
		if ((substr( strtolower($entry),-8 ) == ".torrent")) {
			// this is a torrent-client
			$btclient = getTransferClient($entry);
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $torrentowner, $cfg, $btclient);
		} else if ((substr( strtolower($entry),-4 ) == ".url")) {
			// this is wget. use tornado statfile
			$btclient = "wget";
			$alias = str_replace(".url", "", $alias);
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
		} else {
			$btclient = "tornado";
			// this is "something else". use tornado statfile as default
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
		}
		// cache running-flag in local var. we will access that often
		$transferRunning = (int) $af->running;
		// cache percent-done in local var. ...
		$percentDone = $af->percent_done;

		// more vars
		$detailsLinkString = "<a style=\"font-size:9px; text-decoration:none;\" href=\"JavaScript:ShowDetails('index.php?page=downloaddetails&alias=".$alias."&torrent=".urlencode($entry)."')\">";

		// ---------------------------------------------------------------------
		//XFER: add upload/download stats to the xfer array
		if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1)) {
			if (($btclient) != "wget") {
				$torrentTotalsCurrent = getTransferTotalsCurrentOP($entry,$btclient,$af->uptotal,$af->downtotal);
			} else {
				$torrentTotalsCurrent["uptotal"] = $af->uptotal;
				$torrentTotalsCurrent["downtotal"] = $af->downtotal;
			}
			$sql = 'SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time());
			$newday = !$db->GetOne($sql);
			showError($db,$sql);
			sumUsage($torrentowner, ($torrentTotalsCurrent["downtotal"]+0), ($torrentTotalsCurrent["uptotal"]+0), 'total');
			sumUsage($torrentowner, ($torrentTotalsCurrent["downtotal"]+0), ($torrentTotalsCurrent["uptotal"]+0), 'month');
			sumUsage($torrentowner, ($torrentTotalsCurrent["downtotal"]+0), ($torrentTotalsCurrent["uptotal"]+0), 'week');
			sumUsage($torrentowner, ($torrentTotalsCurrent["downtotal"]+0), ($torrentTotalsCurrent["uptotal"]+0), 'day');
			//XFER: if new day add upload/download totals to last date on record and subtract from today in SQL
			if ($newday) {
				$newday = 2;
				$sql = 'SELECT date FROM tf_xfer ORDER BY date DESC';
				$lastDate = $db->GetOne($sql);
				showError($db,$sql);
				// MySQL 4.1.0 introduced 'ON DUPLICATE KEY UPDATE' to make this easier
				$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$torrentowner.'" AND date = "'.$lastDate.'"';
				if ($db->GetOne($sql)) {
					$sql = 'UPDATE tf_xfer SET download = download+'.($torrentTotalsCurrent["downtotal"]+0).', upload = upload+'.($torrentTotalsCurrent["uptotal"]+0).' WHERE user = "'.$torrentowner.'" AND date = "'.$lastDate.'"';
					$db->Execute($sql);
					showError($db,$sql);
				} else {
					showError($db,$sql);
					$sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$torrentowner.'","'.$lastDate.'",'.($torrentTotalsCurrent["downtotal"]+0).','.($torrentTotalsCurrent["uptotal"]+0).')';
					$db->Execute($sql);
					showError($db,$sql);
				}
				$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$torrentowner.'" AND date = '.$db->DBDate(time());
				if ($db->GetOne($sql)) {
					$sql = 'UPDATE tf_xfer SET download = download-'.($torrentTotalsCurrent["downtotal"]+0).', upload = upload-'.($torrentTotalsCurrent["uptotal"]+0).' WHERE user = "'.$torrentowner.'" AND date = '.$db->DBDate(time());
					$db->Execute($sql);
					showError($db,$sql);
				} else {
					showError($db,$sql);
					$sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$torrentowner.'",'.$db->DBDate(time()).',-'.($torrentTotalsCurrent["downtotal"]+0).',-'.($torrentTotalsCurrent["uptotal"]+0).')';
					$db->Execute($sql);
					showError($db,$sql);
				}
			}
		}

		// ---------------------------------------------------------------------
		// injects
		if(! file_exists($cfg["torrent_file_path"].$alias)) {
			$transferRunning = 2;
			$af->running = "2";
			$af->size = getDownloadSize($cfg["torrent_file_path"].$entry);
			$af->WriteFile();
		}

		// ---------------------------------------------------------------------
		// preprocess alias-file and get some vars
		$estTime = "&nbsp;";
		$statusStr = "&nbsp;";
		switch ($transferRunning) {
			case 2: // new
				// $statusStr
				$statusStr = $detailsLinkString."<font color=\"#32cd32\">New</font></a>";
				break;
			case 3: // queued
				// $statusStr
				$statusStr = $detailsLinkString."Queued</a>";
				// $estTime
				$estTime = "Waiting...";
				break;
			default: // running
				// increment the totals
				if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
				if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
				$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
				$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
				// $estTime
				if ($af->time_left != "" && $af->time_left != "0")
					$estTime = $af->time_left;
				// $lastUser
				$lastUser = $torrentowner;
				// $show_run + $statusStr
				if($percentDone >= 100) {
					if(trim($af->up_speed) != "" && $transferRunning == 1) {
						$statusStr = $detailsLinkString.'Seeding</a>';
					} else {
						$statusStr = $detailsLinkString.'Done</a>';
					}
					$show_run = false;
				} else if ($percentDone < 0) {
					$statusStr = $detailsLinkString."Stopped</a>";
					$show_run = true;
				} else {
					$statusStr = $detailsLinkString."Leeching</a>";
				}
				break;
		}
		// totals-preparation
		// if downtotal + uptotal + progress > 0
		if (($settings[2] + $settings[3] + $settings[5]) > 0) {
			if (($btclient) != "wget") {
				$torrentTotals = getTransferTotalsOP($entry,$btclient,$af->uptotal,$af->downtotal);
			} else {
				$torrentTotals["uptotal"] = $af->uptotal;
				$torrentTotals["downtotal"] = $af->downtotal;
			}
		}

		// ---------------------------------------------------------------------
		// output-string
		$output = "<tr>";

		// ========================================================== led + meta
		$output .= '<td valign="bottom" align="center">';
		// led
		$hd = getStatusImage($af);
		if ($transferRunning == 1)
			$output .= "<a href=\"JavaScript:ShowDetails('index.php?page=downloadhosts&alias=".$alias."&torrent=".urlencode($entry)."')\">";
		$output .= "<img src=\"images/".$hd->image."\" width=\"16\" height=\"16\" title=\"".$hd->title.$entry."\" border=\"0\" align=\"absmiddle\">";
		if ($transferRunning == 1)
			$output .= "</a>";
		// meta
		$output .= $torrentfilelink;
		$output .= "</td>";

		// ================================================================ name
		$output .= "<td valign=\"bottom\">".$detailsLinkString.$displayname."</a></td>";

		// =============================================================== owner
		if ($settings[0] != 0)
			$output .= "<td valign=\"bottom\" align=\"center\"><a href=\"index.php?page=message&to_user=".$torrentowner."\"><font class=\"tiny\">".$torrentowner."</font></a></td>";

		// ================================================================ size
		if ($settings[1] != 0)
			$output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesToKBMGGB($af->size)."</a></td>";

		// =========================================================== downtotal
		if ($settings[2] != 0)
			$output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesToKBMGGB($torrentTotals["downtotal"]+0)."</a></td>";

		// ============================================================= uptotal
		if ($settings[3] != 0)
			$output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesToKBMGGB($torrentTotals["uptotal"]+0)."</a></td>";

		// ============================================================== status
		if ($settings[4] != 0)
			$output .= "<td valign=\"bottom\" align=\"center\">".$detailsLinkString.$statusStr."</a></td>";

		// ============================================================ progress
		if ($settings[5] != 0) {
			$graph_width = 1;
			$progress_color = "#00ff00";
			$background = "#000000";
			$bar_width = "4";
			$percentage = "";
			if (($percentDone >= 100) && (trim($af->up_speed) != "")) {
				$graph_width = -1;
				$percentage = @number_format((($torrentTotals["uptotal"] / $af->size) * 100), 2) . '%';
			} else {
				if ($percentDone >= 1) {
					$graph_width = $percentDone;
					$percentage = $graph_width . '%';
				} else if ($percentDone < 0) {
					$graph_width = round(($percentDone*-1)-100,1);
					$percentage = $graph_width . '%';
				} else {
					$graph_width = 0;
					$percentage = '0%';
				}
			}
			if($graph_width == 100)
				$background = $progress_color;
			$output .= "<td valign=\"bottom\" align=\"center\" nowrap>";
			if ($graph_width == -1) {
				$output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
			} else if ($graph_width > 0) {
				$output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
				$output .= "<br>";
				$output .= "<table width=\"100\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
				$output .= "<td background=\"themes/".$cfg["theme"]."/images/progressbar.gif\" bgcolor=\"".$progress_color."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".$graph_width."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
				$output .= "<td bgcolor=\"".$background."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".(100 - $graph_width)."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
				$output .= "</tr></table>";
			} else {
				if ($transferRunning == 2) {
					$output .= '&nbsp;';
				} else {
					$output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
					$output .= "<br>";
					$output .= "<table width=\"100\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
					$output .= "<td background=\"themes/".$cfg["theme"]."/images/progressbar.gif\" bgcolor=\"".$progress_color."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".$graph_width."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
					$output .= "<td bgcolor=\"".$background."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".(100 - $graph_width)."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
					$output .= "</tr></table>";
				}
			}
			$output .= "</td>";
		}

		// ================================================================ down
		if ($settings[6] != 0) {
			$output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
			if ($transferRunning == 1) {
				$output .= $detailsLinkString;
				if (trim($af->down_speed) != "")
					$output .= $af->down_speed;
				else
					$output .= '0.0 kB/s';
				$output .= '</a>';
			} else {
				 $output .= '&nbsp;';
			}
			$output .= '</td>';
		}

		// ================================================================== up
		if ($settings[7] != 0) {
			$output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
			if ($transferRunning == 1) {
				$output .= $detailsLinkString;
				if (trim($af->up_speed) != "")
					$output .= $af->up_speed;
				else
					$output .= '0.0 kB/s';
				$output .= '</a>';
			} else {
				 $output .= '&nbsp;';
			}
			$output .= '</td>';
		}

		// =============================================================== seeds
		if ($settings[8] != 0) {
			$output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
			if ($transferRunning == 1) {
				$output .= $detailsLinkString;
				$output .= $af->seeds;
				$output .= '</a>';
			} else {
				 $output .= '&nbsp;';
			}
			$output .= '</td>';
		}

		// =============================================================== peers
		if ($settings[9] != 0) {
			$output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
			if ($transferRunning == 1) {
				$output .= $detailsLinkString;
				$output .= $af->peers;
				$output .= '</a>';
			} else {
				 $output .= '&nbsp;';
			}
			$output .= '</td>';
		}

		// ================================================================= ETA
		if ($settings[10] != 0)
			$output .= "<td valign=\"bottom\" align=\"center\">".$detailsLinkString.$estTime."</a></td>";

		// ============================================================== client
		if ($settings[11] != 0) {
			switch ($btclient) {
				case "tornado":
					$output .= "<td valign=\"bottom\" align=\"center\">B</a></td>";
				break;
				case "transmission":
					$output .= "<td valign=\"bottom\" align=\"center\">T</a></td>";
				break;
				case "wget":
					$output .= "<td valign=\"bottom\" align=\"center\">W</a></td>";
				break;
				default:
					$output .= "<td valign=\"bottom\" align=\"center\">U</a></td>";
			}
		}

		// =============================================================== admin
		$output .= "<td><div align=center>";
		$torrentDetails = _TORRENTDETAILS;
		if ($lastUser != "")
			$torrentDetails .= "\n"._USER.": ".$lastUser;
		$output .= "<a href=\"index.php?page=details&torrent=".urlencode($entry);
		if($transferRunning == 1)
			$output .= "&als=false";
		$output .= "\"><img src=\"images/properties.png\" width=18 height=13 title=\"".$torrentDetails."\" border=0></a>";
		if ($owner || IsAdmin($cfg["user"])) {
			if($percentDone >= 0 && $transferRunning == 1) {
				$output .= "<a href=\"index.php?page=index&alias_file=".$alias."&kill=".$kill_id."&kill_torrent=".urlencode($entry)."\"><img src=\"images/kill.gif\" width=16 height=16 title=\""._STOPDOWNLOAD."\" border=0></a>";
				$output .= "<img src=\"images/delete_off.gif\" width=16 height=16 border=0>";
				if ($cfg['enable_multiops'] != 0)
					$output .= "<input type=\"checkbox\" name=\"torrent[]\" value=\"".urlencode($entry)."\">";
			} else {
				if($torrentowner == "n/a") {
					$output .= "<img src=\"images/run_off.gif\" width=16 height=16 border=0 title=\""._NOTOWNER."\">";
				} else {
					if ($transferRunning == 3) {
						$output .= "<a href=\"index.php?page=index&alias_file=".$alias."&dQueue=".$kill_id."&QEntry=".urlencode($entry)."\"><img src=\"images/queued.gif\" width=16 height=16 title=\""._DELQUEUE."\" border=0></a>";
					} else {
						if (!is_file($cfg["torrent_file_path"].$alias.".pid")) {
							// Allow Avanced start popup?
							if ($cfg["advanced_start"] != 0) {
								if($show_run)
									$output .= "<a href=\"#\" onclick=\"StartTorrent('index.php?page=startpop&torrent=".urlencode($entry)."')\"><img src=\"images/run_on.gif\" width=16 height=16 title=\""._RUNTORRENT."\" border=0></a>";
								else
									$output .= "<a href=\"#\" onclick=\"StartTorrent('index.php?page=startpop&torrent=".urlencode($entry)."')\"><img src=\"images/seed_on.gif\" width=16 height=16 title=\""._SEEDTORRENT."\" border=0></a>";
							} else {
								// Quick Start
								if($show_run)
									$output .= "<a href=\"".$_SERVER['PHP_SELF']."?torrent=".urlencode($entry)."\"><img src=\"images/run_on.gif\" width=16 height=16 title=\""._RUNTORRENT."\" border=0></a>";
								else
									$output .= "<a href=\"".$_SERVER['PHP_SELF']."?torrent=".urlencode($entry)."\"><img src=\"images/seed_on.gif\" width=16 height=16 title=\""._SEEDTORRENT."\" border=0></a>";
							}
						} else {
							// pid file exists so this may still be running or dieing.
							$output .= "<img src=\"images/run_off.gif\" width=16 height=16 border=0 title=\""._STOPPING."\">";
						}
					}
				}
				if (!is_file($cfg["torrent_file_path"].$alias.".pid")) {
					$deletelink = $_SERVER['PHP_SELF']."?alias_file=".$alias."&delfile=".urlencode($entry);
					$output .= "<a href=\"".$deletelink."\" onclick=\"return ConfirmDelete('".$entry."')\"><img src=\"images/delete_on.gif\" width=16 height=16 title=\""._DELETE."\" border=0></a>";
					if ($cfg['enable_multiops'] != 0)
						$output .= "<input type=\"checkbox\" name=\"torrent[]\" value=\"".urlencode($entry)."\">";
				} else {
					// pid file present so process may be still running. don't allow deletion.
					$output .= "<img src=\"images/delete_off.gif\" width=16 height=16 title=\""._STOPPING."\" border=0>";
					if ($cfg['enable_multiops'] != 0)
						$output .= "<input type=\"checkbox\" name=\"torrent[]\" value=\"".urlencode($entry)."\">";
				}
			}
		} else {
			$output .= "<img src=\"images/locked.gif\" width=16 height=16 border=0 title=\""._NOTOWNER."\">";
			$output .= "<img src=\"images/locked.gif\" width=16 height=16 border=0 title=\""._NOTOWNER."\">";
			$output .= "<input type=\"checkbox\" disabled=\"disabled\">";
		}
		$output .= "</div>";
		$output .= "</td>";
		$output .= "</tr>\n";

		// ---------------------------------------------------------------------
		// Is this torrent for the user list or the general list?
		if ($cfg["user"] == getOwner($entry))
			array_push($arUserTorrent, $output);
		else
			array_push($arListTorrent, $output);
	}

	//XFER: if a new day but no .stat files where found put blank entry into the DB for today to indicate accounting has been done for the new day
	if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1)) {
	  if ($newday == 1) {
		$sql = 'INSERT INTO tf_xfer (user,date) values ( "",'.$db->DBDate(time()).')';
		$db->Execute($sql);
		showError($db,$sql);
	  }
	  getUsage(0, 'total');
	  $month_start = (date('j')>=$cfg['month_start']) ? date('Y-m-').$cfg['month_start'] : date('Y-m-',strtotime('-1 Month')).$cfg['month_start'];
	  getUsage($month_start, 'month');
	  $week_start = date('Y-m-d',strtotime('last '.$cfg['week_start']));
	  getUsage($week_start, 'week');
	  $day_start = date('Y-m-d');
	  getUsage($day_start, 'day');
	}

	// -------------------------------------------------------------------------
	// build output-string
	$output = '<table bgcolor="'.$cfg["table_data_bg"].'" width="100%" bordercolor="'.$cfg["table_border_dk"].'" border="1" cellpadding="3" cellspacing="0" class="sortable" id="transfer_table">';
	if (sizeof($arUserTorrent) > 0) {
		addTransferTableHead(&$output, &$settings, $sortOrder, $cfg["user"]." : ");
		foreach($arUserTorrent as $torrentrow)
			$output .= $torrentrow;
	}
	$boolCond = true;
	if ($cfg['enable_restrictivetview'] == 1)
		$boolCond = IsAdmin();
	if (($boolCond) && (sizeof($arListTorrent) > 0)) {
		addTransferTableHead(&$output, &$settings, $sortOrder);
		foreach($arListTorrent as $torrentrow)
			$output .= $torrentrow;
	}
	$output .= "</tr></table>\n";
	return $output;
}

/*
 * This method adds html-snip of table-head to content-string
 *
 * @param &$output ref to string holding content where to add.
 * @param &$settings ref to array holding index-page-settings
 * @param $sortOrder
 * @param $nPrefix prefix of name-column
 * @return array with transfers
 */
function addTransferTableHead(&$output, &$settings, $sortOrder = '', $nPrefix = '') {
	global $cfg;
	$output .= "<tr>";
	//
	// ============================================================== led + meta
	$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">";
	switch ($sortOrder) {
		case 'da': // sort by date ascending
			$output .= '<a href="?so=dd"><font class="adminlink">#</font></a>';
			$output .= '&nbsp;';
			$output .= '<a href="?so=dd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
			break;
		case 'dd': // sort by date descending
			$output .= '<a href="?so=da"><font class="adminlink">#</font></a>';
			$output .= '&nbsp;';
			$output .= '<a href="?so=da"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
			break;
		default:
			$output .= '<a href="?so=dd"><font class="adminlink">#</font></a>';
			break;
	}
	$output .= "</div></td>";
	// ==================================================================== name
	$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">";
	switch ($sortOrder) {
		case 'na': // sort alphabetically by name ascending
			$output .= '<a href="?so=nd"><font class="adminlink">' .$nPrefix. _TORRENTFILE .'</font></a>';
			$output .= '&nbsp;';
			$output .= '<a href="?so=nd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
			break;
		case 'nd': // sort alphabetically by name descending
			$output .= '<a href="?so=na"><font class="adminlink">' .$nPrefix. _TORRENTFILE .'</font></a>';
			$output .= '&nbsp;';
			$output .= '<a href="?so=na"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
			break;
		default:
			$output .= '<a href="?so=na"><font class="adminlink">' .$nPrefix. _TORRENTFILE .'</font></a>';
			break;
	}
	$output .= "</div></td>";
	// =================================================================== owner
	if ($settings[0] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._USER."</div></td>";
	// ==================================================================== size
	if ($settings[1] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Size</div></td>";
	// =============================================================== downtotal
	if ($settings[2] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">T. Down</div></td>";
	// ================================================================= uptotal
	if ($settings[3] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">T. Up</div></td>";
	// ================================================================== status
	if ($settings[4] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._STATUS."</div></td>";
	// ================================================================ progress
	if ($settings[5] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Progress</div></td>";
	// ==================================================================== down
	if ($settings[6] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Down</div></td>";
	// ====================================================================== up
	if ($settings[7] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Up</div></td>";
	// =================================================================== seeds
	if ($settings[8] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Seeds</div></td>";
	// =================================================================== peers
	if ($settings[9] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Peers</div></td>";
	// ===================================================================== ETA
	if ($settings[10] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._ESTIMATEDTIME."</div></td>";
	// ================================================================== client
	if ($settings[11] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">C</div></td>";
	// =================================================================== admin
	$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._ADMIN."</div></td>";
	//
	$output .= "</tr>\n";
}
?>