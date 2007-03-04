var short_forms = Array();
var abbrev_phrases = Array();
var acronym_phrases = Array();

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
	} else if (lnblog.getSelection(document.getElementById("body"))) {
		text_str = lnblog.getSelection(document.getElementById("body"));
	} else {
		text_str = window.prompt(prompt_text);
	}
	if (! text_str) return false;
	lnblog.insertAtCursor(
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
	} else if (lnblog.getSelection(document.getElementById("body"))) {
		desc_str = lnblog.getSelection(document.getElementById("body"));
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
	
	lnblog.insertAtCursor(
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
	} else if (lnblog.getSelection(document.getElementById("body"))) {
		desc_str = lnblog.getSelection(document.getElementById("body"));
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
	
	lnblog.insertAtCursor(
		document.getElementById("body"), data_str);
	return false;

}

function set_color(text_prompt, attr_prompt) {

	var text_str;
	var color_str;
	var data_str;
	
	if (lnblog.getSelection(document.getElementById("body"))) {
		text_str = lnblog.getSelection(document.getElementById("body"));
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
	
	if (color_str == undefined) return false;

	data_str = '[color='+color_str+']'+text_str+'[/color]';
	
	lnblog.insertAtCursor(document.getElementById("body"), data_str);
	return false;

}

function set_img(text_prompt, attr_prompt) {

	var text_str;
	var url_str;
	var tag_str;
	var data_str;
	
	if (lnblog.getSelection(document.getElementById("body"))) {
		text_str = lnblog.getSelection(document.getElementById("body"));
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
	
	lnblog.insertAtCursor(document.getElementById("body"), data_str);
	return false;

}


function insertChar(type) {
	lnblog.insertAtCursor(document.getElementById("body"), type);
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

function upload_add_link(e) {
	var ext = this.value.replace(/^(.*)(\..+)$/, "$2");
	var img_exts = new Array('.png', '.jpg', '.jpeg', '.bmp', '.gif', '.tiff', '.tif');
	var is_img = false;

	var check_add = document.getElementById("adduploadlink");
	if (check_add && ! check_add.checked) return false;

	for (i=0; i< img_exts.length; i++) {
		if (img_exts[i] == ext) {
			is_img = true;
			break;
		}
	}

	var prompt_text = false;  // Prompt user for descriptive text.
	                          // The alternative is just using the filename.

	if (prompt_text) {

		if (is_img) {
			var res = window.prompt(strings.get('editor_addImageDesc', this.value));
		} else {
			var res = window.prompt(strings.get('editor_addLinkDesc', this.value));
		}

	} else {
		res = this.value;
	}
	
	if (res) {
		var body = document.getElementById('body');
		if (is_img) {
			if (document.getElementById('input_mode').value == 1) {
				lnblog.insertAtCursor(body, '[img='+this.value+']'+res+'[/img]');
			} else {
				lnblog.insertAtCursor(body, '<img src="'+this.value+'" alt="'+res+'" title="'+res+'" />');
			}
		} else {
			if (document.getElementById('input_mode').value == 1) {
				lnblog.insertAtCursor(body, '[url='+this.value+']'+res+'[/url]');
			} else {
				lnblog.insertAtCursor(body, '<a href="'+this.value+'">'+res+'</a>');
			}
		}
	}
}

function document_add_all_events(e) {

	// Toggle the LBCode editor on or off depending on the markup setting.
	var inputmode = document.getElementById('input_mode');
	lnblog.addEvent(inputmode, 'change', toggle_lbcode_editor);
	toggle_lbcode_editor();
	
	// Toggle the extended settings fields on or off.
	var setfld = document.getElementById('entry_settings');
	var setleg = setfld.getElementsByTagName('legend');
	lnblog.addEvent(setleg[0], 'click', toggle_post_settings);
	toggle_post_settings();

	// Add event handlers to file upload boxes.
	var uploads = document.getElementsByTagName('input');
	for (i=0; i< uploads.length; i++) {
		if (uploads[i].type == 'file') {
			lnblog.addEvent(uploads[i], 'change', upload_add_link);
		}
	}
	
	var tagsel = document.getElementById('tag_list');
	lnblog.addEvent(tagsel, 'change', topic_add_tag);
	
	document.getElementById('subject').focus();
	return true;
}
//document.onload = function (e) { window.alert("FOO"); }
lnblog.addEvent(window, 'load', document_add_all_events);
