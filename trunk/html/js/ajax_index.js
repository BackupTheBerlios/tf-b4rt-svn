
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
var xferEnabled = 0;
var driveSpaceBarStyle = "tf";
var bandwidthBarsEnabled = 0;
var bandwidthBarsStyle = "tf";

var updateTimeLeft = 0;

/**
 * ajax_initialize
 *
 * @param url
 * @param timer
 * @param delim
 * @param glsEnabled
 * @param glsSettings
 * @param bsEnabled
 * @param qActive
 * @param xEnabled
 * @param dsBarStyle
 * @param bwBarsEnabled
 * @param bwBarsStyle
 */
function ajax_initialize(url, timer, delim, glsEnabled, glsSettings, bsEnabled, qActive, xEnabled, dsBarStyle, bwBarsEnabled, bwBarsStyle) {
	ajax_statsUrl = url;
	ajax_updateTimer = timer;
	ajax_txtDelim = delim;
	goodLookingStatsEnabled = glsEnabled;
	if (goodLookingStatsEnabled == 1)
		goodLookingStatsSettings = glsSettings.split(":");
	bottomStatsEnabled = bsEnabled;
	queueActive = qActive;
	xferEnabled = xEnabled;
	driveSpaceBarStyle = dsBarStyle;
	bandwidthBarsEnabled = bwBarsEnabled;
	bandwidthBarsStyle = bwBarsStyle;
	ajax_statsParams = "";
	if ((bottomStatsEnabled == 1) && (xferEnabled == 1))
		ajax_statsParams += '?t=home';
	else
		ajax_statsParams += '?t=server';
	if (ajax_useXML)
		ajax_statsParams += '&f=xml';
	else
		ajax_statsParams += '&f=txt&h=0';
	// http-request
	ajax_httpRequest = ajax_getHttpRequest();
	// start update-thread
	updateTimeLeft = ajax_updateTimer / 1000;
	ajax_indexUpdate();
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
	if ((bottomStatsEnabled == 1) && (xferEnabled == 1)) {
		tempAry = content.split("\n");
		ajax_updateContent(tempAry[0].split(ajax_txtDelim), tempAry[1].split(ajax_txtDelim));
	} else {
		ajax_updateContent(content.split(ajax_txtDelim), null);
	}
}

/**
 * update page contents from response
 *
 * @param statsServer
 * @param statsXfer
 */
function ajax_updateContent(statsServer, statsXfer) {
	// good looking stats
	if (goodLookingStatsEnabled == 1) {
		for (i = 0; i < ajax_idCount; i++) {
			if (goodLookingStatsSettings[i] == 1)
				document.getElementById("g_" + ajax_fieldIds[i]).innerHTML = statsServer[i];
		}
	}
	// drivespace-bar
	document.getElementById("barFreeSpace").innerHTML = statsServer[4];
	document.getElementById("barDriveSpacePercent").innerHTML = (100 - statsServer[10]);
	document.getElementById("barDriveSpace1").width = (statsServer[ajax_idCount + 4]) + "%";
	document.getElementById("barDriveSpace2").width = (100 - statsServer[ajax_idCount + 4]) + "%";
	//if (driveSpaceBarStyle == "xfer") {
	// set color
	//}
	// bandwidth-bars
	if (bandwidthBarsEnabled == 1) {
		// up
		document.getElementById("barSpeedUpPercent").innerHTML = statsServer[ajax_idCount + 3];
		document.getElementById("barSpeedUp").innerHTML = statsServer[1];
		document.getElementById("barSpeedUp1").width = (statsServer[ajax_idCount + 3]) + "%";
		document.getElementById("barSpeedUp2").width = (100 - statsServer[ajax_idCount + 3]) + "%";
		// down
		document.getElementById("barSpeedDownPercent").innerHTML = statsServer[ajax_idCount + 2];
		document.getElementById("barSpeedDown").innerHTML = statsServer[0];
		document.getElementById("barSpeedDown1").width = (statsServer[ajax_idCount + 2]) + "%";
		document.getElementById("barSpeedDown2").width = (100 - statsServer[ajax_idCount + 2]) + "%";
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
	// timer
	updateTimeLeft = ajax_updateTimer / 1000;
}

/**
 * index-page ajax-update
 *
 */
function ajax_indexUpdate() {
	if (updateTimeLeft < 0) {
		document.getElementById("span_update").innerHTML = "Update in progress...";
	} else if (updateTimeLeft == 0) {
		document.getElementById("span_update").innerHTML = "Update in progress...";
		updateTimeLeft = -1;
		ajax_update();
	} else {
		document.getElementById("span_update").innerHTML = "Next AJAX-Update in " + String(updateTimeLeft) + " seconds";
	}
	updateTimeLeft--;
	setTimeout("ajax_indexUpdate();", 1000);
}
