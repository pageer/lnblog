function doc_test() {
	window.open("docroot_test.php");
	return false;
}

function ftp_test() {
	window.open("ftproot_test.php");
	return false;
}

function show_ftp(state) {
	var div = document.getElementById("ftpoptions");
	
	if (state) {
		div.style.display = "block";
	} else {
		div.style.display = "none";
	}
}
