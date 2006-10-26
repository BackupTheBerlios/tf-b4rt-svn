/* $Id$ */
var actionInProgress = false;
var varRefresh;
function initRefresh(refresh) {
	varRefresh = refresh;
	setTimeout("updateRefresh();", 1000);
}
function updateRefresh() {
	varRefresh--;
	if (varRefresh >= 0) {
	    document.getElementById("span_refresh").innerHTML = String(varRefresh);
	    setTimeout("updateRefresh();", 1000);
	}
}
function bulkCheck(thisIn) {
	ajax_updateState = 0;
	var form = thisIn.form, i = 0;
	for(i = 0; i < form.length; i++) {
		if (form[i].type == 'checkbox' && form[i].name != 'bulkBox' && form[i].disabled == false) {
			form[i].checked = thisIn.checked;
		}
	}
}
function ShowDetails(name_file, width, height) {
	window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=450,height=290">')
}
function openServerMonitor() {
	window.open('index.php?iid=servermon','_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=470,height=220')
}
function StartTorrent(name_file) {
	if (actionInProgress) {
		actionRequestError();
		return false;
	}
	window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=800,height=700')
}
function actionClick(showlabel) {
	if (actionInProgress) {
		actionRequestError();
		return false;
	}
	actionRequest(showlabel);
	return true;
}
function actionConfirm(question) {
	if (actionInProgress) {
		actionRequestError();
		return false;
	} else {
		var confirmResult = confirm(question);
		if (confirmResult)
			actionRequest(true);
		return confirmResult;
	}
}
function actionSubmit() {
	if (actionInProgress) {
		actionRequestError();
		return false;
	}
	actionRequest(true);
	return true;
}
function actionRequest(showlabel) {
	actionInProgress = true;
	ajax_updateState = 0;
	if (showlabel) {
		var label = document.getElementById("action_in_progress");
		if (label != null) {
			if (navigator.appName == "Microsoft Internet Explorer" && navigator.userAgent.indexOf("MSIE 7") == -1)
				label.style.top = document.documentElement.scrollTop;
			label.style.display = actionInProgress ? "block" : "none";
		}
	}
}
function actionRequestError() {
	alert("Another Request in progress...");
}