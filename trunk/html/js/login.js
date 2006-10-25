/* $Id$ */
function loginSubmit() {
	msg = "";
	pass = document.theForm.iamhim.value;
	user = document.theForm.username.value;
	if (user.length < 1) {
		msg = msg + "* Username is required\n";
		document.theForm.username.focus();
	}
	if (pass.length <1 ) {
		msg = msg + "* Password is required\n";
		if (user.length > 0)
		{
			document.theForm.iamhim.focus();
		}
	}
	if (msg != "") {
		alert("Check the following:\n\n" + msg);
		return false;
	}
	var loginDivForm = document.getElementById("login_form");
	if (loginDivForm != null) {
		if (navigator.appName == "Microsoft Internet Explorer" && navigator.userAgent.indexOf("MSIE 7") == -1)
			loginDivForm.style.top = document.documentElement.scrollTop;
		loginDivForm.style.display = "none";
	}
	var loginDivAction = document.getElementById("login_in_progress");
	if (loginDivAction != null) {
		if (navigator.appName == "Microsoft Internet Explorer" && navigator.userAgent.indexOf("MSIE 7") == -1)
			loginDivAction.style.top = document.documentElement.scrollTop;
		loginDivAction.style.display = "block";
	}
}