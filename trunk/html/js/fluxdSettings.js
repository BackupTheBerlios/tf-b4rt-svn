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
 * lrtrim
 */
function lrtrim(value) {
	var l, r;
	for (l = 0;                l < value.length && value.charCodeAt(l) == 32; l++);
	for (r = value.length - 1; r > l            && value.charCodeAt(r) == 32; r--);
	return value.substring(l, r + 1);
}

/**
 * addWatchEntry
 */
function addWatchEntry () {
	var wu = lrtrim(document.theForm.watch_user.options[document.theForm.watch_user.selectedIndex].value);
    var wd = lrtrim(document.theForm.watch_dir.value);
    if ((wu != "") && (wd != "")) {
		if (wd.indexOf('/') != 0) {
			alert("Directory must be absolute !");
			return false;
		}
	    var liststr = document.theForm.fluxd_Watch_jobs;
	    var list = document.theForm.watch_jobs;
	    for (var i = 0; i < document.theForm.watch_jobs.options.length; i++) {
	    	if ((lrtrim(document.theForm.watch_jobs.options[i].value)) == (wu + ":" + wd)) {
	    		alert("Job already exists");
	    		return false;
	    	}
	    	if ((lrtrim(document.theForm.watch_jobs.options[i].value)) == (wu + ":" + wd + "/")) {
	    		alert("Job already exists");
	    		return false;
	    	}	    	
	    }	    
	    var newentry = document.createElement("option");
	    newentry.text = wu + ":" + wd;
	    newentry.value = newentry.text;
	    //document.theForm.watch_user.value = "";
		document.theForm.watch_dir.value = "";
	    if (navigator.appName == "Netscape")
	    	list.add(newentry, null);
	    else
	    	list.add(newentry);
	    if (liststr.value == "")
	    	liststr.value = newentry.value;
	    else
	    	liststr.value = liststr.value + ";" + newentry.value;
    } else {
		alert("Please select an Username and enter a Directory first!");
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
		    newValue += lrtrim(document.theForm.watch_jobs.options[j].value);
		}
		liststr.value = lrtrim(newValue);
	} else {
		alert("Please select a Job first!");
	}
}

/**
 * addRssadFilterEntry()
 */
function addRssadFilterEntry() {
    var filter = lrtrim(document.theForm.rssad_filter_entry.value);
    if (filter != "") {
	    for (var i = 0; i < document.theForm.rssad_filters.options.length; i++) {
	    	if ((lrtrim(document.theForm.rssad_filters.options[i].value)) == filter) {
	    		alert("Filter already exists");
	    		return false;
	    	}
	    }
	    var liststr = document.theForm.rssad_filtercontent;
	    var list = document.theForm.rssad_filters;	    
	    var newentry = document.createElement("option");
	    newentry.text = filter;
	    newentry.value = newentry.text;
		document.theForm.rssad_filter_entry.value = "";
	    if (navigator.appName == "Netscape")
	    	list.add(newentry, null);
	    else
	    	list.add(newentry);
	    if (liststr.value == "")
	    	liststr.value = newentry.value;
	    else
	    	liststr.value = liststr.value + "\n" + newentry.value;
    } else {
		alert("Please enter a Filter.");
	}
}

/**
 * removeRssadFilterEntry()
 */
function removeRssadFilterEntry() {
	if (document.theForm.rssad_filters.selectedIndex != -1) {
		var liststr = document.theForm.rssad_filtercontent;
		document.theForm.rssad_filters.remove(document.theForm.rssad_filters.selectedIndex);
		var newValue = "";
		for (var j = 0; j < document.theForm.rssad_filters.options.length; j++) {
            if (j > 0)
                newValue += "\n";
		    newValue += lrtrim(document.theForm.rssad_filters.options[j].value);
		}
		liststr.value = lrtrim(newValue);
	} else {
		alert("Please select a Filter first!");
	}
}
