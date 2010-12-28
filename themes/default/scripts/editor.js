function LBCodeEditor() {
	
	var that = this;
	var self = this;
	
	this.short_forms = Array();
	this.abbrev_phrases = Array();
	this.acronym_phrases = Array();
	
	this.add_acr = function (shrt, lng) {
		var len = this.short_forms.push(shrt);
		this.acronym_phrases[len-1] = lng;
	}
	
	this.add_abbr = function (shrt, lng) {
		var len = this.short_forms.push(shrt);
		this.abbrev_phrases[len-1] = lng;
	}
	
	this.get_abbr = function (shrt) {
		var i=0;
		for (i=0; i < this.short_forms.length; i++) 
			if (this.short_forms[i] == shrt) {
				if (this.abbrev_phrases[i]) return this.abbrev_phrases[i];
				else return '';
			}
		return '';
	}
	
	this.get_acr = function (shrt) {
		var i=0;
		for (i=0; i < this.short_forms.length; i++) 
			if (this.short_forms[i] == shrt) {
				if (this.acronym_phrases[i]) return this.acronym_phrases[i];
				else return '';
			}
		return '';
	}
	
	this.one_parm = function (type, prompt_text) {
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
	
	this.two_parm = function (type, prompt_text, meta_text) {
		
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
			
			if (type == 'ab') meta_str = this.get_abbr(desc_str);
			else meta_str = this.get_acr(desc_str);
	
			if (meta_str == '') {
				if (document.getElementById("editboxes") &&
					document.getElementById("editboxes").style.display == "block") {
					meta_str = document.getElementById("meta_txt").value;
				} else {
					meta_str = window.prompt(meta_text);
				}
				if (! meta_str) return false;
				if (type == 'ab') this.add_abbr(desc_str, meta_str);
				else this.add_acr(desc_str, meta_str);
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
	
	this.opt_parm = function (type, prompt_text, meta_text) {
	
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
	
	this.set_color = function (text_prompt, attr_prompt) {
	
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
	
	this.set_img = function (text_prompt, attr_prompt) {
	
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
	
	
	this.insertChar = function (type) {
		lnblog.insertAtCursor(document.getElementById("body"), type);
		return true;
	}
	
	this.insertEntity = function (type) {
		insertChar('&'+type+';');
		return true;
	}
	
	this.toggle_show = function (ctrl, caller) {
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
		
		return false;
	}
	
	function set_article_path(e) {
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
	
	this.upload_add_link = function (e) {
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
	
		var components = new Array();
		var splitchar = '';
		if (this.value.indexOf("\\") > 0 && this.value.indexOf("/") < 0) {
			splitchar = "\\";
		} else {
			splitchar = "/";
		}
		components = this.value.split(splitchar);
		var fname = components[components.length - 1];
	
		var prompt_text = false;  // Prompt user for descriptive text.
		                          // The alternative is just using the filename.
	
		if (prompt_text) {
	
			if (is_img) {
				var res = window.prompt(strings.get('editor_addImageDesc', this.value));
			} else {
				var res = window.prompt(strings.get('editor_addLinkDesc', this.value));
			}
	
		} else {
			res = fname;
		}
		
		if (res) {
			var body = document.getElementById('body');
			if (is_img) {
				if (document.getElementById('input_mode').value == 1) {
					lnblog.insertAtCursor(body, '[img='+fname+']'+res+'[/img]');
				} else {
					lnblog.insertAtCursor(body, "<img src=\""+fname+"\" alt=\""+res+"\" title=\""+res+"\" />");
				}
			} else {
				if (document.getElementById('input_mode').value == 1) {
					lnblog.insertAtCursor(body, '[url='+fname+']'+res+'[/url]');
				} else {
					lnblog.insertAtCursor(body, "<a href=\""+fname+"\">"+res+"</a>");
				}
			}
		}
		return false;
	}
	
	function articleSet(e) {
		var path = document.getElementById('articlepath');
		var isart = document.getElementById('publisharticle');
		if (isart.checked) path.style.display = 'block';
		else path.style.display = 'none';
	}
	
	function document_add_all_events (e) {
	
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
				lnblog.addEvent(uploads[i], 'change', self.upload_add_link);
			}
		}
		
		var tagsel = document.getElementById('tag_list');
		lnblog.addEvent(tagsel, 'change', topic_add_tag);
		
		var pubart = document.getElementById('publisharticle');
		if (pubart) {
			lnblog.addEvent(pubart, 'change', articleSet);
			articleSet();
		}
		
		if (document.getElementById('articlepath')) {
			lnblog.addEvent(document.getElementById('subject'), 'blur', set_article_path);
		}
		
		var preview = document.getElementById('preview');
		lnblog.addEvent(preview, 'click', 
			function(e) {
				var has_files = false;
				var i = 1;
				var max_uploads = 20;
				var upld;
			
				postform = document.getElementById('postform');
				
				while (upld = document.getElementById('upload'+i) && i < max_uploads) {
					if (upld.value) {
						has_files = true;
						break;
					}
					i++;
				}
				
				if (has_files) {
					var ret = window.confirm(strings.editor_submitWithFiles);
					if (ret) {
					
						postform.action = postform.action + '&save=draft';
						return true;
					} else {
						return false;
					}
				} else {
					return true;
				}
			});
		
		document.getElementById('subject').focus();
		
		return true;
	}
	
	lnblog.addEvent(window, 'load', document_add_all_events);
}

var lbcode_editor = new LBCodeEditor();
