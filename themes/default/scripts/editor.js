var short_forms = Array();
var abbrev_phrases = Array();
var acronym_phrases = Array();

function addEvent(obj, sType, fn){
    if (obj.addEventListener){
        obj.addEventListener(sType, fn, false);
    } else if (obj.attachEvent) {
        var r = obj.attachEvent("on"+sType, fn);
    } else {
        alert("Handler could not be attached");
    }
}

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
	return false;
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

	return false;

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
	return false;

}

function set_color(text_prompt, attr_prompt) {

	var text_str;
	var color_str;
	var data_str;
	
	if (hasSelection(document.getElementById("body"))) {
		text_str = hasSelection(document.getElementById("body"));
	} else {
		text_str = window.prompt(text_prompt);
	}
	
	if (! text_str) return false;
	
	var color = document.getElementById('colorselect');
	if (color.value == 'custom') {
		color_str = window.prompt(attr_prompt);
	} else {
		color_str = color.value;
	}
	
	data_str = '[color='+color_str+']'+text_str+'[/color]';
	
	insertAtCursor(document.getElementById("body"), data_str);
	return false;

}

function set_img(text_prompt, attr_prompt) {

	var text_str;
	var url_str;
	var tag_str;
	var data_str;
	
	if (hasSelection(document.getElementById("body"))) {
		text_str = hasSelection(document.getElementById("body"));
	} else {
		text_str = window.prompt(text_prompt);
	}
	
	if (! text_str) return false;
	
	url_str = window.prompt(attr_prompt);
	if (! url_str) return false;
	
	var alignbox = document.getElementById('imgalign');
	if (alignbox.value == 'center') {
		tag_str = 'img';
	} else {
		tag_str = 'img-'+alignbox.value;
	}
	
	data_str = '['+tag_str+'='+url_str+']'+text_str+'[/'+tag_str+']';
	
	insertAtCursor(document.getElementById("body"), data_str);
	return false;

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
	var block_hidden = false;

	if (! cnt.style.display && caller.innerHTML == "(+)") {
		block_hidden = true;
	}
	
	if (cnt.style.display == "none" ||  block_hidden) {
		cnt.style.display = "inline";
		caller.innerHTML = "(-)";
	} else {
		cnt.style.display = "none";
		caller.innerHTML = "(+)";
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

function toggle_lbcode_editor(ev) {
	var lbcode = document.getElementById('lbcode_editor');
	var dropdown = document.getElementById('input_mode');
	if (lbcode) {
		if (dropdown.value == 1) lbcode.style.display = 'inline';
		else lbcode.style.display = 'none';
	}
	return true;
}

function toggle_post_settings(ev) {
	var settings = document.getElementById('entry_settings');
	var divs = settings.getElementsByTagName('div');
	var leg = settings.getElementsByTagName('legend');
	var leglink = leg[0].getElementsByTagName('a');
	
	if (leglink[0].innerHTML == '(+)') { 
		leglink[0].innerHTML = '(-)';
	} else { 
		leglink[0].innerHTML = '(+)';
	}
	
	for (i = 0; i < divs.length; i++) {
		if (divs[i].style.display == 'none') divs[i].style.display = 'block';
		else divs[i].style.display = 'none';
	}	
	return false;
}

function topic_add_tag(e) {
	var tags = document.getElementById('tags');
	var tagsel = document.getElementById('tag_list');
	if (tags.value == '') {
		tags.value = tagsel.value;
	} else if (tagsel.value != '') {
		tags.value = tags.value+','+tagsel.value;
	}
}

function document_add_all_events(e) {

	// Toggle the LBCode editor on or off depending on the markup setting.
	var inputmode = document.getElementById('input_mode');
	addEvent(inputmode, 'change', toggle_lbcode_editor);
	toggle_lbcode_editor();
	
	// Toggle the extended settings fields on or off.
	var setfld = document.getElementById('entry_settings');
	var setleg = setfld.getElementsByTagName('legend');
	addEvent(setleg[0], 'click', toggle_post_settings);
	toggle_post_settings();
	
	var tagsel = document.getElementById('tag_list');
	addEvent(tagsel, 'change', topic_add_tag);
	
	document.getElementById('subject').focus();
	return true;
}
//document.onload = function (e) { window.alert("FOO"); }
addEvent(window, 'load', document_add_all_events);
