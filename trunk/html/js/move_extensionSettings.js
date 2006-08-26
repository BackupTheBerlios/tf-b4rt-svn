// Enable the textfield if Moving shall be anabled.
function enableMoveElements(form, dropDownField) {
	if(dropDownField.value == "0") {
		form.move_path.disabled = true;
		form.categorylist.disabled = true;
		form.category.disabled = true;
		form.addCatButton.disabled = true;
		form.remCatButton.disabled = true;
	} else {
		form.move_path.disabled = false;
		form.categorylist.disabled = false;
		form.category.disabled = false;
		form.addCatButton.disabled = false;
		form.remCatButton.disabled = false;
	}
}

function addEntry () {
    var catliststr = document.theForm.move_paths;
    var catliste = document.theForm.categorylist;
    var newentry = document.createElement("option");
    newentry.text = document.theForm.category.value;
    newentry.text = mytrim(newentry.text);
 	if( newentry.text != "") {
        // empty the new category field
        document.theForm.category.value = "";
        newentry.value = catliste.length;
        if(navigator.appName == "Netscape") {
        	catliste.add(newentry, null);
        } else {
        	catliste.add(newentry);
        }
        if( catliststr.value == "" ) {
        	catliststr.value = newentry.text;
        } else {
        	catliststr.value = catliststr.value + ":" + newentry.text;
        }
  	} else {
		alert("Please enter a Directory first!");
	}
}

function mytrim(value) {
	var trimmedVal = "";
	for(var i=0; i<value.length; i++) {
		if(value.charCodeAt(i) != 32) {
			trimmedVal = trimmedVal + value.charAt(i);
		}
	}
	return trimmedVal;
}

function removeEntry() {
	var catliststr = document.theForm.move_paths;
	if(document.theForm.categorylist.selectedIndex != -1) {
		document.theForm.categorylist.remove(document.theForm.categorylist.selectedIndex);
		var newValue = "";
		for (var j = 0; j < document.theForm.categorylist.options.length; j++) {
            if (j > 0) {
                newValue += ":";
            }
		    newValue += mytrim(document.theForm.categorylist.options[j].text);
		}
		catliststr.value = mytrim(newValue);
	} else {
		alert("Please select an entry first!");
	}
}