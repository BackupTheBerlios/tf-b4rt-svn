/* $Id$ */

/**
 * old meth
 */
function mytrim(value) {
	var trimmedVal = "";
	for (var i=0; i<value.length; i++) {
		if (value.charCodeAt(i) != 32) {
			trimmedVal = trimmedVal + value.charAt(i);
		}
	}
	return trimmedVal;
}

/**
 * old meth
 */
function addDirEntry () {
    var resliststr = document.theForm.dir_restricted;
    var reslist = document.theForm.resdirlist;
    var newentry = document.createElement("option");
    newentry.text = document.theForm.resdirentry.value;
    newentry.text = mytrim(newentry.text);
 	if( newentry.text != "") {
        document.theForm.resdirentry.value = "";
        newentry.value = reslist.length;
        if (navigator.appName == "Netscape") {
        	reslist.add(newentry, null);
        } else {
        	reslist.add(newentry);
        }
        if (resliststr.value == "") {
        	resliststr.value = newentry.text;
        } else {
        	resliststr.value = resliststr.value + ":" + newentry.text;
        }
  	} else {
		alert("Please enter a Entry first!");
	}
}

/**
 * old meth
 */
function removeDirEntry() {
	var resliststr = document.theForm.dir_restricted;
	if (document.theForm.resdirlist.selectedIndex != -1) {
		document.theForm.resdirlist.remove(document.theForm.resdirlist.selectedIndex);
		var newValue = "";
		for (var j = 0; j < document.theForm.resdirlist.options.length; j++) {
            if (j > 0) {
                newValue += ":";
            }
		    newValue += mytrim(document.theForm.resdirlist.options[j].text);
		}
		resliststr.value = mytrim(newValue);
	} else {
		alert("Please select an entry first!");
	}
}
