
function bulkCheck(thisIn) {
	var form = thisIn.form, i = 0;
	for(i = 0; i < form.length; i++) {
		if (form[i].type == 'checkbox' && form[i].name != 'bulkBox' && form[i].disabled == false) {
			form[i].checked = thisIn.checked;
		}
	}
}

function ShowDetails(name_file, width, height) {
	window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=450,height=290">')
}

function StartTorrent(name_file) {
	window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=700,height=650')
}

function openServerMonitor() {
	window.open('index.php?iid=servermon','_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=300,height=190')
}
