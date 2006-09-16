
// fields
var debug = true;
var useXML = false;
var transferName = "";
var txtDelim = ";";
var statsUrl = "";
var updateTimer = 5000;
var httpRequest = false;
var fieldIds = new Array(
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
var idCount = fieldIds.length;

/**
 * initialize
 *
 * @param name
 * @param url
 * @param timer
 * @param delim
 */
function initialize(name, url, timer, delim) {
	transferName = name;
	statsUrl = url;
	updateTimer = timer;
	txtDelim = delim;
	if (useXML)
		statsUrl += '?t=transfer&f=xml&i=' + name;
	else
		statsUrl += '?t=transfer&f=txt&h=0&i=' + name;
	httpRequest = getHttpRequest();
	setTimeout("update();", updateTimer);
}

/**
 * get http-request-instance
 */
function getHttpRequest() {
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
		if (debug)
			alert('Error : cant create XMLHTTP-instance');
		return false;
	}
	return _httpRequest;
}

/**
 * update
 */
function update() {
	if (window.ActiveXObject) // IE seems to dispose this object.. recreate
		httpRequest = getHttpRequest();
	// trigger asynch http-request
	httpRequest.onreadystatechange = updateCallback;
	httpRequest.open('GET', statsUrl, true);
	httpRequest.send(null);
	// set timeout
	setTimeout("update();", updateTimer);
}

/**
 * update-callback
 */
function updateCallback() {
	if (httpRequest.readyState == 4) {
		if (httpRequest.status == 200) {
			if (useXML)
				processXML(httpRequest.responseXML);
			else
				processText(httpRequest.responseText);
		} else {
			if (debug)
				alert('Error in Request :'+httpRequest.status);
		}
	}
}

/**
 * process XML-response
 *
 * @param content
 */
function processXML(content) {
	alert(content);
}

/**
 * process text-response
 *
 * @param content
 */
function processText(content) {
	updateContent(content.split(txtDelim));
}

/**
 * update page contents from response
 *
 * @param content
 */
function updateContent(content) {
	for (i = 1; i < idCount; i++) {
		document.getElementById(fieldIds[i]).innerHTML = content[i];
	}
}
