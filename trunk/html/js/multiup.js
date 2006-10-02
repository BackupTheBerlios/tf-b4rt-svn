/* $Id$ */

/**
 * addUploadField
 */
function addUploadField() {
	formContent = document.getElementById("fileUploadCell");
	var inp  = document.createElement('input');
	inp.type = 'file';
	inp.name = 'upload_files[]';
	inp.size = '40';
	formContent.appendChild(inp);
}