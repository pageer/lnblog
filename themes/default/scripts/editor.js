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

function one_parm(type) {
	var text_str = document.getElementById("desc_txt").value;
	if (text_str == '') return false;
	insertAtCursor(
		document.getElementById("data"), 
		'[' + type + ']' + text_str + '[/' + type + ']');
	document.getElementById("desc_txt").value = '';
	return true;
}

function two_parm(type) {
	
	var desc_str = document.getElementById("desc_txt").value;
	var meta_str = document.getElementById("meta_txt").value;
	var lookup;

	if (desc_str == '') return false;
	
	if (type == 'ab' || type == 'ac') {
		if (meta_str != '') {
			lookup = meta_str;
			if (type == 'ab') add_abbr(desc_str, meta_str);
			else add_acr(desc_str, meta_str);
		} else {
			if (type == 'ab') lookup = get_abbr(desc_str);
			else lookup = get_acr(desc_str);
		}
		if (lookup == '') {
			var msg = (type == 'ab' ? 'Abbreviation' : 'Acronym')
			window.alert(msg + " '"+desc_str+"'" + ' not found.');
			return false;
		}
		meta_str = lookup;
	} else if (meta_str == '') return false;
	insertAtCursor(
		document.getElementById("data"),
		'[' + type + '=' + meta_str + ']' +	desc_str + '[/' + type + ']');

	document.getElementById("desc_txt").value = '';
	document.getElementById("meta_txt").value = '';
	return true;

}

function opt_parm(type) {

	var desc_str = document.getElementById("desc_txt").value;
	var meta_str = document.getElementById("meta_txt").value;
	var data_str = '';

	if (desc_str == '') return false;

	data_str = '[' + type;
	if (meta_str == '') {
		data_str = '[' + type + ']' + desc_str + '[/' + type + ']'
	} else {
		data_str = '[' + type + '=' + meta_str + ']' + desc_str + '[/' + type + ']'
	}
	
	insertAtCursor(
		document.getElementById("data"), data_str);
	document.getElementById("desc_txt").value = '';
	document.getElementById("meta_txt").value = '';
	return true;

}
