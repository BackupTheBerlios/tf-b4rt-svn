function slider(type) {
	if (document.getElementById(type).style.display == "none") {
		document.getElementById(type).style.display = "";
		document.getElementById(type).style.height = "";
	}
	else {
		document.getElementById(type).style.display = "none";
		document.getElementById(type).style.height = "0px";
	}
}