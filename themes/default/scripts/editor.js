var short_forms = Array();
var abbrev_phrases = Array();
var acronym_phrases = Array();

function insertAtCursor(myField, myValue) {
	//IE support
	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		sel.text = myValue;
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		myField.value = myField.value.substring(0, startPos)
			+ myValue
			+ myField.value.substring(endPos, myField.value.length);
	} else {
		myField.value += myValue;
	}
	myField.focus();
}

function hasSelection(myField) {
	var ret = false;
	//IE support
	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		ret = sel.text;
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		ret = myField.value.substring(startPos, endPos);
	} else {
		ret = false;
	}
	return ret;
}

function add_acr(shrt, lng) {
	var len = short_forms.push(shrt);
	acronym_phrases[len-1] = lng;
}

function add_abbr(shrt, lng) {
	var len = short_forms.push(shrt);
	abbrev_phrases[len-1] = lng;
}

function get_abbr(shrt) {
	var i=0;
	for (i=0; i < short_forms.length; i++) 
		if (short_forms[i] == shrt) {
			if (abbrev_phrases[i]) return abbrev_phrases[i];
			else return '';
		}
	return '';
}

function get_acr(shrt) {
	var i=0;
	for (i=0; i < short_forms.length; i++) 
		if (short_forms[i] == shrt) {
			if (acronym_phrases[i]) return acronym_phrases[i];
			else return '';
		}
	return '';
}

function one_parm(type, prompt_text) {
	var text_str;
	if (document.getElementById("editboxes") &&
	    document.getElementById("editboxes").style.display == "block") {
		text_str = document.getElementById("desc_txt").value;
		document.getElementById("desc_txt").value = '';
	} else if (hasSelection(document.getElementById("body"))) {
		text_str = hasSelection(document.getElementById("body"));
	} else {
		text_str = window.prompt(prompt_text);
	}
	if (! text_str) return false;
	insertAtCursor(
		document.getElementById("body"), 
		'[' + type + ']' + text_str + '[/' + type + ']');
	return true;
}

function two_parm(type, prompt_text, meta_text) {
	
	var desc_str;
	var meta_str;
	
	if (document.getElementById("editboxes") &&
	    document.getElementById("editboxes").style.display == "block") {
		desc_str = document.getElementById("desc_txt").value;
	} else if (hasSelection(document.getElementById("body"))) {
		desc_str = hasSelection(document.getElementById("body"));
	} else {
		desc_str = window.prompt(prompt_text);
	}

	if (! desc_str) return false;
	
	if (type == 'ab' || type == 'ac') {
		
		if (type == 'ab') meta_str = get_abbr(desc_str);
		else meta_str = get_acr(desc_str);

		if (meta_str == '') {
			if (document.getElementById("editboxes") &&
			    document.getElementById("editboxes").style.display == "block") {
				meta_str = document.getElementById("meta_txt").value;
			} else {
				meta_str = window.prompt(meta_text);
			}
			if (! meta_str) return false;
			if (type == 'ab') add_abbr(desc_str, meta_str);
			else add_acr(desc_str, meta_str);
		}
		
	} else {
		if (document.getElementById("editboxes") &&
		    document.getElementById("editboxes").style.display == "block") {
			meta_str = document.getElementById("meta_txt").value;
		} else {
			meta_str = window.prompt(meta_text);
		}
	}
	
	if (! meta_str) return false;
	
	insertAtCursor(
		document.getElementById("body"),
		'[' + type + '=' + meta_str + ']' +	desc_str + '[/' + type + ']');

	return true;

}

function opt_parm(type, prompt_text, meta_text) {

	var desc_str;
	var meta_str;
	var data_str;
	
	if (document.getElementById("editboxes") &&
	    document.getElementById("editboxes").style.display == "block") {
		desc_str = document.getElementById("desc_txt").value;
	} else if (hasSelection(document.getElementById("body"))) {
		desc_str = hasSelection(document.getElementById("body"));
	} else {
		desc_str = window.prompt(prompt_text);
	}

	if (! desc_str) return false;
	
	if (document.getElementById("editboxes") &&
	    document.getElementById("editboxes").style.display == "block") {
		meta_str = document.getElementById("meta_txt").value;
	} else {
		meta_str = window.prompt(meta_text);
	}

	if (! meta_str) meta_str = '';
	
	data_str = '[' + type;
	if (meta_str == '') {
		data_str = '[' + type + ']' + desc_str + '[/' + type + ']'
	} else {
		data_str = '[' + type + '=' + meta_str + ']' + desc_str + '[/' + type + ']'
	}
	
	insertAtCursor(
		document.getElementById("body"), data_str);
	return true;

}

function insertChar(type) {
	insertAtCursor(document.getElementById("body"), type);
	return true;
}

function insertEntity(type) {
	insertChar('&'+type+';');
	return true;
}

function toggle_show(ctrl, caller) {
	var cnt = document.getElementById(ctrl);
	var links = caller.getElementsByTagName("a");
	var block_hidden = false;
	//window.alert(cnt.style.display);
	for (var i=0; i < links.length; i++) {
		// Account for undefined display style
		if (! cnt.style.display && links[i].innerHTML == "(+)") {
			block_hidden = true;
		}
	}
	if (cnt.style.display == "none" ||  block_hidden) {
		cnt.style.display = "block";
		for (var i=0; i < links.length; i++) links[i].innerHTML = "(-)";
	} else {
		cnt.style.display = "none";
		for (var i=0; i < links.length; i++) links[i].innerHTML = "(+)";
	}
}

function set_article_path() {
	var subj = document.getElementById("subject").value;
	var path = subj.toLowerCase();
	if (document.getElementById("short_path").value) return true;
	path = path.replace(/\W+/g, " ");
	path = path.replace(/^ */, "");
	path = path.replace(/ *$/, "");
	path = path.replace(/\W+/g, "_");
	document.getElementById("short_path").value = path;
	return true;
}
