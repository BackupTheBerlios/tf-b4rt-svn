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

/*
 * showPicture()
 */
function showPicture(pic) {
	var dim = 'width=830,height=630';
	var win = open('about:blank',"pic",'scrollbars=1,resizable=1,width=880,height=660');
	win.document.open();
	with (win.document) {
		write('<html><head><title>' + pic + '</title>');
		write('<script>');
		var daScript = '/* inline-js */';
		daScript += ' var offsetWidth = 40;';
		daScript += ' var offsetHeight = 50;';
		daScript += ' var fullWidth, fullHeight, fullyloaded;';
		daScript += ' function simIEsizedownInit() {';
		daScript += '	fullyloaded = 1;';
		daScript += '	fullWidth = document.images[0].width;';
		daScript += '	fullHeight = document.images[0].height;';
		daScript += '	simIEsizedown();';
		daScript += ' } ';
		daScript += ' function simIEsizedown() {';
		daScript += '	if (fullyloaded == 1) {';
		daScript += '		document.images[0].width = fullWidth;';
		daScript += '		document.images[0].height = fullHeight;';
		daScript += '		var recentWidth = document.images[0].width;';
		daScript += '		var recentHeight = document.images[0].height;';
		daScript += '		var viewportWidth = document.body.offsetWidth;';
		daScript += '		var viewportHeight = document.body.offsetHeight;';
		daScript += '		var ratioWidth = viewportWidth / (recentWidth + offsetWidth);';
		daScript += '		var ratioHeight = viewportHeight / (recentHeight + offsetHeight);';
		daScript += '		var offsetWidthEff, offsetHeightEff, ratioRes;';
		daScript += '		if(ratioWidth < ratioHeight) {';
		daScript += '			ratioRes = ratioWidth;';
		daScript += '		} else {';
		daScript += '			ratioRes = ratioHeight;';
		daScript += '		}';
		daScript += '		if(ratioRes < 1) {';
		daScript += '			var newWidth = Math.floor(document.images[0].width * ratioRes * 0.95);';
		daScript += '			var newHeight = Math.floor(document.images[0].height * ratioRes * 0.95);';
		daScript += '			document.images[0].width = newWidth;';
		daScript += '			document.images[0].height = newHeight;';
		daScript += '		}';
		daScript += '	} ';
		daScript += ' }';
		write(daScript);
		write('</script>');
		write('</head>');
		write('<body bgcolor="#2f2f2e" onclick="self.close();" onload=" simIEsizedownInit(); self.focus();" onresize="simIEsizedown()">');
		write('<center><table align="center" width="100%" height="100%" cellspacing="0" cellpadding="0" border="0"><tr>');
		write('<td align="center"><img src="' + pic + '"></td></tr></table></center>');
		write('</body></html>');
	}
	win.document.close();
	return false;
}
