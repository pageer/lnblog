/*globals strings, lnblog, lbcode_editor, AJAX_URL */
/*jshint regexp: false */
Dropzone.autoDiscover = false;
$(document).ready( function () {
	
	function initUploadFieldset(field, editor) {
		
		var addLink = function ($input) {
			var ext = $input.val().replace(/^(.*)(\..+)$/, "$2"),
                img_exts = ['.png', '.jpg', '.jpeg', '.bmp', '.gif', '.tiff', '.tif'],
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
		
            res = fname;
			
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

        // Add toggling for attachment list
		
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

    var removeUpload = function() {
        var $node = $(this).closest('.attachment');
        var file_name = $node.data('file');
        var should_remove = confirm("Really delete file '" + file_name + "'?");
        var url = (window.AJAX_URL || '') + '?action=removefile';
        
        var query_params = window.location.search.substr(1).split('&');
        for (var i = 0; i < query_params.length; i++) {
            var pieces = query_params[i].split('=');
            if (pieces[0] === 'draft') {
                url += '&draft=' + pieces[1];
            }
        }

        if (should_remove) {
            $.post(url, {file: file_name})
                .done(function() {
                    var $list = $node.closest('.attachment-list');
                    $node.remove();
                    if ($list.children().length === 0) {
                        var link_name = $list.hasClass('entry-attachments') ? 'entry-attachments' : 'blog-attachments';
                        $list.hide();
                        $list.parent().find('[name="' + link_name + '"]').hide();
                    }
                })
                .fail(function() {
                    alert("Error removing file '" + file_name + "'.");
                });
        }

        return false;
    };
	
    $('.attachment-list-toggle').on('click', function() {
        var $this = $(this);
        var type = $this.attr('name');
        $this.parent().find('.' + type).toggle();
        return false;
    });
    $('.attachment-list').toggle(false);

    var createRemoveLinks = function () {
        $('.attachment .remove-link').remove();
        $('.attachment').each(function() {
            var $link = $('<a href="#" class="remove-link" title="' + strings.editor_removeLink + '">&times;</a>');
            $link.on('click', removeUpload);
            $(this).append($link);
        });
    };

    createRemoveLinks();

	$('.upload_field').each( function (index) {
		initUploadFieldset(this, typeof(lbcode_editor) != 'undefined' ? lbcode_editor : false);
	});

    $('#fileupload').hide();
    
    var uploadUrl = function (files) {
        var entryExists = (window.entryData || {}).entryExists;
        var entryIsDraft = (window.entryData || {}).entryIsDraft;
        var entryId = (window.entryData || {}).entryId;

        if (!window.entryData) {
            return '?action=upload&ajax=1';
        } else if (entryExists) {
            if (entryIsDraft) {
                return '?action=upload&draft='+entryId+'&ajax=1';
            }
            return '?action=upload&ajax=1';
        }
        return '?action=newentry&save=draft&preview=yes&ajax=1';
    };

    var parameterData = function () {
        if ($("#postform").length === 1) {
            var result = {};
            // This is a URL-encoded string, but we need json.
            var formData = $("#postform").formSerialize();
            var keys = formData.split("&");
            for (var i = 0; i < keys.length; i++) {
                var parts = keys[i].split("=", 2);
                if (parts[0].indexOf("upload") === -1) {
                    var value = (parts[1] || '').replace(/\+/g, " ");
                    result[parts[0]] = unescape(value);
                }
            }
            result['subject'] = result['subject'] || '-';
            result['body'] = result['body'] || '-';
            return result;
        }
        return {};
    };

    var uploadSuccess = function (file, response, data) {
        console.log(response, typeof response);
        var responseData = typeof response === 'string' ? JSON.parse(response) : response;
        var hasEntryData = !!window.entryData;
        var entryData = window.entryData || {};
        var $new_file = $('<li class="attachment"></li>')
            .attr('data-file', file.name)
            .text(file.name);

        // Update the stored entry information.
        if (hasEntryData && !entryData.entryExists && responseData.id) {
            window.entryData = {
                entryId: responseData.id,
                entryExists: responseData.exists,
                entryIsDraft: responseData.isDraft
            };

            var $input = $('input[name="entryid"]');
            console.log("Found?", $input.length);
            if ($input.length === 0) {
                console.log("Appending for element");
                $input = $('<input type="hidden" name="entryid"/>');
                $('#postform').append($input);
            }
            $input.val(responseData.id);
        }

        if (entryData.entryId) {
            $('.entry-attachments').append($new_file);
            $('a[name="entry-attachments"]').show();
            $('.entry-attachments').show();
        } else {
            $('.blog-attachments').append($new_file);
        }
        createRemoveLinks();
        return true;
    };

    $("#filedrop").dropzone({
        url: uploadUrl,
        paramName: 'upload',
        params: parameterData,
        addRemoveLinks: true,
        init: function () {
            this.on("success", uploadSuccess);
        }
    });
} );
