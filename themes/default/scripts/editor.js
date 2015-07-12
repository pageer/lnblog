function LBCodeEditor() {

	this.short_forms = [];
	this.abbrev_phrases = [];
	this.acronym_phrases = [];
	
	this.add_acr = function (shrt, lng) {
		var len = this.short_forms.push(shrt);
		this.acronym_phrases[len-1] = lng;
	};
	
	this.add_abbr = function (shrt, lng) {
		var len = this.short_forms.push(shrt);
		this.abbrev_phrases[len-1] = lng;
	};
	
	this.get_abbr = function (shrt) {
		var i=0;
		for (i=0; i < this.short_forms.length; i++) {
			if (this.short_forms[i] == shrt) {
				if (this.abbrev_phrases[i]) {
					return this.abbrev_phrases[i];
				} else {
					return '';
				}
			}
		}
		return '';
	};
	
	this.get_acr = function (shrt) {
		var i=0;
		for (i=0; i < this.short_forms.length; i++) {
			if (this.short_forms[i] == shrt) {
				if (this.acronym_phrases[i]) {
					return this.acronym_phrases[i];
				} else {
					return '';
				}
			}
		}
		return '';
	};
	
	this.one_parm = function (type, prompt_text) {
		/*globals lnblog */
		var text_str;
		if (lnblog.getSelection($("#body").get(0))) {
			text_str = lnblog.getSelection($("#body").get(0));
		} else {
			text_str = window.prompt(prompt_text);
		}
		if (! text_str) {
			return false;
		}
		lnblog.insertAtCursor(
			$("#body").get(0),
			'[' + type + ']' + text_str + '[/' + type + ']'
		);
		return false;
	};
	
	this.two_parm = function (type, prompt_text, meta_text, defaults) {
		
		var desc_str,  meta_str,
			desc_default = (defaults || {}).desc || '',
			meta_default = (defaults || {}).meta || '';
		
		if (lnblog.getSelection($("#body").get(0))) {
			desc_str = lnblog.getSelection($("#body").get(0));
		} else {
			desc_str = window.prompt(prompt_text, desc_default);
		}
	
		if (! desc_str) {
			return false;
		}
		
		if (type == 'ab' || type == 'ac') {
			
			if (type == 'ab') {
				meta_str = this.get_abbr(desc_str);
			} else {
				meta_str = this.get_acr(desc_str);
			}
	
			if (! meta_str) {
				meta_str = window.prompt(meta_text, meta_default);
				if (! meta_str) {
					return false;
				}
				if (type == 'ab') {
					this.add_abbr(desc_str, meta_str);
				} else {
					this.add_acr(desc_str, meta_str);
				}
			}
			
		} else {
			meta_str = window.prompt(meta_text, meta_default);
		}
		
		if (! meta_str) {
			return false;
		}
		
		lnblog.insertAtCursor(
			document.getElementById("body"),
			'[' + type + '=' + meta_str + ']' +	desc_str + '[/' + type + ']');
	
		return false;
	};
	
	this.opt_parm = function (type, prompt_text, meta_text, defaults) {
	
		var desc_str, meta_str, data_str,
			prompt_default = (defaults || {}).prompt || '',
			meta_default = (defaults || {}).meta || '';
		
		if (lnblog.getSelection($("#body").get(0))) {
			desc_str = lnblog.getSelection($("#body").get(0));
		} else {
			desc_str = window.prompt(prompt_text, prompt_default);
		}
	
		if (! desc_str) {
			return false;
		}
		
		meta_str = window.prompt(meta_text, meta_default);
	
		if (! meta_str) {
			meta_str = '';
		}
		
		data_str = '[' + type;
		if (meta_str === '') {
			data_str = '[' + type + ']' + desc_str + '[/' + type + ']';
		} else {
			data_str = '[' + type + '=' + meta_str + ']' + desc_str + '[/' + type + ']';
		}
		
		lnblog.insertAtCursor(
			$("#body").get(0), data_str);
		return false;
	};
	
	this.set_color = function (text_prompt, attr_prompt) {
	
		var text_str, color_str, data_str;
		
		if (lnblog.getSelection($("#body").get(0))) {
			text_str = lnblog.getSelection($("#body").get(0));
		} else {
			text_str = window.prompt(text_prompt);
		}
		
		if (! text_str) {
			return false;
		}
		
		var color = document.getElementById('colorselect');
		if (color.value == 'custom') {
			color_str = window.prompt(attr_prompt);
		} else {
			color_str = color.value;
		}
		
		if (!color_str) {
			return false;
		}
	
		data_str = '[color='+color_str+']'+text_str+'[/color]';
		
		lnblog.insertAtCursor(document.getElementById("body"), data_str);
		return false;
	};
	
	this.set_img = function (text_prompt, attr_prompt) {
	
		var text_str, url_str, tag_str, data_str;
		
		if (lnblog.getSelection(document.getElementById("body"))) {
			text_str = lnblog.getSelection(document.getElementById("body"));
		} else {
			text_str = window.prompt(text_prompt);
		}
		
		if (! text_str) {
			return false;
		}
		
		url_str = window.prompt(attr_prompt);
		if (! url_str) {
			return false;
		}
		
		var alignbox = document.getElementById('imgalign');
		if (alignbox.value == 'center') {
			tag_str = 'img';
		} else {
			tag_str = 'img-'+alignbox.value;
		}
		
		data_str = '['+tag_str+'='+url_str+']'+text_str+'[/'+tag_str+']';
		
		lnblog.insertAtCursor(document.getElementById("body"), data_str);
		return false;
	};
	
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
	};
	
	function set_article_path(e) {
		var subj = document.getElementById("subject").value;
		var path = subj.toLowerCase();
		if (document.getElementById("short_path").value) {
			return true;
		}
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
			if (dropdown.value == 1) {  // Magic number: LBCode constant
				lbcode.style.display = 'block';
			} else {
				lbcode.style.display = 'none';
			}
		}
		return true;
	}
	
	function toggle_post_settings(ev) {
		var $settings = $('#entry_settings'),
			$legend_link = $settings.find('legend > a');
		
		$settings.find('.settings').toggle();
		if ($legend_link.text() == '(+)') {
			$legend_link.text('(-)');
		} else {
			$legend_link.text('(+)');
		}
		
		return false;
	}
	
	function topic_add_tag(e) {
		var tags = document.getElementById('tags');
		var tagsel = document.getElementById('tag_list');
		if (! tags.value) {
			tags.value = tagsel.value;
		} else {
			tags.value = tags.value+','+tagsel.value;
		}
	}
	
	this.upload_add_link = function (e) {
		/*globals strings */
		/*jshint regexp: false */
		var ext = this.value.replace(/^(.*)(\..+)$/, "$2");
		var img_exts = new Array('.png', '.jpg', '.jpeg', '.bmp', '.gif', '.tiff', '.tif');
		var is_img = false;
	
		var check_add = document.getElementById("adduploadlink");
		if (check_add && ! check_add.checked) {
			return false;
		}
		
		// Cheap, cheap hack since we're no longer doing auto-add.
		if (e !== true) {
			return false;
		}
	
		for (var i=0; i< img_exts.length; i++) {
			if (img_exts[i] == ext) {
				is_img = true;
				break;
			}
		}
	
		var components = [];
		var splitchar = '';
		if (this.value.indexOf("\\") > 0 && this.value.indexOf("/") < 0) {
			splitchar = "\\";
		} else {
			splitchar = "/";
		}
		components = this.value.split(splitchar);
		var fname = components[components.length - 1];
	
		// Prompt user for descriptive text. The alternative is just using the filename.
		var res, prompt_text = false;
	
		if (prompt_text) {
	
			if (is_img) {
				res = window.prompt(strings.get('editor_addImageDesc', this.value));
			} else {
				res = window.prompt(strings.get('editor_addLinkDesc', this.value));
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
	};
	
	function articleSet(e) {
		var path = document.getElementById('short_path');
		var isart = document.getElementById('publisharticle');
		path.style.display = isart.checked ? 'inline' : 'none';
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
		
		var tagsel = document.getElementById('tag_list');
		lnblog.addEvent(tagsel, 'change', topic_add_tag);
		
		var pubart = document.getElementById('publisharticle');
		if (pubart) {
			lnblog.addEvent(pubart, 'change', articleSet);
			articleSet();
		}
		
		if (document.getElementById('short_path')) {
			lnblog.addEvent(document.getElementById('subject'), 'blur', set_article_path);
		}
		
		document.getElementById('subject').focus();
		
		return true;
	}
	
	lnblog.addEvent(window, 'load', document_add_all_events);
}

var lbcode_editor = new LBCodeEditor();

$(document).ready(function () {
	
	// Auto-publish widgets
	$('#autopublishdate').datetimepicker({
		format: 'Y-m-d h:i a'
	});
	
	$('.checktoggle[data-for]').on('change', function() {
		var $this = $(this),
			checked = $this.is(':checked'),
			for_id = $this.attr('data-for');
		$('#' + for_id).toggle(checked);
		if (checked) {
			$(for_id).focus();
		}
	});
	
	$('.checktoggle[data-for]').each(function() {
		var $this = $(this),
			for_id = $this.attr('data-for');
		$('#' + for_id).toggle($this.is(':checked'));
	});
	
	$('.entry_preview .preview-close').on('click.preview', function (e) {
		e.preventDefault();
		$('.entry_preview').hide();
		$('#postform').show();
	});
	
	$("#postform input[type='submit']").click(function () {
		$("#postform input[type='submit']").attr('rel', '');
		$(this).attr('rel', 'clicked');
	});
	
	$('#postform').submit(function () {
		if ($('#preview').attr('rel') == 'clicked') {
			var form_url = $('#postform').attr('action');
			form_url += (form_url.indexOf('?') >= 0) ? '&' : '?';
			form_url += 'preview=yes&ajax=1';
			
			var has_files = false;
			$("#postform input[type='file']").each(function () {
				if ($(this).val()) {
					has_files = true;
				}
			});
			
			if (has_files) {
				var ret = window.confirm(strings.editor_submitWithFiles);
				if (ret) {
					form_url += '&save=draft';
				} else {
					return false;
				}
			}

			var options = {
				target: '.entry_preview',
				url: form_url,
				dataType: 'json',
				success: function (response, statusText, xhr, $form) {
					$('.entry_preview')
						.find('.preview-text').html(unescape(response.content)).end()
						.find('.preview-overlay').remove();
					if (response.id.match(/draft/)) {
						var form_url = $('#postform').attr('action');
						form_url += form_url.match(/draft=/) ? '' : '&draft='+response.id;
						form_url += form_url.match(/preview=1/) ? '' : '&preview=yes';
						form_url += form_url.match(/ajax=1/) ? '' : 'ajax=1';
						$('#postform').attr('action', form_url);
					}
				}
			};
			
			var markup = '<div class="preview-overlay"><div class="label"><img src="'+
				window.INSTALL_ROOT+'/themes/default/images/ajax-loader.gif"> Loading...</div></div>';
			$('.entry_preview').show().prepend(markup);
			
			$('#postform').hide().ajaxSubmit(options);
			return false;
		}
		return true;
	});
});
