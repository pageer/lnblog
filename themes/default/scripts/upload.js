$(document).ready( function () {
	
	function initUploadFieldset(field, editor) {
		
		var fieldset = field;
		var num_fields = 0;
		
		var button_markup = ''; //'<button class="insert_upload_link_btn">Insert Link</button>';
		
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
					if (editor) $(this).after(button_markup);
				} );
		}
		var addField = function () {
			var linkBtn = $('<button>Insert Link</button>').click( function () {
				
			});
			
			var cls = editor ? '' : ' class="none"';
			num_fields++;
			var markup = '<div><label'+cls+' for="upload'+num_fields+'">Select file</label>' + 
						 '<input type="file" name="upload[]" id="upload'+num_fields+'" />';
			if (editor) {
				markup += button_markup;
			}
			markup += '</div>';
			
			$(fieldset).append(markup);
			if (editor) {
				$(fieldset).find("input[type='file']").change(editor.upload_add_link);
			}
			return false;
		}
		
		$(fieldset).find('.add_upload_btn').click( function () {
			addField();
			return false;
		});
		
		if (editor) {
			$(fieldset).find('.insert_upload_link_btn').click( function () {
				//editor.upload_add_link($(this).prev());
				return editor.upload_add_link();
				//alert($(this).prev());
				return false;
			} );
		}
		
	}
	
	$('.upload_field').each( function (index) {
		initUploadFieldset(this, typeof(lbcode_editor) != 'undefined' ? lbcode_editor : false);
	} );
	
} );