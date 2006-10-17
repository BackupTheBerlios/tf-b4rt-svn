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
 * addMoveEntry
 */
function addMoveEntry () {
    var val = mytrim(document.theForm.category.value);
 	if (val != "") {
 	 	for (var i = 0; i < document.theForm.categorylist.options.length; i++) {
	    	if ((mytrim(document.theForm.categorylist.options[i].text)) == val) {
	    		alert("Move-dir already exists");
	    		return false;
	    	}
	    	if ((mytrim(document.theForm.categorylist.options[i].text)) == val + "/") {
	    		alert("Move-dir already exists");
	    		return false;
	    	}	    	
	    }
	    var catliststr = document.theForm.move_paths;
	    var catliste = document.theForm.categorylist;
	    var newentry = document.createElement("option");
 		newentry.text = val;
        // empty the new category field
        document.theForm.category.value = "";
        newentry.value = catliste.length;
        if (navigator.appName == "Netscape")
        	catliste.add(newentry, null);
        else
        	catliste.add(newentry);
        if (catliststr.value == "")
        	catliststr.value = newentry.text;
        else
        	catliststr.value = catliststr.value + ":" + newentry.text;
  	} else {
		alert("Please enter a Directory first!");
	}
}

/**
 * removeMoveEntry
 */
function removeMoveEntry() {
	var catliststr = document.theForm.move_paths;
	if (document.theForm.categorylist.selectedIndex != -1) {
		document.theForm.categorylist.remove(document.theForm.categorylist.selectedIndex);
		var newValue = "";
		for (var j = 0; j < document.theForm.categorylist.options.length; j++) {
            if (j > 0)
                newValue += ":";
		    newValue += mytrim(document.theForm.categorylist.options[j].text);
		}
		catliststr.value = mytrim(newValue);
	} else {
		alert("Please select an entry first!");
	}
}