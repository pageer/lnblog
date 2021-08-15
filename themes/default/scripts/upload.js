/*globals strings, lnblog, lbcode_editor, AJAX_URL */
/*jshint regexp: false */
Dropzone.autoDiscover = false;
var initializeUpload = function () {

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

    var getProfile = function () {
        var profile = window.location.search.match(/profile=([^&]*)/);
        return profile ? profile[1] : null;
    };

    var generateData = function (data) {
        data['xrf-token'] = $('input[name="xrf-token"]').val();
        var profile = getProfile();
        if (profile) {
            data.profile = profile;
        }
        return data;
    };

    var removeUpload = function() {
        var $node = $(this).closest('.attachment');
        var file_name = $node.data('file');
        var should_remove = confirm("Really delete file '" + file_name + "'?");
        var data = generateData({'file': file_name});
        if ($node.closest('.entry-attachments').length > 0) {
            data.entryFile = true;
        }
        var url = generateUrl('removefile', true);

        if (should_remove) {
            $.post(url, data)
            .done(
                function() {
                    var $list = $node.closest('.attachment-list');
                    $node.remove();
                    if ($list.children().length === 0) {
                        var link_name = 'blog-attachments';
                        if ($list.hasClass('entry-attachments')) {
                            link_name = 'entry-attachments';
                        } else if ($list.hasClass('profile-attachments')) {
                            link_name = 'profile-attachments';
                        }
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
        if ($self.closest('.entry-attachments').length > 0) {
            data.entryFile = true;
        }
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
                        $thumb_node.data('file', result.file);
                        $thumb_node.find('.link')
                            .attr('href', result.file)
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
            $this.parent().find('.' + type).slideToggle();
            return false;
        }
    );
    $('.attachment-list').toggle(false);

    var setAttachmentControls = function () {
        var $editor_body = $('textarea');
        var page_has_edtor = $editor_body.length > 0;

        $('.attachment .attachment-control').remove();
        $('.attachment').each(function() {
            var $link = $('<a href="#" class="attachment-control remove-link" title="' + strings.editor_removeLink + '">&times;</a>');
            $link.on('click', removeUpload);
            $(this).append($link);

            var file_name = $(this).data('file') || '';

            var is_image = file_name.toLowerCase().match(/.+.(jpg|jpeg|png|gif|tif|tiff|svg)/);
            var is_supported_image = file_name.toLowerCase().match(/.+.(jpg|jpeg|png)/);
            var is_scaled_version = file_name.match(/.+-(thumb|small|med|large).(jpg|png)/);

            if (page_has_edtor) {
                var $link_insert = $(
                    '<a href="#" class="attachment-control insert-link"></a>'
                );
                $link_insert.attr('title', strings.upload_insertLink);
                $link_insert.on('click', function () {
                    var markup_type = lnblog.editor.getMarkupMode();
                    var is_entry_attachment = $(this).closest('.attachment-list').hasClass('entry-attachments');
                    var $attachment = $(this).closest('.attachment');
                    var link = is_entry_attachment ?
                        $attachment.data('file') :
                        $attachment.find('.link').attr('href');
                    lnblog.editor[markup_type].insertLinkHandler(file_name, link);
                    return false;
                });
                $(this).append($link_insert);

                var $image_insert = $(
                    '<a href="#" class="attachment-control insert-image"></a>'
                );
                var text = is_scaled_version ? strings.upload_insertImageWithLink : strings.upload_insertImage;
                $image_insert.attr('title', text);
                $image_insert.on('click', function () {
                    var markup_type = lnblog.editor.getMarkupMode();
                    var is_entry_attachment = $(this).closest('.attachment-list').hasClass('entry-attachments');
                    var $attachment = $(this).closest('.attachment');
                    var link = is_entry_attachment ?
                        $attachment.data('file') :
                        $attachment.find('.link').attr('href');
                    var full_link = is_scaled_version ?
                        link.replace(/(.+)-(thumb|small|med|large).(jpg|png)/, '$1.$3') :
                        undefined;
                    if (lnblog.editor[markup_type].insertImageHandler) {
                        lnblog.editor[markup_type].insertImageHandler(file_name, link, full_link);
                    }
                    return false;
                });
                var markup_type = lnblog.editor.getMarkupMode();
                if (is_image && typeof(lnblog.editor[markup_type].insertImageHandler) !== 'undefined') {
                    $(this).append($image_insert);
                }
            }

            if (is_supported_image && !is_scaled_version) {
                var $scale_link = $(
                    '<a href="#" class="attachment-control scale-link" title="' + strings.upload_scalerLink + '"></a>'
                );
                $scale_link.on(
                    'click', function () {
                        $scaler_box.toggle();
                        return false;
                    }
                );
                var $scaler_box = $(
                    '<select class="scale-select attachment-control">' +
                    '<option>' + strings.upload_scalePlaceholderLabel + '</option>' +
                    '<option value="thumb">' + strings.upload_scaleThumbLabel + '</option>' +
                    '<option value="small">' + strings.upload_scaleSmallLabel + '</option>' +
                    '<option value="med">' + strings.upload_scaleMediumLabel + '</option>' +
                    '<option value="large">' + strings.upload_scaleLargeLabel + '</option>' +
                    '</select>'
                );
                $scaler_box.on('change', resizeUpload);
                $scaler_box.hide();
                var $status_label = $('<span class="status-label attachment-control"></span>');
                $status_label.hide();
                $(this).append($scale_link);
                $(this).append($scaler_box);
                $(this).append($status_label);
            }

        });
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
        var entryData = window.entryData || {};
        var $new_file = $('<li class="attachment"></li>')
            .attr('data-file', file.name);
        var $link = $('<a href="" class="link" target="_blank"></a>')
            .attr('href', file.name)
            .text(file.name);
        $new_file.append($link);

        if (entryData.entryId) {
            $('.entry-attachments').append($new_file);
            $('a[name="entry-attachments"]').show();
            $('.entry-attachments').show();
        } else if (getProfile()) {
            $('.profile-attachments').append($new_file);
            $('a[name="profile-attachments"]').show();
            $('.profile-attachments').show();
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
