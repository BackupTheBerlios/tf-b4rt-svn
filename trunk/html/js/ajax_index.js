
// fields

var ajax_fieldIds_GLS = new Array(
	"g_speedDown",
	"g_speedUp",
	"g_speedTotal",
	"g_connections",
	"g_freeSpace",
	"g_loadavg"
);
var ajax_idCount_GLS = ajax_fieldIds_GLS.length;

var ajax_fieldIds_Bottom = new Array(
	"speedDown",
	"speedUp",
	"speedTotal",
	"connections",
	"freeSpace",
	"loadavg"
);
var ajax_idCount_Bottom = ajax_fieldIds_Bottom.length;

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
	setTimeout("ajax_update();", ajax_updateTimer);
}

/**
 * update page contents from response
 *
 * @param content
 */
function ajax_updateContent(content) {
	// good looking stats
	for (i = 0; i < ajax_idCount_GLS; i++) {
		document.getElementById(ajax_fieldIds_GLS[i]).innerHTML = content[i];
	}
	// bottom stats
	for (i = 0; i < ajax_idCount_Bottom; i++) {
		document.getElementById(ajax_fieldIds_Bottom[i]).innerHTML = content[i];
	}
}
