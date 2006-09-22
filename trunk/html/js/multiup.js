

// fields
var fileCtr = 1;
var fileAry = new Array();

/**
 * addUploadField
 */
function addUploadField() {
	/*
	// get old values
	for (i = 0; i < fileCtr; i++) {
		id = "file_" + String(i);
		fileAry[i] = document.getElementById(id).value;
	}
	*/
	// add element
	addElement  = "<br>\n" + '<input type="File" name="upload_files[]" size="30" id="file_';
	addElement += String(fileCtr);
	addElement += '">';
	document.getElementById("fileUploadDiv").innerHTML += addElement;
	/*
	// set values
	for (i = 0; i < fileCtr; i++) {
		id = "file_" + String(i);
		// next op is not allowed (security exception) :
		document.getElementById(id).value = fileAry[i];
	}
	*/
	// increment field-count
	fileCtr++;
}
