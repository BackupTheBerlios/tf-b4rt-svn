
// fields
var ajax_fieldIds = new Array(
	"speedDown",
	"speedUp",
	"speedTotal",
	"connections",
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

/**
 * ajax_initialize
 *
 * @param url
 * @param timer
 * @param delim
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
	ajax_httpRequest = ajax_getHttpRequest();
	setTimeout("ajax_update();", ajax_updateTimer);
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
}
