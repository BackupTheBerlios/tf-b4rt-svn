/* $Id$ */

/**
 * mytrim
 */
function mytrim(value) {
	var trimmedVal = "";
	for (var i=0; i<value.length; i++) {
		if (value.charCodeAt(i) != 32)
			trimmedVal = trimmedVal + value.charAt(i);
	}
	return trimmedVal;
}

/**
 * addWatchEntry
 */
function addWatchEntry () {
	var wu = mytrim(document.theForm.watch_user.value);
    var wd = mytrim(document.theForm.watch_dir.value);
    if ((wu != "") && (wd != "")) {
	    var liststr = document.theForm.fluxd_Watch_jobs;
	    var list = document.theForm.watch_jobs;
	    var newentry = document.createElement("option");
	    newentry.text = wu + ":" + wd;
	    newentry.value = newentry.text;
	    document.theForm.watch_user.value = "";
		document.theForm.watch_dir.value = "";
	    if (navigator.appName == "Netscape")
	    	list.add(newentry, null);
	    else
	    	list.add(newentry);
	    if (liststr.value == "")
	    	liststr.value = newentry.text;
	    else
	    	liststr.value = liststr.value + ";" + newentry.text;
    } else {
		alert("Please enter Directory + Username first!");
	}
}

/**
 * removeWatchEntry
 */
function removeWatchEntry() {
	if (document.theForm.watch_jobs.selectedIndex != -1) {
		var liststr = document.theForm.fluxd_Watch_jobs;
		document.theForm.watch_jobs.remove(document.theForm.watch_jobs.selectedIndex);
		var newValue = "";
		for (var j = 0; j < document.theForm.watch_jobs.options.length; j++) {
            if (j > 0)
                newValue += ";";
		    newValue += mytrim(document.theForm.watch_jobs.options[j].text);
		}
		liststr.value = mytrim(newValue);
	} else {
		alert("Please select a Job first!");
	}
}