<?php
# Plugin: TinyMCEEditor
# This plugin provides a WYSIWYG (What You See Is What You Get) editor widget for typing your posts.
# It uses <TinyMCE at http://www.tinymce.com/> as the editor control.
#
# The plugin currently provides two "theme" options for TinyMCE.  The default is "Advanced", which provides
# a fairly full-featured editor experience, with most of the options and controls you're likely to need.  The
# "Basic" theme is a much simpler, more stripped-down version for those who don't like a lot of clutter in
# their editor's tool bar.
#
# The other option for this plugin is the URL to the TinyMCE instance to use.  Unless you want to host your own
# copy of TinyMCE, you can ignore this.  The default value is the CDN version of TinyMCE 4.0 hosted by CacheFly.
# This is free for public use and is probably much faster than your server anyway, so it will be the right
# choice for most people.

class TinyMCEEditor extends Plugin
{
    public $theme;

    protected $file_extensions = array('htm', 'html');

    public function __construct() {
        $this->plugin_desc = _("Use TinyMCE for the post editor and file editor.");
        $this->plugin_version = "0.4.0";
        $this->addOption(
            "theme", _("TinyMCE theme to use"), "advanced", "select",
            array("basic" => _("Basic"), "advanced" => _("Advanced"))
        );
        parent::__construct();
    }

    public function show_editor($selector = '') {
        Page::instance()->addPackage('tinymce');
        ob_start();
        ?>
        // <script>
        /*global tinymce, tinyMCE, current_text_content:true */
        var default_link_handler = lnblog.editor.html.insertLinkHandler;
        var default_image_handler = lnblog.editor.html.insertImageHandler;

        var MARKUP_HTML = <?php echo MARKUP_HTML?>;
        var selector = "<?php echo $selector?>";
        <?php if ($this->theme == 'advanced'): ?>
        var init = {
            theme: "silver",
            convert_urls: false,
            plugins: [
                "link lists image searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime",
                "media nonbreaking hr charmap table directionality emoticons template paste preview"
            ],
            browser_spellcheck: true,
            gecko_spellcheck: true,
            toolbar: "bold italic underline | style-code | bullist numlist | forecolor backcolor | link image media emoticons | preview",
            contextmenu: "link linkchecker image imagetools table spellchecker configurepermanentpen",
            removed_menuitems: "newdocument",
            mobile: {
                theme: 'mobile',
                toolbar: ['bold', 'italic', 'underline', 'link', 'unlink', 'image', 'bullist', 'numlist', 'fontsizeselect', 'forecolor', 'styleselect', 'undo', 'redo', 'removeformat'],
                plugins: ['autolink', 'lists'],
            }
        };
        <?php else: ?>
        var init = {
            mobile: {
                theme: 'mobile'
            }
        };
        <?php endif; ?>

        init['selector'] = selector || 'textarea#body',
        init['setup'] = function(ed) {
            ed.on("change", function(e) {
                current_text_content = ed.getContent();
                $(selector).val(current_text_content);
            });
        };
        init['relative_urls'] = false;

        var set_insert_handlers = function () {
            lnblog.editor.html.insertLinkHandler = function (filename, link) {
                if (tinymce.activeEditor) {
                    var content = '<a href="' + link + '">' + filename + '</a>';
                    tinymce.activeEditor.insertContent(content);
                } else {
                    default_image_handler(filename, link, full_link);
                }
            };
            lnblog.editor.html.insertImageHandler = function (filename, link, full_link) {
                if (tinymce.activeEditor) {
                    var content = '<img src="' + link + '" alt="' + filename +'" />';
                    if (full_link) {
                        content = '<a href="' + full_link + '">' + content + '</a>';
                    }
                    tinymce.activeEditor.insertContent(content);
                } else {
                    default_image_handler(filename, link, full_link);
                }
            };
        };

        var initialize_tinymce = function() {
            var $input_mode = $('#input_mode');
            var unconditional_display = selector ? true : false;
            var content_fetch_timer = null;

            // Suppress the plugin on the list-link page.
            if (window.location.href.match('[?&]list=yes')) {
                return;
            }

            var setContentFetchInterval = function() {
                content_fetch_timer = setInterval(function tinymceUpdate() {
                    current_text_content = tinyMCE.activeEditor.getContent();
                }, 5000);
            };
            var clearContentFetchInterval = function() {
                clearInterval(content_fetch_timer);
            };

            if (unconditional_display) {
                tinymce.init(init);
                set_insert_handlers();
                $('#postform').addClass('rich-text');

                var $toggle_button = $('<button style="float:right"><?php p_('Toggle HTML Editor')?></button>');
                $toggle_button.off('click').on('click', function (e) {
                    e.preventDefault();
                    if (tinymce.editors.length === 0) {
                        tinymce.init(init);
                        setContentFetchInterval();
                    } else {
                        clearContentFetchInterval();
                        tinymce.remove();
                    }
                    return false;
                });

                $('textarea').closest('form')
                              .find('button, input[type=submit], input[type=reset]')
                              .filter(':last')
                              .after($toggle_button);
            } else {
                if ($input_mode.val() == MARKUP_HTML) {
                    tinymce.init(init);
                    set_insert_handlers();
                    $('#postform').addClass('rich-text');
                }
                $input_mode.on('change.editor', function(e) {
                    var mode = $(this).val();
                    $('#postform').toggleClass('rich-text', mode == MARKUP_HTML);
                    if (mode == MARKUP_HTML) { // HTML mode
                        tinymce.init(init);
                        set_insert_handlers();
                        setContentFetchInterval();
                    } else {
                        clearContentFetchInterval();
                        lnblog.editor.html.insertLinkHandler = default_link_handler;
                        lnblog.editor.html.insertImageHandler = default_image_handler;
                        tinymce.remove();
                    }
                });
            }
        };

        // This will be called by the editor page to force a sync of editor
        // content to the textarea.  This prevents desyncing issues when 
        // switching between markup modes.
        var editor_commit_current = function() {
            if (tinymce.activeEditor) {
                tinymce.triggerSave();
            }
        };

        jQuery(function() {
            try {
                initialize_tinymce();
            } catch (error) {
                console.log("Unable to initialize TinyMCE editor");
                console.log(error);
            }
        });
        // </script>
        <?php
        $scr = ob_get_clean();
        Page::instance()->addInlineScript($scr);
    }

    public function file_editor() {
        $file_ext = pathinfo(GET('file'), PATHINFO_EXTENSION);
        $editor = GET('richedit');
        if (in_array($file_ext, $this->file_extensions) && $editor != 'false') {
            $this->show_editor('textarea#output');
        }
    }
}

$plug = new TinyMCEEditor();
$plug->registerEventHandler("posteditor", "ShowControls", "show_editor");
$plug->registerEventHandler('page', 'FileEditorReady', 'file_editor');
