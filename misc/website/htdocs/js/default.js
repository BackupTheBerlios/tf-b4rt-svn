/* $Id$ */

/*
 * printMail()
 * prints "crawler-friendly" mail-address via js.
 * usage:
 * <script>printMail('foo[AT]bar[DOT]com');</script>
 */
function printMail() {
	if (printMail.arguments.length > 0) {
		document.write(reformatMail(printMail.arguments[0]));
		return true;
	} else {
		return false;
	}
}

/*
 * printMailLink()
 * prints "crawler-friendly" mailto-link via js.
 * optional labeled with second argument
 * usage:
 * <script>printMailLink('foo[AT]bar[DOT]com');</script>
 * <script>printMailLink('foo[AT]bar[DOT]com', "This is a E-Mail-Link");</script>
 */
function printMailLink() {
	var mailLink, mailLabel, mailOut;
	if (printMailLink.arguments.length < 1) {
		return false;
	}
	mailLink = reformatMail(printMailLink.arguments[0]);
	if (printMailLink.arguments.length > 1) {
		mailLabel = reformatMail(printMailLink.arguments[1]);
	} else {
		mailLabel = reformatMail(printMailLink.arguments[0]);
	}
	mailOut = "<a href=\"mailto:" +
				  mailLink + "\"" +
				  " onMouseOver=\"status='Send Mail to: " + mailLabel + "'; return true;\"" +
				  " onMouseOut=\"status=''; return true;\"" +
				  " title=\"Send Mail to " + mailLabel + "\"" +
				  ">" +
				  mailLabel + "</a>"
	document.write(mailOut);
	return true;
}

/*
 * reformatMail()
 * INTERNAL: reformat mail-string for function printMailLink
 */
function reformatMail() {
	var subMailStr = reformatMail.arguments[0];
	subMailStr = subMailStr.replace(/\[AT\]/g, "@");
	subMailStr = subMailStr.replace(/\[DOT\]/g, ".");
	return subMailStr;
}
