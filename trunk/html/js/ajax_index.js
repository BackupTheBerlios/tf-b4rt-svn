/* $Id$ */

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
//
var titleChangeEnabled = 0;
var pageTitle = "torrentflux-b4rt";
var goodLookingStatsEnabled = 0;
var goodLookingStatsSettings = null;
var bottomStatsEnabled = 0;
var queueActive = 0;
var transferListEnabled = 0;
var xferEnabled = 0;
var sortTableEnabled = 0;
var driveSpaceBarStyle = "tf";
var bandwidthBarsEnabled = 0;
var bandwidthBarsStyle = "tf";
var imgSrcDriveSpaceBlank = "themes/default/images/blank.gif";
var imgHeightDriveSpaceBlank = 12;
var imgSrcBandwidthUpBlank = "themes/default/images/blank.gif";
var imgHeightBandwidthUpBlank = 12;
var imgSrcBandwidthDownBlank = "themes/default/images/blank.gif";
var imgHeightBandwidthDownBlank = 12;
//
var updateTimeLeft = 0;

/**
 * ajax_initialize
 *
 * @param timer
 * @param delim
 * @param tChangeEnabled
 * @param pTitle
 * @param glsEnabled
 * @param glsSettings
 * @param bsEnabled
 * @param qActive
 * @param xEnabled
 * @param tEnabled
 * @param sortEnabled
 * @param dsBarStyle
 * @param bwBarsEnabled
 * @param bwBarsStyle
 */
function ajax_initialize(timer, delim, tChangeEnabled, pTitle, glsEnabled, glsSettings, bsEnabled, qActive, xEnabled, tEnabled, sortEnabled, dsBarStyle, bwBarsEnabled, bwBarsStyle) {
	ajax_updateTimer = timer;
	ajax_txtDelim = delim;
	titleChangeEnabled = tChangeEnabled;
	pageTitle = pTitle;
	goodLookingStatsEnabled = glsEnabled;
	bottomStatsEnabled = bsEnabled;
	queueActive = qActive;
	xferEnabled = xEnabled;
	transferListEnabled = tEnabled;
	sortTableEnabled = sortEnabled;
	driveSpaceBarStyle = dsBarStyle;
	bandwidthBarsEnabled = bwBarsEnabled;
	bandwidthBarsStyle = bwBarsStyle;
	// url + params
	ajax_updateUrl = "index.php?iid=index";
	ajax_updateParams = "&ajax_update=1";
	if ((bottomStatsEnabled == 1) && (xferEnabled == 1))
		ajax_updateParams += '1';
	else
		ajax_updateParams += '0';
	ajax_updateParams += transferListEnabled;
	// gls
	if (goodLookingStatsEnabled == 1)
		goodLookingStatsSettings = glsSettings.split(":");
	// tf-style drivespace bar init
	if (driveSpaceBarStyle == "xfer") {
		elementBlank = document.getElementById("imgDriveSpaceBlank");
		imgSrcDriveSpaceBlank = elementBlank.src;
		imgHeightDriveSpaceBlank = elementBlank.height;
	}
	// tf-style bandwidth bars init
	if ((bandwidthBarsEnabled == 1) && (bandwidthBarsStyle == "xfer")) {
		elementBlank = document.getElementById("imgBandwidthUpBlank");
		imgSrcBandwidthUpBlank = elementBlank.src;
		imgHeightBandwidthUpBlank = elementBlank.height;
		elementBlank = document.getElementById("imgBandwidthDownBlank");
		imgSrcBandwidthDownBlank = elementBlank.src;
		imgHeightBandwidthDownBlank = elementBlank.height;
	}
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
	if (ajax_updateState == 1) {
		if (updateTimeLeft < 0) {
			document.getElementById("span_update").innerHTML = "Update in progress...";
			if (titleChangeEnabled == 1)
				document.title = "Update in progress... - "+ pageTitle;
		} else if (updateTimeLeft == 0) {
			updateTimeLeft = -1;
			document.getElementById("span_update").innerHTML = "Update in progress...";
			if (titleChangeEnabled == 1)
				document.title = "Update in progress... - "+ pageTitle;
			setTimeout("ajax_update();", 100);
		} else {
			document.getElementById("span_update").innerHTML = "Next AJAX-Update in " + String(updateTimeLeft) + " seconds";
		}
		updateTimeLeft--;
	} else {
		document.getElementById("span_update").innerHTML = "AJAX-Update disabled";
	}
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
	// page-title
	if (titleChangeEnabled == 1) {
		newTitle = "";
		for (i = 0; i < 5; i++) {
			newTitle += statsServer[i] + "|";
		}
		newTitle += statsServer[5]+ " - " + pageTitle;
		document.title = newTitle;
	}
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
	if (driveSpaceBarStyle == "xfer") {
		// set color
		dsbCol = 'rgb(';
		dsbCol += parseInt(255 - 255 * ((100 - dSpace) / 100));
		dsbCol += ',' + parseInt(255 * ((100 - dSpace) / 100));
		dsbCol += ',0)';
		dsbDiv  = '<div style="background:' + dsbCol + ';">';
		dsbDiv += '<img id="imgDriveSpaceBlank" src="' + imgSrcDriveSpaceBlank + '" width="1" height="' + imgHeightDriveSpaceBlank + '" border="0">';
		dsbDiv += '</div>';
		document.getElementById("barDriveSpace2").innerHTML = dsbDiv;
	}
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
		if (bandwidthBarsStyle == "xfer") {
			// set color
			// up
			bwbCol  = 'rgb(';
			bwbCol += parseInt(255 - 255 * ((100 - upPer) / 150));
			bwbCol += ',' + parseInt(255 * ((100 - upPer) / 150));
			bwbCol += ',0)';
			bwbDiv  = '<div style="background:' + bwbCol + ';">';
			bwbDiv += '<img id="imgBandwidthUpBlank" src="' + imgSrcBandwidthUpBlank + '" width="1" height="' + imgHeightBandwidthUpBlank + '" border="0">';
			bwbDiv += '</div>';
			document.getElementById("barSpeedUp1").innerHTML = bwbDiv;
			// down
			bwbCol  = 'rgb(';
			bwbCol += parseInt(255 - 255 * ((100 - downPer) / 150));
			bwbCol += ',' + parseInt(255 * ((100 - downPer) / 150));
			bwbCol += ',0)';
			bwbDiv  = '<div style="background:' + bwbCol + ';">';
			bwbDiv += '<img id="imgBandwidthDownBlank" src="' + imgSrcBandwidthDownBlank + '" width="1" height="' + imgHeightBandwidthDownBlank + '" border="0">';
			bwbDiv += '</div>';
			document.getElementById("barSpeedDown1").innerHTML = bwbDiv;
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
	// transfer-list
	if (transferListEnabled == 1) {
		// update content
		document.getElementById("transferList").innerHTML = transferList;
		// re-init sort-table
		if (sortTableEnabled == 1)
			sortables_init();
	}
}
