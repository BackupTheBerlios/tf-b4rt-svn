function loginvalidate() {
	msg = "";
	pass = document.theForm.iamhim.value;
	user = document.theForm.username.value;
	if (user.length < 1)
	{
		msg = msg + "* Username is required\n";
		document.theForm.username.focus();
	}
	if(pass.length<1)
	{
		msg = msg + "* Password is required\n";
		if (user.length > 0)
		{
			document.theForm.iamhim.focus();
		}
	}
	if (msg != "")
	{
		alert("Check the following:\n\n" + msg);
		return false;
	}
}