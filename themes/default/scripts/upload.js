/*globals strings, lnblog, lbcode_editor */
/*jshint regexp: false */
$(document).ready( function () {
	
	function initUploadFieldset(field, editor) {
		
		var addLink = function ($input) {
			alert("Calling addLink");
			var ext = $input.val().replace(/^(.*)(\..+)$/, "$2"),
                img_exts = new Array('.png', '.jpg', '.jpeg', '.bmp', '.gif', '.tiff', '.tif'),
                is_img = false,
                res;
		
			for (var i=0; i< img_exts.length; i++) {
				if (img_exts[i] == ext) {
					is_img = true;
					break;
				}
			}
		
			var components = [];
			var splitchar = '';
			if ($input.val().indexOf("\\") > 0 && $input.val().indexOf("/") < 0) {
				splitchar = "\\";
			} else {
				splitchar = "/";
			}
			components = $input.val().split(splitchar);
			var fname = components[components.length - 1];
		
			var prompt_text = false;  // Prompt user for descriptive text.
                                      // The alternative is just using the filename.
		
			if (prompt_text) {
		
				if (is_img) {
					res = window.prompt(strings.get('editor_addImageDesc', $input.val()));
				} else {
					res = window.prompt(strings.get('editor_addLinkDesc', $input.val()));
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
		
		var fieldset = field,
			num_fields = 0,
			button_markup = '<a href="#" class="insert-link">Insert Link</a>';
		
		// Add the "Add Field" button
		var btn_markup = '<div><label for="add_upload">File uploads</label> ' +
			'<a href="#" id="add_upload" class="add_upload_btn">Add Upload</a></div>';
		$(fieldset).prepend(btn_markup);
		
		// Get the starting field index
		var has_fields = $(fieldset).find('input[id^="upload"]').length;
		if (has_fields > 0) {
			$(fieldset).find('input[id^="upload"]')
				.each( function () {
					var num = parseInt($(this).attr('id').replace(/upload/, ''), 10);
					if (num > num_fields) {
						num_fields = num;
					}
					if (editor) {
						var btnElem = $(button_markup);
						$(this).after(btnElem);
						btnElem.click(function () {
							addLink($(this).prev());
							return false;
						});
					}
				} );
		}
		var addField = function () {
			var cls = editor ? '' : ' class="none"',
				elems,
				markup = '<div>' +
                         '<label'+cls+' for="upload'+num_fields+'">Select file</label> ' +
                         '<input type="file" name="upload[]" id="upload'+num_fields+'" />';
			num_fields++;
			if (editor) {
				markup += button_markup;
			}
			markup += '</div>';
			elems = $(markup);
			
			if (editor) {
				alert(elems.find('.insert-link').length + markup);
				elems.find(".insert-link").click(function () {
					addLink($(this).prev());
					return false;
				});
			}
			
			$(fieldset).append(elems);
			
			return false;
		};
		
		$(fieldset).find('.add_upload_btn').click( function () {
			addField();
			return false;
		});
	}
	
	$('.upload_field').each( function (index) {
		initUploadFieldset(this, typeof(lbcode_editor) != 'undefined' ? lbcode_editor : false);
	} );
	
} );