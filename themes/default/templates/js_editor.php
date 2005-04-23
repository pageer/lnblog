<script type="text/javascript">
<!--
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
//	document.getElementById("<?php echo ENTRY_POST_DATA; ?>").value = 
//		document.getElementById("<?php echo ENTRY_POST_DATA; ?>").value + 
//		'[' + type + ']' + text_str + '[/' + type + ']';
	insertAtCursor(
		document.getElementById("<?php echo ENTRY_POST_DATA; ?>"), 
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
//	document.getElementById("<?php echo ENTRY_POST_DATA; ?>").value = 
//		document.getElementById("<?php echo ENTRY_POST_DATA; ?>").value + 
//		'[' + type + '=' + meta_str + ']' +	desc_str + '[/' + type + ']';
	insertAtCursor(
		document.getElementById("<?php echo ENTRY_POST_DATA; ?>"),
		'[' + type + '=' + meta_str + ']' +	desc_str + '[/' + type + ']');

	document.getElementById("desc_txt").value = '';
	document.getElementById("meta_txt").value = '';
	return true;

}
-->
</script>
<div class="button_row">
<input type="button" id="bold" value="Bold" onclick="one_parm('b');" />
<input type="button" id="italic" value="Italic" onclick="one_parm('i');" />
<input type="button" id="underline" value="Underline" onclick="one_parm('u');" />
<input type="button" id="qt" value="Short quote" onclick="one_parm('q');" />
</div>
<div class="button_row">
<input type="button" id="abr" value="Abbreviation" onclick="two_parm('ab');" />
<input type="button" id="acr" value="Acronym" onclick="two_parm('ac');" />
<input type="button" id="url" value="URL" onclick="two_parm('url');" />
<input type="button" id="img" value="Image" onclick="two_parm('img');" />
</div>
<div class="editorbox">
<div><label for="desc_txt" title="Text to be displayed on page">Text</label>
<input type="text" id="desc_txt" title="Text to be displayed on page" /></div>
<div><label for="meta_txt" title="Link/image URL or abbreviation title">Attribute</label>
<input type="text" id="meta_txt" title="Link/image URL or abbreviation title" /></div>
<hr />
</div>
