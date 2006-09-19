
// fields
var ajax_fieldIds = new Array(
	"speedDown",
	"speedUp",
	"speedTotal",
	"cons",
	"freeSpace",
	"loadavg"
);
var ajax_idCount = ajax_fieldIds.length;
var ajax_fieldIdsXfer = new Array(
	"xferGlobalTotal",
	"xferGlobalMonth",
	"xferGlobalWeek",
	"xferGlobalDay",
	"xferUserTotal",
	"xferUserMonth",
	"xferUserWeek",
	"xferUserDay"
);
var ajax_idCountXfer = ajax_fieldIdsXfer.length;
var goodLookingStatsEnabled = 0;
var goodLookingStatsSettings = null;
var bottomStatsEnabled = 0;
var queueActive = 0;
var transferListEnabled = 0;
var xferEnabled = 0;
var driveSpaceBarStyle = "tf";
var bandwidthBarsEnabled = 0;
var bandwidthBarsStyle = "tf";
var updateTimeLeft = 0;

/**
 * ajax_initialize
 *
 * @param timer
 * @param delim
 * @param glsEnabled
 * @param glsSettings
 * @param bsEnabled
 * @param qActive
 * @param xEnabled
 * @param tEnabled
 * @param dsBarStyle
 * @param bwBarsEnabled
 * @param bwBarsStyle
 */
function ajax_initialize(timer, delim, glsEnabled, glsSettings, bsEnabled, qActive, xEnabled, tEnabled, dsBarStyle, bwBarsEnabled, bwBarsStyle) {
	ajax_updateTimer = timer;
	ajax_txtDelim = delim;
	goodLookingStatsEnabled = glsEnabled;
	if (goodLookingStatsEnabled == 1)
		goodLookingStatsSettings = glsSettings.split(":");
	bottomStatsEnabled = bsEnabled;
	queueActive = qActive;
	xferEnabled = xEnabled;
	transferListEnabled = tEnabled;
	driveSpaceBarStyle = dsBarStyle;
	bandwidthBarsEnabled = bwBarsEnabled;
	bandwidthBarsStyle = bwBarsStyle;
	ajax_updateUrl = "index.php?iid=index";
	ajax_updateParams = "&ajax_update=1";
	if ((bottomStatsEnabled == 1) && (xferEnabled == 1))
		ajax_updateParams += '1';
	else
		ajax_updateParams += '0';
	ajax_updateParams += transferListEnabled;
	// state
	ajax_updateState = 1;
	// http-request
	ajax_httpRequest = ajax_getHttpRequest();
	// start update-thread
	updateTimeLeft = ajax_updateTimer / 1000;
	ajax_pageUpdate();
}

/**
 * page ajax-update
 *
 */
function ajax_pageUpdate() {
	if (updateTimeLeft < 0) {
		document.getElementById("span_update").innerHTML = "Update in progress...";
	} else if (updateTimeLeft == 0) {
		document.getElementById("span_update").innerHTML = "Update in progress...";
		updateTimeLeft = -1;
		setTimeout("ajax_update();", 100);
	} else {
		document.getElementById("span_update").innerHTML = "Next AJAX-Update in " + String(updateTimeLeft) + " seconds";
	}
	updateTimeLeft--;
	setTimeout("ajax_pageUpdate();", 1000);
}

/**
 * process XML-response
 *
 * @param content
 */
function ajax_processXML(content) {
	alert(content);
}

/**
 * process text-response
 *
 * @param content
 */
function ajax_processText(content) {
	// content
	if ((bottomStatsEnabled == 1) && (xferEnabled == 1)) {
		tempAry = content.split("|");
		if (transferListEnabled == 1)
			ajax_updateContent(tempAry[0].split(ajax_txtDelim), tempAry[1].split(ajax_txtDelim), tempAry[2]);
		else
			ajax_updateContent(tempAry[0].split(ajax_txtDelim), tempAry[1].split(ajax_txtDelim), null);
	} else {
		if (transferListEnabled == 1) {
			tempAry = content.split("|");
			ajax_updateContent(tempAry[0].split(ajax_txtDelim), null, tempAry[1]);
		} else {
			ajax_updateContent(content.split(ajax_txtDelim), null, null);
		}
	}
	// timer
	updateTimeLeft = ajax_updateTimer / 1000;
}

/**
 * update page contents from response
 *
 * @param statsServer
 * @param statsXfer
 */
function ajax_updateContent(statsServer, statsXfer, transferList) {
	// good looking stats
	if (goodLookingStatsEnabled == 1) {
		for (i = 0; i < ajax_idCount; i++) {
			if (goodLookingStatsSettings[i] == 1)
				document.getElementById("g_" + ajax_fieldIds[i]).innerHTML = statsServer[i];
		}
	}
	// drivespace-bar
	dSpace = statsServer[10];
	document.getElementById("barFreeSpace").innerHTML = statsServer[4];
	document.getElementById("barDriveSpacePercent").innerHTML = (100 - dSpace);
	if (dSpace == 0)
		document.getElementById("barDriveSpace1").width = 1;
	else
		document.getElementById("barDriveSpace1").width = dSpace + "%";
	if (dSpace == 100)
		document.getElementById("barDriveSpace2").width = 1;
	else
		document.getElementById("barDriveSpace2").width = (100 - dSpace) + "%";
	//if (driveSpaceBarStyle == "xfer") {
		// set color
	//}
	// bandwidth-bars
	if (bandwidthBarsEnabled == 1) {
		// up
		upPer = statsServer[9];
		document.getElementById("barSpeedUpPercent").innerHTML = upPer;
		document.getElementById("barSpeedUp").innerHTML = statsServer[1];
		if (upPer == 0)
			document.getElementById("barSpeedUp1").width = 1;
		else
			document.getElementById("barSpeedUp1").width = upPer + "%";

		if (upPer == 100)
			document.getElementById("barSpeedUp2").width = 1;
		else
			document.getElementById("barSpeedUp2").width = (100 - upPer) + "%";
		// down
		downPer = statsServer[8];
		document.getElementById("barSpeedDownPercent").innerHTML = downPer;
		document.getElementById("barSpeedDown").innerHTML = statsServer[0];
		if (downPer == 0)
			document.getElementById("barSpeedDown1").width = 1;
		else
			document.getElementById("barSpeedDown1").width = downPer + "%";
		if (downPer == 100)
			document.getElementById("barSpeedDown2").width = 1;
		else
			document.getElementById("barSpeedDown2").width = (100 - downPer) + "%";
		//if (bandwidthBarsStyle == "xfer") {
			// set color
		//}
	}
	// bottom stats
	if (bottomStatsEnabled == 1) {
		for (i = 0; i < ajax_idCount; i++) {
			document.getElementById("b_" + ajax_fieldIds[i]).innerHTML = statsServer[i];
		}
		// running + queued
		if (queueActive == 1) {
			document.getElementById("running").innerHTML = statsServer[ajax_idCount];
			document.getElementById("queued").innerHTML = statsServer[ajax_idCount + 1];
		}
		// xfer
		if (xferEnabled == 1) {
			for (i = 0; i < ajax_idCountXfer; i++) {
				document.getElementById(ajax_fieldIdsXfer[i]).innerHTML = statsXfer[i];
			}
		}
	}
	// transfer-list
	if (transferListEnabled == 1)
		document.getElementById("transferList").innerHTML = transferList;
}
