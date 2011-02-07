$(document).ready( function () {
	
	function initUploadFieldset(field, editor) {
		
		var fieldset = field;
		var num_fields = 0;
		
		var uploadLinkClick = function () {
			// FIXME: Cheap, cheap hack
			var chkState = $('#adduploadlink').attr('checked');
			$('#adduploadlink').attr('checked', true);
			$(this).prev().change();
			$('#adduploadlink').attr('checked', chkState);
			return false;
		};
		
		var button_markup = '<button>Insert Link</button>';
		
		// Add the "Add Field" button
		var btn_markup = '<div><label for="add_upload">File uploads</label>' +
			'<button id="add_upload" class="add_upload_btn">Add Upload</button></div>';
		$(fieldset).prepend(btn_markup);
		
		// Get the starting field index
		var has_fields = $(fieldset).find('input[id^="upload"]').length;
		if (has_fields > 0) {
			$(fieldset).find('input[id^="upload"]')
				.each( function () {
					var num = parseInt($(this).attr('id').replace(/upload/, ''));
					if (num > num_fields) num_fields = num;
					if (editor) {
						$(this).change(editor.upload_add_link);
						var btnElem = $(button_markup).click(uploadLinkClick);
						$(this).after(btnElem);
					}
				} );
		}
		var addField = function () {
			var cls = editor ? '' : ' class="none"';
			num_fields++;
			var markup = '<div>' +
						 '<label'+cls+' for="upload'+num_fields+'">Select file</label>' + 
						 '<input type="file" name="upload[]" id="upload'+num_fields+'" />';
			if (editor) {
				markup += button_markup;
			}
			markup += '</div>';
			elems = $(markup);
			
			$(fieldset).append(elems);
			if (editor) {
				$(elems).find("input[type='file']").change(editor.upload_add_link);
				$(elems).find("button").click(uploadLinkClick);
			}
			return false;
		}
		
		$(fieldset).find('.add_upload_btn').click( function () {
			addField();
			return false;
		});
	}
	
	$('.upload_field').each( function (index) {
		initUploadFieldset(this, typeof(lbcode_editor) != 'undefined' ? lbcode_editor : false);
	} );
	
} );