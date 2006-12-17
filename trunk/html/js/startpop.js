/* $Id$ */

var setQueue = 0;

/**
 * StartTorrent
 */
function StartTransfer() {
	if (setQueue == 1) {
		var qbox = document.getElementById("queuebox");
		if (qbox.checked)
			document.getElementById("queue").value = "true";
		else
			document.getElementById("queue").value = "false";
	}
	if (ValidateValues())
		document.theForm.submit();
}

/**
 * ValidateValues
 */
function ValidateValues() {
	var msg = "";
	if (isNumber(document.theForm.rate.value) == false) {
		msg = msg + "* Max Upload Rate must be a valid number.\n";
		document.theForm.rate.focus();
	}
	if (isNumber(document.theForm.drate.value) == false) {
		msg = msg + "* Max Download Rate must be a valid number.\n";
		document.theForm.drate.focus();
	}
	if (isNumber(document.theForm.maxuploads.value) == false) {
		msg = msg + "* Max # Uploads must be a valid number.\n";
		document.theForm.maxuploads.focus();
	}
	if ((isNumber(document.theForm.minport.value) == false) || (isNumber(document.theForm.maxport.value) == false)) {
		msg = msg + "* Port Range must have valid numbers.\n";
		document.theForm.minport.focus();
	}
	if (isNumber(document.theForm.rerequest.value) == false) {
		msg = msg + "* Rerequest Intervall must be a valid number.\n";
		document.theForm.rerequest.focus();
	}
	if (document.theForm.rerequest.value < 10) {
		msg = msg + "* Rerequest Intervall must be 10 or greater.\n";
		document.theForm.rerequest.focus();
	}
	if (isNumber(document.theForm.sharekill.value) == false) {
		msg = msg + "* Keep seeding until Sharing % must be a valid number.\n";
		document.theForm.sharekill.focus();
	}
	if ((document.theForm.maxport.value > 65535) || (document.theForm.minport.value > 65535)) {
		msg = msg + "* Port can not be higher than 65535.\n";
		document.theForm.minport.focus();
	}
	if ((document.theForm.maxport.value < 0) || (document.theForm.minport.value < 0)) {
		msg = msg + "* Can not have a negative number for port value.\n";
		document.theForm.minport.focus();
	}
	if (document.theForm.maxport.value < document.theForm.minport.value) {
		msg = msg + "* Port Range is not valid.\n";
		document.theForm.minport.focus();
	}
	if (isNumber(document.theForm.maxcons.value) == false) {
		msg = msg + "* Max # Connections must be a valid number.\n";
		document.theForm.maxcons.focus();
	}
	if (msg != "") {
		alert("Please check the following:\n\n" + msg);
		return false;
	} else {
		return true;
	}
}

/**
 * CheckShareState
 */
function CheckShareState() {
	var obj = document.getElementById('sharekiller');
	if (document.theForm.runtime.value == "True") {
		obj.style.visibility = "hidden";
	} else {
		obj.style.visibility = "visible";
	}
}

/**
 * isNumber
 */
function isNumber(sText) {
	var ValidChars = "-0123456789";
	var IsNumber = true;
	var Char;
	for (i = 0; i < sText.length && IsNumber == true; i++) {
		Char = sText.charAt(i);
		if (ValidChars.indexOf(Char) == -1)
			IsNumber = false;
	}
	return IsNumber;
}
