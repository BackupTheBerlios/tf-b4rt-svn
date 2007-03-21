/* $Id$ */

/**
 * validateUser
 */
function validateUser(_USERIDREQUIRED, _PASSWORDLENGTH, _PASSWORDNOTMATCH, _PLEASECHECKFOLLOWING) {
	var msg = ""
	if (theForm.user_id.value == "") {
		msg = msg + "* " + _USERIDREQUIRED + "\n";
		theForm.user_id.focus();
	}
	if (theForm.pass1.value != "" || theForm.pass2.value != "") {
		if (theForm.pass1.value.length <= 5 || theForm.pass2.value.length <= 5) {
			msg = msg + "* " + _PASSWORDLENGTH + "\n";
			theForm.pass1.focus();
		}
		if (theForm.pass1.value != theForm.pass2.value) {
			msg = msg + "* " + _PASSWORDNOTMATCH + "\n";
			theForm.pass1.value = "";
			theForm.pass2.value = "";
			theForm.pass1.focus();
		}
	}
	if (msg != "") {
		alert(_PLEASECHECKFOLLOWING + ":\n\n" + msg);
		return false;
	} else {
		return true;
	}
}

/**
 * validateProfile
 */
function validateProfile(isCreate, _USERIDREQUIRED, _PASSWORDLENGTH, _PASSWORDNOTMATCH, _PLEASECHECKFOLLOWING) {
	var msg = ""
	if (isCreate == 1) {
		if (theForm.newUser.value == "") {
			msg = msg + "* " + _USERIDREQUIRED + "\n";
			theForm.newUser.focus();
		}
	}
	if (theForm.pass1.value != "" || theForm.pass2.value != "") {
		if (theForm.pass1.value.length <= 5 || theForm.pass2.value.length <= 5) {
			msg = msg + "* " + _PASSWORDLENGTH + "\n";
			theForm.pass1.focus();
		}
		if (theForm.pass1.value != theForm.pass2.value) {
			msg = msg + "* " + _PASSWORDNOTMATCH + "\n";
			theForm.pass1.value = "";
			theForm.pass2.value = "";
			theForm.pass1.focus();
		}
	} else {
		if (isCreate == 1) {
			msg = msg + "* " + _PASSWORDLENGTH + "\n";
			theForm.pass1.focus();
		}
	}
	if (msg != "") {
		alert(_PLEASECHECKFOLLOWING + ":\n\n" + msg);
		return false;
	} else {
		return true;
	}
}

/**
 * validateSettings
 */
function validateSettings() {
	var msg = "";
	if (isNumber(document.settingsForm.page_refresh.value) == false ) {
		msg = msg + "* Page Refresh Intervalll must be a valid number.\n";
		document.settingsForm.page_refresh.focus();
	}
	if (isNumber(document.settingsForm.index_ajax_update.value) == false ) {
		msg = msg + "* AJAX Update Intervall must be a valid number.\n";
		document.settingsForm.index_ajax_update.focus();
	}
	if (isNumber(document.settingsForm.transferStatsUpdate.value) == false) {
		msg = msg + "* Download-Details Update Intervall must be a valid number.\n";
		document.settingsForm.transferStatsUpdate.focus();
	}
	if (isNumber(document.settingsForm.servermon_update.value) == false) {
		msg = msg + "* Server Monitor Update Intervall must be a valid number.\n";
		document.settingsForm.servermon_update.focus();
	}
	if (msg != "") {
		alert("Please check the following:\n\n" + msg);
		return false;
	} else {
		return true;
	}
}

/**
 * isNumber
 */
function isNumber(sText) {
	var ValidChars = "0123456789";
	var IsNumber = true;
	var Char;
	for (i = 0; i < sText.length && IsNumber == true; i++) {
		Char = sText.charAt(i);
		if (ValidChars.indexOf(Char) == -1)
			IsNumber = false;
	}
	return IsNumber;
}
