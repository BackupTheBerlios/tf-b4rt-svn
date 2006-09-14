
function validateSettings() {
	var rtnValue = true;
	var msg = "";
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
	if ((isNumber(document.theForm.minport.value) == false) || (isNumber(document.theForm.maxport.value) == false)) {
		msg = msg + "* Port Range must have valid numbers.\n";
		document.theForm.minport.focus();
	}
	if (isNumber(document.theForm.rerequest_interval.value) == false) {
		msg = msg + "* Rerequest Interval must have a valid number.\n";
		document.theForm.rerequest_interval.focus();
	}
	if (document.theForm.rerequest_interval.value < 10) {
		msg = msg + "* Rerequest Interval must 10 or greater.\n";
		document.theForm.rerequest_interval.focus();
	}
	if (isNumber(document.theForm.days_to_keep.value) == false) {
		msg = msg + "* Days to keep Audit Actions must be a valid number.\n";
		document.theForm.days_to_keep.focus();
	}
	if (isNumber(document.theForm.minutes_to_keep.value) == false) {
		msg = msg + "* Minutes to keep user online must be a valid number.\n";
		document.theForm.minutes_to_keep.focus();
	}
	if (isNumber(document.theForm.rss_cache_min.value) == false) {
		msg = msg + "* Minutes to Cache RSS Feeds must be a valid number.\n";
		document.theForm.rss_cache_min.focus();
	}
	if (isNumber(document.theForm.page_refresh.value) == false) {
		msg = msg + "* Page Refresh must be a valid number.\n";
		document.theForm.page_refresh.focus();
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
	// maxcons
	if (isNumber(document.theForm.maxcons.value) == false) {
		msg = msg + "* Max Cons must be a valid number.\n" ;
	}
	// Specific save path
	if (isNumber(document.theForm.maxdepth.value) == false) {
		msg = msg + "* Max Depth must be a valid number.\n" ;
	}
	if (msg != "") {
		rtnValue = false;
		alert("Please check the following:\n\n" + msg);
	}
	return rtnValue;
}

function isNumber(sText) {
	var ValidChars = "0123456789";
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