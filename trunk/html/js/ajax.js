/* $Id$ */

// fields
var ajax_debug = false;
var ajax_useXML = false;
var ajax_txtDelim = ";";
var ajax_updateUrl = "stats.php";
var ajax_updateParams = "";
var ajax_updateTimer = 5000;
var ajax_updateState = 0; // 0 = update off; 1 = update on
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
	if (ajax_updateState == 1) {
		try {
		    if (!ajax_httpRequest)
		        ajax_httpRequest = ajax_getHttpRequest();
		    else if (ajax_httpRequest.readyState != 0)
		        ajax_httpRequest.abort();
			ajax_httpRequest.onreadystatechange = ajax_updateCallback;
			ajax_httpRequest.open('GET', ajax_updateUrl + ajax_updateParams, true);
			ajax_httpRequest.send(null);
		} catch (ajaxception) {
			if (ajax_debug)
				alert(ajaxception);
		    ajax_updateState = 0;
		}
	}
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
