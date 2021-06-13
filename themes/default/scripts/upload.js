/*globals strings, lnblog, lbcode_editor, AJAX_URL */
/*jshint regexp: false */
Dropzone.autoDiscover = false;
var initializeUpload = function () {

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

    var generateUrl = function (action, use_entry) {
        var base = window.AJAX_URL || '';
        var url = base + '?action=' + action;
        var entryid = '';
        if (use_entry) {
            var full_path = window.location.href.replace(/\?.*/, '');
            entryid = full_path.replace(base, '');
            url += '&entry=' + entryid;
        }
        var query_params = window.location.search.substr(1).split('&');

        for (var i = 0; i < query_params.length; i++) {
            var pieces = query_params[i].split('=');
            if (pieces[0] === 'draft') {
                url += '&draft=' + pieces[1];
            }
        }

        return url;
    };

    var generateData = function (data) {
        data['xrf-token'] = $('input[name="xrf-token"]').val();
        return data;
    };

    var removeUpload = function() {
        var $node = $(this).closest('.attachment');
        var file_name = $node.data('file');
        var should_remove = confirm("Really delete file '" + file_name + "'?");
        var data = generateData({'file': file_name});
        var url = generateUrl('removefile');

        if (should_remove) {
            $.post(url, data)
            .done(
                function() {
                    var $list = $node.closest('.attachment-list');
                    $node.remove();
                    if ($list.children().length === 0) {
                        var link_name = $list.hasClass('entry-attachments') ? 'entry-attachments' : 'blog-attachments';
                        $list.hide();
                        $list.parent().find('[name="' + link_name + '"]').hide();
                    }
                }
            )
            .fail(
                function() {
                    alert("Error removing file '" + file_name + "'.");
                }
            );
        }

        return false;
    };

    var resizeUpload = function () {
        var $self = $(this).closest('.attachment');
        var file_name = $self.data('file');
        var data = generateData({'file': file_name, 'mode': $(this).val()});
        var url = generateUrl('scaleimage', true);

        $self.find('.scale-link, .scale-select').hide();
        $self
            .find('.status-label')
            .html('<span class="loading"></span>' + strings.upload_scaleStatusProgress)
            .show();

        $.post(url, data)
            .done(
                function (response) {
                    var result = {};
                    try {
                        result = JSON.parse(response);
                    } catch (err) {
                        result.success = false;
                        result.error = response;
                    }
                    if (result.success) {
                        $self.find('.status-label').addClass('success').text(strings.upload_scaleStatusSuccess).show();
                        var $thumb_node = $self.clone();
                        $thumb_node
                            .data('file', result.file)
                            .text(result.file);
                        $self.after($thumb_node);
                    } else {
                        $self.find('.status-label').addClass('failure').text(strings.upload_scaleStatusError + result.error);
                    }
                    setTimeout(
                        function () {
                            $self.find('.status-label').fadeOut(
                                400, 
                                function () {
                                    $self.find('.scale-link').fadeIn(400, setAttachmentControls);
                                }
                            );
                        },
                        3000
                    );
                }
            )
            .fail(
                function (response) {
                    var result = JSON.parse(response);
                    $self.find('.status-label').addClass('failure').text(strings.upload_imageStatusError);
                    setTimeout(
                        function () {
                            $self.find('.scale-link .scale-select, .status-label').toggle();
                        }, 3000
                    );
                }
            );
    };

    $('.attachment-list-toggle').on(
        'click', 
        function() {
            var $this = $(this);
            var type = $this.attr('name');
            $this.parent().find('.' + type).toggle();
            return false;
        }
    );
    $('.attachment-list').toggle(false);

    var setAttachmentControls = function () {
        $('.attachment .remove-link, .scale-link, .scale-select, .status-label').remove();
        $('.attachment').each(
            function() {
                var $link = $('<a href="#" class="remove-link" title="' + strings.editor_removeLink + '">&times;</a>');
                $link.on('click', removeUpload);
                $(this).append($link);

                var file_name = $(this).data('file') || '';
                var is_supported_image = file_name.toLowerCase().match(/.+.(jpg|jpeg|png)/);
                var is_scaled_version = file_name.match(/.+-(thumb|small|med|large).(jpg|png)/);
                if (is_supported_image && !is_scaled_version) {
                    var $scale_link = $(
                        '<a href="#" class="scale-link" title="' + strings.upload_scalerLink + '"></a>'
                    );
                    $scale_link.on(
                        'click', function () {
                            $scaler_box.toggle();
                            return false;
                        }
                    );
                    var $scaler_box = $(
                        '<select class="scale-select">' + 
                        '<option>' + strings.upload_scalePlaceholderLabel + '</option>' +
                        '<option value="thumb">' + strings.upload_scaleThumbLabel + '</option>' +
                        '<option value="small">' + strings.upload_scaleSmallLabel + '</option>' +
                        '<option value="med">' + strings.upload_scaleMediumLabel + '</option>' +
                        '<option value="large">' + strings.upload_scaleLargeLabel + '</option>' +
                        '</select>'
                    );
                    $scaler_box.on('change', resizeUpload);
                    $scaler_box.hide();
                    var $status_label = $('<span class="status-label"></span>');
                    $status_label.hide();
                    $(this).append($scale_link);
                    $(this).append($scaler_box);
                    $(this).append($status_label);
                }
            }
        );
    };

    var uploadUrl = function (files) {
       return '?action=upload&ajax=1';
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
           result.subject = result.subject || '-';
           result.body = result.body || '-';
           return result;
       }
       return {
           'xrf-token': $('input[name="xrf-token"]').val()
       };
    };

    var uploadSuccess = function (file, response, data) {
       var responseData = typeof response === 'string' ? JSON.parse(response) : response;
       var hasEntryData = !!window.entryData;
       var entryData = window.entryData || {};
       var $new_file = $('<li class="attachment"></li>')
           .attr('data-file', file.name)
           .text(file.name);

       if (entryData.entryId) {
           $('.entry-attachments').append($new_file);
           $('a[name="entry-attachments"]').show();
           $('.entry-attachments').show();
       } else {
           $('.blog-attachments').append($new_file);
           $('a[name="blog-attachments"]').show();
           $('.blog-attachments').show();
       }
       setAttachmentControls();
       return true;
    };

    setAttachmentControls();

    $("#filedrop").dropzone(
        {
            url: uploadUrl,
            paramName: 'upload',
            params: parameterData,
            addRemoveLinks: true,
            dictDefaultMessage: strings.upload_droponeText,
            init: function () {
               this.on("success", uploadSuccess);
               $('#fileupload').hide();
            }
        }
    );
};

$(document).ready(initializeUpload);
