

// fields
var formElement = '<input type="File" name="upload_files[]" size="30">';

/**
 * addUploadField
 */
function addUploadField() {
	document.getElementById("fileUploadDiv").innerHTML += "<br>\n" + formElement;
}
