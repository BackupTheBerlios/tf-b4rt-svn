
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

/**
 * ajax_initialize
 *
 * @param url
 * @param timer
 * @param delim
 */
function ajax_initialize(url, timer, delim) {
	ajax_statsUrl = url;
	if (ajax_useXML)
		ajax_statsUrl += '?t=server&f=xml';
	else
		ajax_statsUrl += '?t=server&f=txt&h=0';
	ajax_updateTimer = timer;
	ajax_txtDelim = delim;
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
}
