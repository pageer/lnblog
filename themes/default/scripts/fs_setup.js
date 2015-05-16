function doc_test() {
	window.open("docroot_test.php");
	return false;
}

function ftp_test() {
	window.open("ftproot_test.php");
	return false;
}

function toggle_ftp(state) {
	var div = $("#ftpoptions");
	
	if (state) {
		div.show();
	} else {
		div.hide();
	}
}

function show_ftp() {
	toggle_ftp(true);
}

function hide_ftp() {
	toggle_ftp(false);
}

function toggle_presets() {
	if ($('#mod_nosmroot').is(':checked')) {
		$('#permdir')[0].selectedIndex = 0;
		$('#permscript')[0].selectedIndex = 0;
		$('#permfile')[0].selectedIndex = 0;
		$('#native').prop('checked', true);
		toggle_ftp(false);
	} else if ($('#mod_nosm').is(':checked')) {
		$('#permdir')[0].selectedIndex = 3;
		$('#permscript')[0].selectedIndex = 3;
		$('#permfile')[0].selectedIndex = 3;
		$('#ftpfs').prop('checked', true);
		toggle_ftp(true);
	} else if ($('#mod_sm').is(':checked')) {
		$('#permdir')[0].selectedIndex = 3;
		$('#permscript')[0].selectedIndex = 3;
		$('#permfile')[0].selectedIndex = 3;
		$('#ftpfs').prop('checked', true);
		toggle_ftp(true);
	} else if ($('#suexec').is(':checked')) {
		$('#permdir')[0].selectedIndex = 3;
		$('#permscript')[0].selectedIndex = 3;
		$('#permfile')[0].selectedIndex = 3;
		$('#native').prop('checked', true);
		toggle_ftp(false);
	}
}

function init_event_handlers() {
	$('#mod_nosmroot').on('click', toggle_presets);
	$('#mod_nosm').on('click', toggle_presets);
	$('#mod_sm').on('click', toggle_presets);
	$('#suexec').on('click', toggle_presets);
	$('#native').on('focus', hide_ftp);
	$('#ftpfs').on('focus', show_ftp);
	toggle_presets();
}

$(document).ready(init_event_handlers);
