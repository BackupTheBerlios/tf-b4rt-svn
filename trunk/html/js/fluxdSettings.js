
function validateSettings() {
	var rtnValue = true;
	var msg = "";
	if (isNumber(document.theForm.fluxd_Qmgr_maxTotalTorrents.value) == false) {
		msg = msg + "* Max Total Threads must be a valid number.\n";
		document.theForm.fluxd_Qmgr_maxTotalTorrents.focus();
	}
	if (isNumber(document.theForm.fluxd_Qmgr_maxUserTorrents.value) == false) {
		msg = msg + "* Max User Threads must be a valid number.\n";
		document.theForm.fluxd_Qmgr_maxUserTorrents.focus();
	}
	if (isNumber(document.theForm.fluxd_Clientmaint_interval.value) == false) {
		msg = msg + "* Clientmaint Interval must be a valid number.\n";
		document.theForm.fluxd_Clientmaint_interval.focus();
	}
	if (isNumber(document.theForm.fluxd_Fluxinet_port.value) == false ) {
		msg = msg + "* Fluxinet port must be a valid number.\n";
		document.theForm.fluxd_Fluxinet_port.focus();
	}
	if (msg != "") {
		rtnValue = false;
		alert("Please check the following:\n\n" + msg);
	}
	return rtnValue;
}

function isNumber(sText) {
	var ValidChars = "0123456789.";
	var IsNumber = true;
	var Char;
	for (i = 0; i < sText.length && IsNumber == true; i++) {
		Char = sText.charAt(i);
		if (ValidChars.indexOf(Char) == -1) {
			IsNumber = false;
		}
	}
	return IsNumber;
}
