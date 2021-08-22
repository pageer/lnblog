/*global strings */
var original_text_content = '';
var current_text_content = '';

$(window).on('beforeunload', function unloadHandler() {
    if (current_text_content !== original_text_content) {
        return strings.editor_leavePrompt;
    }
});

var auto_save_key = "savepostdata." + window.location.pathname;
var post_save_timeout = function () {
    if (typeof localStorage != 'undefined') {
        localStorage.setItem(auto_save_key, current_text_content);
        setTimeout(post_save_timeout, 5000);
    }
};
setTimeout(post_save_timeout, 5000);

$(function () {

    original_text_content = $('#body').val();
    current_text_content = original_text_content;

    if (typeof localStorage != 'undefined') {
        var saved_data = localStorage.getItem(auto_save_key);
        if (saved_data && saved_data != original_text_content) {
            if (window.confirm(strings.editor_restoreText)) {
                $('#body').val(saved_data);
                current_text_content = saved_data;
            }
        }
    }

    $('#body').on('change', function bodyChange() {
        current_text_content = $(this).val();
    });

    // Auto-publish widgets
    // NOTE: Current release of jQuery DateTimePicker is bugged.
    // Last update messed up the allowed times so that they start on the current minute.
    // This is to set them back to "on the hour".
    var allowedTimes = [];
    for (var i = 1; i < 24; i++) {
        allowedTimes.push(i + ':00');
    }
    $('#autopublishdate').datetimepicker(
        {
        format: 'Y-m-d h:i a',
        hours12: true,
        allowTimes: allowedTimes,
        // FIXME: Last update messed up the selector so that the selected time in the UI is
        // decremented when you bring it back up.  Possibly related to time zones
        // and daylight savings time.  In any case, this keeps that from propagating
        // to the textbox and changing the time you JUST set to an hour earlier.
        // The UI is still wrong, but I don't care enough to fix that right now.
        validateOnBlur: false
        }
    );

    $('#subject').on('blur', function set_article_path(e) {
        var subj = $("#subject").val();
        var path = subj.toLowerCase();
        if ($("#short_path").val()) {
            return true;
        }
        path = path.replace(/\W+/g, " ");
        path = path.replace(/^ */, "");
        path = path.replace(/ *$/, "");
        path = path.replace(/\W+/g, "-");
        path = path.replace(/--+/g, "-");
        $("#short_path").val(path);
        return true;
    });

    $('#publisharticle').on('change.article', function articleSet(e) {
        var is_checked = $(this).is(':checked');
        $('.sticky-toggle').toggle(is_checked);
        $('#short_path').toggle(is_checked);
    });

    $('.checktoggle[data-for]').on('change.toggle', function() {
        var $this = $(this),
            checked = $this.is(':checked'),
            for_id = '#' +  $this.attr('data-for');
        $(for_id).toggle(checked);
        if (checked) {
            $(for_id).focus();
        }
    });

    $('.entry_preview .preview-close').on('click.preview', function (e) {
        e.preventDefault();
        $('.entry_preview').hide();
        $('#postform').show();
    });

    $("#postform input[type='submit']").on('click', function () {
       $("#postform input[type='submit']").attr('rel', '');
       $(this).attr('rel', 'clicked');
    });

    $('#postform').on('submit', function () {
        // Make sure the WYSIWYG editor syncs to the text area.
        if (typeof(editor_commit_current) == 'function') {
            editor_commit_current();
        }

        if ($('#preview').attr('rel') == 'clicked') {
            var form_url = $('#postform').attr('action');
            form_url += (form_url.indexOf('?') >= 0) ? '&' : '?';
            form_url += 'preview=yes&ajax=1';

            var has_files = false;
            $("#postform input[type='file']").each(
                function () {
                    if ($(this).val()) {
                        has_files = true;
                    }
                }
            );

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
        } else {
            // Clear out the stored post data because it's being saved now.
            if (typeof localStorage != 'undefined') {
                localStorage.removeItem(auto_save_key);
            }
        }
        // Make the submission not prompt to leave the page.
        current_text_content = original_text_content;
        return true;
    });

    $('.checktoggle[data-for]').each(function() {
       var $this = $(this),
           for_id = $this.attr('data-for');
       $('#' + for_id).toggle($this.is(':checked'));
    });

    $('#publisharticle').change();
    $('#subject').focus();

    var availableTags = $('#tag_list option').map(function (idx, value) {
        var val = $(value).val();
        return val ? val : null;
    });

    $('#tags').tagit({
        singleField: true,
        allowSpaces: true,
        availableTags: availableTags,
        placeholderText: strings.editor_tagBoxPlaceholder,
        showAutocompleteOnFocus: true
    });
    $('#tag_list').hide();
});
