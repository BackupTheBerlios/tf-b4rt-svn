
// fields
var ajax_debug = true;
var ajax_useXML = false;
var ajax_txtDelim = ";";
var ajax_statsUrl = "";
var ajax_statsParams = "";
var ajax_updateTimer = 5000;
var ajax_httpRequest = false;

/**
 * get http-request-instance
 */
function ajax_getHttpRequest() {
	_httpRequest = false;
	if (window.XMLHttpRequest) { // Mozilla, Safari,...
		_httpRequest = new XMLHttpRequest();
		if (_httpRequest.overrideMimeType)
			_httpRequest.overrideMimeType('text/xml');
	} else if (window.ActiveXObject) { // IE
		try {
			_httpRequest = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				_httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {}
		}
	}
	if (!_httpRequest) {
		if (ajax_debug)
			alert('Error : cant create XMLHTTP-instance');
		return false;
	}
	return _httpRequest;
}

/**
 * ajax_update
 */
function ajax_update() {
	if (window.ActiveXObject) // IE seems to dispose this object.. recreate
		ajax_httpRequest = ajax_getHttpRequest();
	// trigger asynch http-request
	ajax_httpRequest.onreadystatechange = ajax_updateCallback;
	ajax_httpRequest.open('GET', ajax_statsUrl + ajax_statsParams, true);
	ajax_httpRequest.send(null);
	// set timeout
	setTimeout("ajax_update();", ajax_updateTimer);
}

/**
 * update-callback
 */
function ajax_updateCallback() {
	if (ajax_httpRequest.readyState == 4) {
		if (ajax_httpRequest.status == 200) {
			if (ajax_useXML)
				ajax_processXML(ajax_httpRequest.responseXML);
			else
				ajax_processText(ajax_httpRequest.responseText);
		} else {
			if (ajax_debug)
				alert('Error in Request :' + ajax_httpRequest.status);
		}
	}
}
