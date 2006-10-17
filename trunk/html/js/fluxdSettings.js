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
	var wu = mytrim(document.theForm.watch_user.options[document.theForm.watch_user.selectedIndex].value);
    var wd = mytrim(document.theForm.watch_dir.value);
    if ((wu != "") && (wd != "")) {
		if (wd.indexOf('/') != 0) {
			alert("Directory must be absolute !");
			return false;
		}
	    var liststr = document.theForm.fluxd_Watch_jobs;
	    var list = document.theForm.watch_jobs;
	    for (var i = 0; i < document.theForm.watch_jobs.options.length; i++) {
	    	if ((mytrim(document.theForm.watch_jobs.options[i].text)) == (wu + ":" + wd)) {
	    		alert("Job already exists");
	    		return false;
	    	}
	    	if ((mytrim(document.theForm.watch_jobs.options[i].text)) == (wu + ":" + wd + "/")) {
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
	    	liststr.value = newentry.text;
	    else
	    	liststr.value = liststr.value + ";" + newentry.text;
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
		    newValue += mytrim(document.theForm.watch_jobs.options[j].text);
		}
		liststr.value = mytrim(newValue);
	} else {
		alert("Please select a Job first!");
	}
}

/**
 * addRssadFilterEntry()
 */
function addRssadFilterEntry() {
    var filter = mytrim(document.theForm.rssad_filter_entry.value);
    if (filter != "") {
	    for (var i = 0; i < document.theForm.rssad_filters.options.length; i++) {
	    	if ((mytrim(document.theForm.rssad_filters.options[i].text)) == filter) {
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
	    	liststr.value = newentry.text;
	    else
	    	liststr.value = liststr.value + "\n" + newentry.text;
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
		    newValue += mytrim(document.theForm.rssad_filters.options[j].text);
		}
		liststr.value = mytrim(newValue);
	} else {
		alert("Please select a Filter first!");
	}
}
