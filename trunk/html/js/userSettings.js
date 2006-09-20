
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
function validateProfile(_USERIDREQUIRED, _PASSWORDLENGTH, _PASSWORDNOTMATCH, _PLEASECHECKFOLLOWING) {
	var msg = ""
	if (theForm.newUser.value == "") {
		msg = msg + "* " + _USERIDREQUIRED + "\n";
		theForm.newUser.focus();
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
		msg = msg + "* " + _PASSWORDLENGTH + "\n";
		theForm.pass1.focus();
	}
	if (msg != "") {
		alert(_PLEASECHECKFOLLOWING + ":\n\n" + msg);
		return false;
	} else {
		return true;
	}
}
