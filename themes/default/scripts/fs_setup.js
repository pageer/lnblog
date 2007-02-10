function doc_test() {
	window.open("docroot_test.php");
	return false;
}

function ftp_test() {
	window.open("ftproot_test.php");
	return false;
}

function toggle_ftp(state) {
	var div = document.getElementById("ftpoptions");
	
	if (state) {
		div.style.display = "block";
	} else {
		div.style.display = "none";
	}
}

function show_ftp() {
	toggle_ftp(true);
}

function hide_ftp() {
	toggle_ftp(false);
}

function toggle_presets() {
	if (document.getElementById('mod_nosmroot').checked) {
		document.getElementById('permdir').selectedIndex = 0;
		document.getElementById('permscript').selectedIndex = 0;
		document.getElementById('permfile').selectedIndex = 0;
		document.getElementById('native').checked = true;
		toggle_ftp(false);
	} else if (document.getElementById('mod_nosm').checked) {
		document.getElementById('permdir').selectedIndex = 3;
		document.getElementById('permscript').selectedIndex = 3;
		document.getElementById('permfile').selectedIndex = 3;
		document.getElementById('ftpfs').checked = true;
		toggle_ftp(true);
	} else if (document.getElementById('mod_sm').checked) {
		document.getElementById('permdir').selectedIndex = 3;
		document.getElementById('permscript').selectedIndex = 3;
		document.getElementById('permfile').selectedIndex = 3;
		document.getElementById('ftpfs').checked = true;
		toggle_ftp(true);
	} else if (document.getElementById('suexec').checked) {
		document.getElementById('permdir').selectedIndex = 3;
		document.getElementById('permscript').selectedIndex = 3;
		document.getElementById('permfile').selectedIndex = 3;
		document.getElementById('native').checked = true;
		toggle_ftp(false);
	}
}

function init_event_handlers() {
	lnblog.addEvent(document.getElementById('mod_nosmroot'), 'click', toggle_presets);
	lnblog.addEvent(document.getElementById('mod_nosm'), 'click', toggle_presets);
	lnblog.addEvent(document.getElementById('mod_sm'), 'click', toggle_presets);
	lnblog.addEvent(document.getElementById('suexec'), 'click', toggle_presets);
	lnblog.addEvent(document.getElementById('native'), 'focus', hide_ftp);
	lnblog.addEvent(document.getElementById('ftpfs'), 'focus', show_ftp);
	toggle_presets();
}

lnblog.addEvent(document, 'load', init_event_handlers);
