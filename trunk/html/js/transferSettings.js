/* $Id$ */

/**
 * validateSettings
 */
function validateSettings(type) {
	var msg = "";
	switch (type) {

		case 'torrent':
			if (isNumber(document.theForm.max_upload_rate.value) == false) {
				msg = msg + "* Max Upload Rate must be a valid number.\n";
				document.theForm.max_upload_rate.focus();
			}
			if (isNumber(document.theForm.max_download_rate.value) == false) {
				msg = msg + "* Max Download Rate must be a valid number.\n";
				document.theForm.max_download_rate.focus();
			}
			if (isNumber(document.theForm.max_uploads.value) == false) {
				msg = msg + "* Max # Uploads must be a valid number.\n";
				document.theForm.max_uploads.focus();
			}
			if (isNumber(document.theForm.maxcons.value) == false) {
				msg = msg + "* Max Cons must be a valid number.\n" ;
			}
			if ((isNumber(document.theForm.minport.value) == false) || (isNumber(document.theForm.maxport.value) == false)) {
				msg = msg + "* Port Range must have valid numbers.\n";
				document.theForm.minport.focus();
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
			if (isNumber(document.theForm.sharekill.value) == false) {
				msg = msg + "* Keep seeding until Sharing % must be a valid number.\n";
				document.theForm.sharekill.focus();
			}
			break;

		case 'wget':
			if (isNumber(document.theForm.wget_limit_rate.value) == false) {
				msg = msg + "* wget Download Rate must be a valid number.\n";
				document.theForm.wget_limit_rate.focus();
			}
			if (isNumber(document.theForm.wget_limit_retries.value) == false) {
				msg = msg + "* wget Limit Number of Retries must be a valid number.\n";
				document.theForm.wget_limit_retries.focus();
			}
			break;

		case 'nzb':
			if (isNumber(document.theForm.nzbperl_rate.value) == false) {
				msg = msg + "* nzbperl Download Rate must be a valid number.\n";
				document.theForm.nzbperl_rate.focus();
			}
			if (isNumber(document.theForm.nzbperl_conn.value) == false) {
				msg = msg + "* nzbperl Connections must be a valid number.\n";
				document.theForm.nzbperl_conn.focus();
			}
			if (isNumber(document.theForm.nzbperl_threads.value) == false) {
				msg = msg + "* nzbperl Threads must be a valid number.\n";
				document.theForm.nzbperl_threads.focus();
			}
			break;

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
