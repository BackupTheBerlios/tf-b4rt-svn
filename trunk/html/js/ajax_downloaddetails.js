
// fields
var ajax_transferName = "";
var ajax_fieldIds = new Array(
	"running",
	"speedDown",
	"speedUp",
	"downCurrent",
	"upCurrent",
	"downTotal",
	"upTotal",
	"percentDone",
	"sharing",
	"eta",
	"seeds",
	"peers",
	"cons"
);
var ajax_idCount = ajax_fieldIds.length;

/**
 * ajax_initialize
 *
 * @param name
 * @param url
 * @param timer
 * @param delim
 */
function ajax_initialize(url, timer, delim, name) {
	ajax_statsUrl = url;
	ajax_updateTimer = timer;
	ajax_txtDelim = delim;
	ajax_transferName = name;
	if (ajax_useXML)
		ajax_statsParams = '?t=transfer&f=xml&i=' + name;
	else
		ajax_statsParams = '?t=transfer&f=txt&h=0&i=' + name;
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
	ajax_updateContent(content.split(ajax_txtDelim));
}

/**
 * update page contents from response
 *
 * @param content
 */
function ajax_updateContent(content) {
	// progress-bar
	currentPercentage = content[7];
	document.barImage1.width = currentPercentage * 3.5;
	document.barImage2.width = (100 - currentPercentage) * 3.5;
	// fields
	for (i = 1; i < ajax_idCount; i++) {
		document.getElementById(ajax_fieldIds[i]).innerHTML = content[i];
	}
}
