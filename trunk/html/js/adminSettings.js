/* $Id$ */

/**
 * validateSettings
 */
function validateSettings(section) {
	var msg = "";
	switch (section) {
		case 'dir':
			break;		
		case 'fluxd':
			if (isNumber(document.theForm.fluxd_Qmgr_interval.value) == false ) {
				msg = msg + "* Qmgr Intervall must be a valid number.\n";
				document.theForm.fluxd_Qmgr_interval.focus();
			}
			if (isNumber(document.theForm.fluxd_Qmgr_maxTotalTorrents.value) == false) {
				msg = msg + "* Max Total Threads must be a valid number.\n";
				document.theForm.fluxd_Qmgr_maxTotalTorrents.focus();
			}
			if (isNumber(document.theForm.fluxd_Qmgr_maxUserTorrents.value) == false) {
				msg = msg + "* Max User Threads must be a valid number.\n";
				document.theForm.fluxd_Qmgr_maxUserTorrents.focus();
			}
			if (isNumber(document.theForm.fluxd_Fluxinet_port.value) == false ) {
				msg = msg + "* Fluxinet port must be a valid number.\n";
				document.theForm.fluxd_Fluxinet_port.focus();
			}
			if (isNumber(document.theForm.fluxd_Watch_interval.value) == false ) {
				msg = msg + "* Watch Intervall must be a valid number.\n";
				document.theForm.fluxd_Watch_interval.focus();
			}
			if (isNumber(document.theForm.fluxd_Clientmaint_interval.value) == false) {
				msg = msg + "* Clientmaint Intervall must be a valid number.\n";
				document.theForm.fluxd_Clientmaint_interval.focus();
			}
			if (isNumber(document.theForm.fluxd_Trigger_interval.value) == false ) {
				msg = msg + "* Trigger Intervall must be a valid number.\n";
				document.theForm.fluxd_Trigger_interval.focus();
			}
			break;
		case 'fluxd_Rssad_filter_1':
			if (document.theForm.filtername.value.length < 1) {
				msg = msg + "* Enter a Filtername.\n";
				document.theForm.filtername.focus();
			}
			break;			
		case 'index':
			if (isNumber(document.theForm.page_refresh.value) == false ) {
				msg = msg + "* Page Refresh Intervalll must be a valid number.\n";
				document.theForm.page_refresh.focus();
			}
			if (isNumber(document.theForm.index_ajax_update.value) == false ) {
				msg = msg + "* AJAX Update Intervall must be a valid number.\n";
				document.theForm.index_ajax_update.focus();
			}
			if (isNumber(document.theForm.hack_multiupload_rows.value) == false ) {
				msg = msg + "* multi-upload rows must be a valid number.\n";
				document.theForm.hack_multiupload_rows.focus();
			}
			if (isNumber(document.theForm.bandwidth_up.value) == false ) {
				msg = msg + "* Bandwidth Up must be a valid number.\n";
				document.theForm.bandwidth_up.focus();
			}
			if (isNumber(document.theForm.bandwidth_down.value) == false ) {
				msg = msg + "* Bandwidth Down must be a valid number.\n";
				document.theForm.bandwidth_down.focus();
			}
			break;
		case 'server':
			bwd = document.theForm.path_incoming.value;
			//if (bwd.indexOf('/') == 0) {
			//	msg = msg + "* Incoming-PATH cannot be a absolute path. Incoming is a sub-dir of PATH.\n";
			if (bwd.indexOf('/') != -1) {
				msg = msg + "* Incoming-dir can only be a subdir of PATH and not contain Slashes. (specify relative Path, no subdirs).\n";
				document.theForm.path_incoming.focus();
			}
			break;
		case 'startpop':
			if (isNumber(document.theForm.maxdepth.value) == false) {
				msg = msg + "* Max Depth must be a valid number.\n" ;
			}
			break;
		case 'stats':
			break;
		case 'transfer':
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
			if (isNumber(document.theForm.rerequest_interval.value) == false) {
				msg = msg + "* Rerequest Intervall must have a valid number.\n";
				document.theForm.rerequest_interval.focus();
			}
			if (document.theForm.rerequest_interval.value < 10) {
				msg = msg + "* Rerequest Intervall must be 10 or greater.\n";
				document.theForm.rerequest_interval.focus();
			}
			if (isNumber(document.theForm.sharekill.value) == false) {
				msg = msg + "* Keep seeding until Sharing % must be a valid number.\n";
				document.theForm.sharekill.focus();
			}
			if (isNumber(document.theForm.wget_limit_retries.value) == false) {
				msg = msg + "* Limit Number of Retries must be a valid number.\n";
				document.theForm.wget_limit_retries.focus();
			}
			break;
		case 'webapp':
			if (isNumber(document.theForm.details_update.value) == false) {
				msg = msg + "* Download-Details Update Intervall must be a valid number.\n";
				document.theForm.details_update.focus();
			}
			if (isNumber(document.theForm.servermon_update.value) == false) {
				msg = msg + "* Server Monitor Update Intervall must be a valid number.\n";
				document.theForm.servermon_update.focus();
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
			break;
		case 'xfer':
			if (isNumber(document.theForm.xfer_total.value) == false) {
				msg = msg + "* xfer total must be a valid number.\n";
				document.theForm.xfer_total.focus();
			}
			if (isNumber(document.theForm.xfer_month.value) == false) {
				msg = msg + "* xfer month must be a valid number.\n";
				document.theForm.xfer_month.focus();
			}
			if (isNumber(document.theForm.xfer_week.value) == false) {
				msg = msg + "* xfer week must be a valid number.\n";
				document.theForm.xfer_week.focus();
			}
			if (isNumber(document.theForm.xfer_day.value) == false) {
				msg = msg + "* xfer day must be a valid number.\n";
				document.theForm.xfer_day.focus();
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
