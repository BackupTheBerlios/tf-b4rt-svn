
// fields
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
var ajax_transferName = "";

/**
 * ajax_initialize
 *
 * @param timer
 * @param delim
 * @param name
 */
function ajax_initialize(timer, delim, name) {;
	ajax_updateTimer = timer;
	ajax_txtDelim = delim;
	ajax_transferName = name;
	if (ajax_useXML)
		ajax_updateParams = '?t=transfer&f=xml&i=' + name;
	else
		ajax_updateParams = '?t=transfer&f=txt&h=0&i=' + name;
	// state
	ajax_updateState = 1;
	// http-request
	ajax_httpRequest = ajax_getHttpRequest();
	// start update-thread
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
	// set timeout
	setTimeout("ajax_update();", ajax_updateTimer);
}

/**
 * update page contents from response
 *
 * @param content
 */
function ajax_updateContent(content) {
	// progress-bar
	currentPercentage = content[7];
	if (currentPercentage == 0)
		document.barImage1.width = 1;
	else
		document.barImage1.width = currentPercentage * 3.5;
	if (currentPercentage == 100)
		document.barImage2.width = 1;
	else
		document.barImage2.width = (100 - currentPercentage) * 3.5;
	// fields
	for (i = 1; i < ajax_idCount; i++) {
		document.getElementById(ajax_fieldIds[i]).innerHTML = content[i];
	}
}
