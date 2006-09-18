
// fields
var ajax_fieldIds = new Array(
	"speedDown",
	"speedUp",
	"speedTotal",
	"connections",
	"freeSpace",
	"loadavg",
	"running",
	"queued"
);
var ajax_idCount = ajax_fieldIds.length;

/**
 * ajax_initialize
 *
 * @param url
 * @param timer
 * @param delim
 */
function ajax_initialize(url, timer, delim) {
	ajax_statsUrl = url;
	ajax_updateTimer = timer;
	ajax_txtDelim = delim;
	if (ajax_useXML)
		ajax_statsParams = '?t=server&f=xml';
	else
		ajax_statsParams = '?t=server&f=txt&h=0';
	ajax_httpRequest = ajax_getHttpRequest();
	ajax_update();
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
	ajax_updateContent(content.split(ajax_txtDelim));
}

/**
 * update page contents from response
 *
 * @param content
 */
function ajax_updateContent(content) {
	for (i = 0; i < ajax_idCount; i++) {
		document.getElementById(ajax_fieldIds[i]).innerHTML = content[i];
	}
	// download-bar
	currentPercentage = content[ajax_idCount];
	document.barImageSpeedDown1.width = currentPercentage * 2;
	document.barImageSpeedDown2.width = (100 - currentPercentage) * 2;
	// upload-bar
	currentPercentage = content[ajax_idCount + 1];
	document.barImageSpeedUp1.width = currentPercentage * 2;
	document.barImageSpeedUp2.width = (100 - currentPercentage) * 2;
	// drivespace-bar
	currentPercentage = content[ajax_idCount + 2];
	document.barImageDriveSpace1.width = (100 - currentPercentage) * 2;
	document.barImageDriveSpace2.width = currentPercentage * 2;
}
